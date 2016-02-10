<?php namespace Klubitus\Facebook\Components;

use Auth;
use Flash;
use Cms\Classes\ComponentBase;
use Klubitus\Facebook\Classes\GraphAPI;
use Klubitus\Facebook\Models\UserExternal as UserExternalModel;
use Lang;
use Log;
use October\Rain\Database\ModelException;
use October\Rain\Exception\SystemException;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Components\Session;
use Redirect;
use Request;
use URL;

class FacebookConnect extends Session {

    /**
     * @var  string  Facebook app id
     */
    public $appId;

    /**
     * @var  string  Facebook app secret
     */
    protected $appSecret;

    /**
     * @var  string
     */
    public $graphAPIVersion = GraphAPI::API_VERSION;

    /**
     * @var  string  URL to redirect to after login
     */
    public $redirectLogin;

    /**
     * @var  string  URL to redirect to after signup
     */
    public $redirectSignup;


    public function componentDetails() {
        return [
            'name'        => 'Facebook Connect.',
            'description' => 'Facebook Connect button functionality.'
        ];
    }


    public function defineProperties() {
        return array_merge(parent::defineProperties(), [
            'redirectLogin' => [
                'title'       => '',
                'description' => '',
                'type'        => 'dropdown',
                'default'     => '',
            ],
            'redirectSignup' => [
                'title'       => '',
                'description' => '',
                'type'        => 'dropdown',
                'default'     => '',
            ],
        ]);
    }


    public function getRedirectLoginOptions() {
        return parent::getRedirectOptions();
    }


    public function getRedirectSignupOptions() {
        return parent::getRedirectOptions();
    }


    /**
     * Executed when this component is bound to a page or layout.
     */
    public function onRun() {
        $this->appId          = GraphAPI::instance()->appId;
        $this->redirectLogin  = $this->property('redirectLogin') ? $this->controller->pageUrl($this->property('redirectLogin')) : URL::previous();
        $this->redirectSignup = $this->property('redirectSignup') ? $this->controller->pageUrl($this->property('redirectSignup')) : URL::previous();

        return parent::onRun();
    }


    /**
     * Failed login with Facebook.
     */
    public function onFacebookError() {
        $status = (int)post('status');

        Log::error('Facebook login failed.', [
            'status' => $status,
        ]);

        Flash::error(Lang::get('klubitus.facebook::lang.login.login_failed'));
    }


    /**
     * Successful login with Facebook.
     */
    public function onLoginWithFacebook() {
        $newUser = false;

        try {
            $accessToken  =  GraphAPI::instance()->getUserAccessToken(true);
            $response     =  GraphAPI::instance()->get('/me', ['id', 'email', 'name'], $accessToken);
            $facebookUser = $response->getGraphUser();
        }
        catch (SystemException $e) {

            Flash::error('Facebook login failed.');

            return;
        }

        $externalUser = UserExternalModel::facebook([$facebookUser->getId()])->first();
        if (!$externalUser) {

            // No user found by Facebook user id
            $user = UserModel::where('email', '=', $facebookUser->getEmail())->first();
            if (!$user) {

                // No user found by email either, sign up
                $newUser = true;
                $password = uniqid();
                $signup = [
                    'username' => $facebookUser->getName(),
                    'name' => $facebookUser->getName(),
                    'email' => $facebookUser->getEmail(),
                    'password' => $password,
                    'password_confirmation' => $password,
                ];

                try {
                    $user = Auth::register($signup, true);
                }
                catch (ModelException $e) {
                    Log::error('Facebook sign up failed: ' . $e->getMessage());

                    $signup['username'] .= rand(1000, 9999);

                    try {
                        $user = Auth::register($signup, true);
                    }
                    catch (ModelException $e) {
                        Log::error('Facebook sign up failed: ' . $e->getMessage());

                        Flash::error(Lang::get('klubitus.facebook::lang.register.register_failed'));

                        return;
                    }
                }

            }

            UserExternalModel::create([
                'user_id' => $user->id,
                'provider' => UserExternalModel::PROVIDER_FACEBOOK,
                'external_user_id' => $facebookUser->getId(),
                'token' => $accessToken->getValue(),
                'expires_at' => $accessToken->getExpiresAt()
            ]);

        }
        else {
            $externalUser->token = $accessToken->getValue();
            $externalUser->expires_at = $accessToken->getExpiresAt();
            $externalUser->save();

            $user = $externalUser->user;
        }

        Auth::login($user, true);

        if (post('redirect', true)) {
            if ($newUser) {
                return Redirect::to(post('redirect_signup', URL::previous() ?: Request::fullUrl()));
            }
            else {
                return Redirect::to(post('redirect_login', URL::previous() ?: Request::fullUrl()));
            }
        }
    }

}
