<?php
/**
 * Twitter.php - twitter api wrapper
 *
 * User: nathanmyles
 * Date: 2013-01-20 10:29 PM
 *
 */

// load OAuth lib. You can find it at http://oauth.net
require_once(dirname(__FILE__) . '/OAuth.php');

class Twitter
{
    const DEBUG = true;

    // curl resource
    private $curl;

    private $httpCode;

    private $httpInfo;

    private $errors;

    // api urls
    private static $apiUrl = 'https://api.twitter.com/1.1/';
    private static $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';
    private static $authenticateUrl = 'https://api.twitter.com/oauth/authenticate';
    private static $authorizeUrl = 'https://api.twitter.com/oauth/authorize';
    private static $requestTokenURL = 'https://api.twitter.com/oauth/request_token';

    private $validMethods = array('GET', 'POST');
    private $validFormats = array('json');

    // consumer
    private $consumer;

    // OAuth token
    private $token;

    private static $defaultUserAgent = 'PHP Twitter Client';
    private $userAgent;

    private $timeOut = 30;
    private $connectionTimeOut = 30;

    private $sha1Method;

    public function __construct($key, $secret, $token = null, $tokenSecret = null)
    {
        $this->sha1Method = new OAuthSignatureMethod_HMAC_SHA1();
        $this->consumer = new OAuthConsumer($key, $secret);
        if(!empty($token) && !empty($tokenSecret))
        {
            $this->token = new OAuthConsumer($token, $tokenSecret);
        }
        else
        {
            $this->token = null;
        }
    }

    public function __destruct()
    {
        if($this->curl != null) curl_close($this->curl);
    }

    /**
     * get a request token from twitter
     *
     * @returns array a key/value array containing oauth_token and oauth_token_secret
     */
    public function getRequestToken($oauthCallback = null)
    {
        $parameters = array();
        if (!empty($oauthCallback))
        {
            $parameters['oauth_callback'] = $oauthCallback;
        }
        $request = $this->oAuthRequest(self::$requestTokenURL, $parameters);
        $token = OAuthUtil::parse_parameters($request);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    /**
     * send user to be authorized by twitter
     *
     */
    public function authorize($token, $signInWithTwitter = false, $forceLogin = 'false')
    {
        header('Location: ' . $this->getAuthorizeURL($token, $signInWithTwitter, $forceLogin));
    }

    /**
     * get the authorize URL
     *
     * @returns string
     */
    public function getAuthorizeURL($token, $signInWithTwitter = false, $forceLogin = 'false')
    {
        if (is_array($token))
        {
            $token = $token['oauth_token'];
        }
        if ($signInWithTwitter)
        {
            return self::$authorizeUrl . "?oauth_token={$token}";
        }
        else
        {
            return self::$authenticateUrl . "?oauth_token={$token}";
        }
    }

    /**
     * exchange request token and secret for an access token and
     * secret, to sign API calls.
     *
     * @returns array("oauth_token" => "the-access-token",
     *                "oauth_token_secret" => "the-access-secret",
     *                "user_id" => "user-id",
     *                "screen_name" => "user-screen-name")
     */
    public function getAccessToken($oauthVerifier = false) {
        $parameters = array();
        if (!empty($oauthVerifier)) {
            $parameters['oauth_verifier'] = $oauthVerifier;
        }
        $request = $this->oAuthRequest(self::$accessTokenUrl, $parameters);
        $token = OAuthUtil::parse_parameters($request);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    // exchange username / password for token
    public function getXAuthToken($username, $password) {
        $parameters = array();
        $parameters['x_auth_username'] = $username;
        $parameters['x_auth_password'] = $password;
        $parameters['x_auth_mode'] = 'client_auth';
        $request = $this->oAuthRequest(self::$accessTokenUrl, $parameters, 'POST');
        $token = OAuthUtil::parse_parameters($request);
        $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
        return $token;
    }

    // format and sign an OAuth / API request
    public function oAuthRequest($url, $parameters = null, $method = 'GET', $file = null, $format = 'json') {
        $url = $this->buildUrl($url, $format);
        $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
        $request->sign_request($this->sha1Method, $this->consumer, $this->token);
        return $this->request($request->get_normalized_http_url(), $request->to_postdata(), $method, $file, $format);
    }

    public function request($url, $parameters = null, $method = 'GET', $file = null, $format = 'json')
    {
        $validMethods = $this->validMethods;

        // validate method
        if(!in_array($method, $validMethods))
        {
            throw new Exception("Invalid Method: $method (Valid Methods: " . implode(',', $validMethods) . ")");
        }

        $validFormats = $this->validFormats;

        // validate method
        if(!in_array($format, $validFormats))
        {
            throw new Exception("Invalid Format: $format (Valid Formats: " . implode(',', $validFormats) . ")");
        }

        $url = $this->buildUrl($url, $format);

        // handle post request
        if ($method == 'POST')
        {
            // upload file
            if ($file != null)
            {
                $boundary = md5(time());

                $fileInfo = pathinfo($file);

                $mimeType = 'application/octet-stream';
                if ($fileInfo['extension'] == 'jpg' || $fileInfo['extension'] == 'jpeg')
                {
                    $mimeType = 'image/jpeg';
                }
                elseif($fileInfo['extension'] == 'gif')
                {
                    $mimeType = 'image/gif';
                }
                elseif($fileInfo['extension'] == 'png')
                {
                    $mimeType = 'image/png';
                }

                // build file request contents
                $content = '--' . $boundary . "\r\n";

                // set file
                $content .= 'Content-Disposition: form-data; name=image; filename="' .
                    $fileInfo['basename'] . '"' . "\r\n";
                $content .= 'Content-Type: ' . $mimeType . "\r\n";
                $content .= "\r\n";
                $content .= file_get_contents($file);
                $content .= "\r\n";
                $content .= "--" . $boundary . '--';

                // build headers
                $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
                $headers[] = 'Content-Length: ' . strlen($content);

                // set content
                $options[CURLOPT_POSTFIELDS] = $content;
            }
            else
            {
                // no file
                $options[CURLOPT_POSTFIELDS] = $parameters;
            }

            // enable post
            $options[CURLOPT_POST] = true;
        }
        else
        {
            // add parameters to the query string
            if(!empty($parameters))
            {
                $url .= '?' . $parameters;
            }
            $options[CURLOPT_POST] = false;
        }

        // set options
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_USERAGENT] = $this->getUserAgent();
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CONNECTTIMEOUT] = $this->getConnectionTimeOut();
        $options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        //$options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        $options[CURLOPT_HTTPHEADER] = array('Expect:');

        // init
        if($this->curl == null) $this->curl = curl_init();

        // set options
        curl_setopt_array($this->curl, $options);

        // execute
        $response = curl_exec($this->curl);
        $headers = curl_getinfo($this->curl);
        $this->httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->httpInfo = $headers;

        // fetch errors
        $errorNumber = curl_errno($this->curl);
        $errorMessage = curl_error($this->curl);

        if($this->httpCode != 200)
        {
            // should we provide debug information
            if (self::DEBUG) {
                // make it output proper
                echo '<pre>';

                // dump the header-information
                var_dump($headers);

                //dump the error code
                var_dump($errorNumber);

                // dump the error
                var_dump($errorMessage);

                // dump the raw response
                var_dump($response);

                // end proper format
                echo '</pre>';
            }

            $this->errors = json_decode($response);

            $response = false;
        }

        return $response;
    }

    public function api($url, $parameters = null, $method = 'GET', $file = null, $format = 'json')
    {
        $response = $this->oAuthRequest($url, $parameters, $method, $file, $format);
        $output = false;
        if($response)
        {
            if($format == 'json')
            {
                $output = $this->decodeJson($response);
            }
            elseif($format == 'xml')
            {
                $output = $this->decodeXml($response);
            }
        }
        return $output;
    }

    private function decodeJson($response)
    {
        // replace ids with their string values, added because of some
        // PHP-version can't handle these large values
        $response = preg_replace('/id":(\d+)/', 'id":"\1"', $response);

        // we expect JSON, so decode it
        $json = json_decode($response, true);

        // return
        return $json;
    }

    //todo: implement this (though, twitter doesn't support it anymore...)
    private function decodeXml($response)
    {
        print '<pre>';
        var_dump($response);
        print '</pre>';

        // return
        return false;
    }

    //getters
    public function getLastHttpCode()
    {
        return $this->httpCode;
    }

    public function getUserAgent()
    {
        return (empty($this->userAgent)) ? self::$defaultUserAgent : $this->userAgent;
    }

    private function getTimeOut()
    {
        return $this->timeOut;
    }

    private function getConnectionTimeOut()
    {
        return $this->connectionTimeOut;
    }
    //end getters

    //setters
    public function setUserAgent($userAgent)
    {
        if(is_string($userAgent))
        {
            $this->userAgent = $userAgent;
        }

        return $this;
    }
    //end setters

    //helpers
    private function buildUrl($url, $format)
    {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0)
        {
            $url = self::$apiUrl . $url;

            if(strrpos($url, '?') === false && !$this->endsWith($url, '.json') && !$this->endsWith($url, '.xml'))
            {
                $url = $url . '.' . $format;
            }
        }

        return $url;
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0)
        {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
