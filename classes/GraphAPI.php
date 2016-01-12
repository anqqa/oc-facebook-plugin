<?php namespace Klubitus\Facebook\Classes;

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
    protected $appId;

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
     * Get app access token.
     *
     * @return  string
     */
    public function appAccessToken() {
        return $this->appId . '|' . $this->appSecret;
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
            Log::error('Facebook Graph API call failed.', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
                'code'     => $e->getCode(),
                'subcode'  => $e->getSubErrorCode(),
                'status'   => $e->getHttpStatusCode(),
                'type'     => $e->getErrorType(),
            ]);

            throw new SystemException('Graph error: ' . $e->getMessage());
        }
        catch (FacebookSDKException $e) {
            Log::error('Facebook SDK error.', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
                'code'     => $e->getCode(),
            ]);

            throw new SystemException('SDK error: ' . $e->getMessage());
        }

        return $response;
    }
}
