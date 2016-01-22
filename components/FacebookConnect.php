<?php namespace Klubitus\Facebook\Components;

use Auth;
use Flash;
use Cms\Classes\ComponentBase;
use Klubitus\Facebook\Classes\GraphAPI;
use Klubitus\Facebook\Models\UserExternal as UserExternalModel;
use Log;
use October\Rain\Database\ModelException;
use October\Rain\Exception\SystemException;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Components\Session;
use Redirect;
use Request;

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
        $this->redirectLogin  = $this->controller->pageUrl($this->property('redirectLogin'));
        $this->redirectSignup = $this->controller->pageUrl($this->property('redirectSignup'));

        return parent::onRun();
    }


    public function onFacebookError() {

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

                        Flash::error('Facebook sign up failed.');

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
            $user = $externalUser->user;
        }

        Auth::login($user, true);

        if (post('redirect', true)) {
            if ($newUser) {
                return Redirect::intended(post('redirect_signup', Request::fullUrl()));
            }
            else {
                return Redirect::intended(post('redirect_login', Request::fullUrl()));
            }
        }

        Flash::success('Welcome, ' . $user->username . '!');
    }

}