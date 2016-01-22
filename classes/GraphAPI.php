<?php namespace Klubitus\Facebook\Classes;

use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Klubitus\Facebook\Models\Settings as FacebookSettings;
use Log;
use October\Rain\Exception\SystemException;


class GraphAPI {
    use \October\Rain\Support\Traits\Singleton;

    const API_VERSION = 'v2.5';

    /**
     * @var  string
     */
    public $appId;

    /**
     * @var  string
     */
    protected $appSecret;

    /**
     * @var  Facebook
     */
    protected $fb;


    public function init() {
        $this->appId = FacebookSettings::get('app_id');
        $this->appSecret = FacebookSettings::get('app_secret');
        $this->fb = new Facebook([
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
            'default_graph_version' => self::API_VERSION,
        ]);
    }


    /**
     * Make an API call.
     *
     * @param   string  $endpoint
     * @param   array   $fields
     * @param   string  $accessToken
     * @return  \Facebook\FacebookResponse
     * @throws  SystemException
     */
    public function get($endpoint, array $fields = null, $accessToken = null) {
        try {
            if ($fields) {
                $endpoint .= '?fields=' . implode(',', $fields);
            }

            $response = $this->fb->get($endpoint, $accessToken);
        }
        catch (FacebookResponseException $e) {
            Log::error('Facebook Graph API call failed when doing a get request.', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
                'code'     => $e->getCode(),
                'subcode'  => $e->getSubErrorCode(),
                'status'   => $e->getHttpStatusCode(),
                'type'     => $e->getErrorType(),
            ]);

            throw new SystemException('Facebook Graph API error: ' . $e->getMessage());
        }
        catch (FacebookSDKException $e) {
            Log::error('Facebook SDK error when doing a get request.', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
                'code'     => $e->getCode(),
            ]);

            throw new SystemException('Facebook SDK error: ' . $e->getMessage());
        }

        return $response;
    }


    /**
     * Get app access token.
     *
     * @return  string
     */
    public function getAppAccessToken() {
        return $this->appId . '|' . $this->appSecret;
    }


    /**
     * Get user access token.
     *
     * @param   bool  $longLived  Exchange short-lived access token for a long-lived one
     * @return  AccessToken
     * @throws  SystemException
     */
    public function getUserAccessToken($longLived = false) {
        $accessToken = null;

        $helper = $this->fb->getJavaScriptHelper();

        try {
            $accessToken = $helper->getAccessToken();
        }
        catch (FacebookResponseException $e) {
            Log::error('Facebook Graph API call failed when getting an access token.', [
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
                'subcode' => $e->getSubErrorCode(),
                'status'  => $e->getHttpStatusCode(),
                'type'    => $e->getErrorType(),
            ]);

            throw new SystemException('Facebook Graph API error: ' . $e->getMessage());
        }
        catch (FacebookSDKException $e) {
            Log::error('Facebook SDK error when getting an access token.', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            throw new SystemException('Facebook SDK error: ' . $e->getMessage());
        }

        if ($longLived && $accessToken && !$accessToken->isLongLived()) {
            try {
                $oAuth2Client = $this->fb->getOAuth2Client();
                $accessToken  = $oAuth2Client->getLongLivedAccessToken($accessToken);
            }
            catch (FacebookSDKException $e) {
                Log::error('Facebook SDK error when getting a long-lived access token.', [
                    'error' => $e->getMessage(),
                    'code'  => $e->getCode(),
                ]);
            }
        }

        return $accessToken;
    }


    /**
     * Returns the JavaScript helper.
     *
     * @return  \Facebook\Helpers\FacebookJavaScriptHelper
     */
    public function getJavaScriptHelper() {
        return $this->fb->getJavaScriptHelper();
    }

}
