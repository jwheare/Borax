<?php

namespace Core;
use Exception;

class Twitter {
    
    const KEY = TWITTER_KEY;
    const SECRET = TWITTER_SECRET;
    
    const ROOT_URL = 'http://twitter.com/';
    const API_ROOT_URL = 'https://api.twitter.com/';
    const API_VERSION = 1;
    
    var $token = null;
    var $secret = null;
    
    var $authCalls = true;
    
    public function __construct($token = null, $secret = null) {
        if ($token && $secret) {
            $this->setCredentials($token, $secret);
        }
    }
    /**
     * API method helpers
    **/
    protected function callUrl($url, $method, $callParams = array()) {
        $headers = array(
            // http://groups.google.com/group/twitter-development-talk/browse_thread/thread/3c859b7774b1e95d
            "X-Twitter-Content-Type-Accept: application/x-www-form-urlencoded",
        );
        if ($this->authCalls) {
            $headers[] = $this->buildAuthorizationHeader($url, $method, $callParams);
        }
        try {
            $request = new HttpRequest();
            list($response, $httpInfo) = $request->send($url, $method, $callParams, $headers);
        } catch (HTTPRequestException $e) {
            switch ($e->getCode()) {
            case 400:
                $message = "Invalid Twitter request";
                break;
            case 401:
                $message = "Unauthorised Twitter request";
                break;
            case 403:
                $message = "Twitter refused this request";
                break;
            case 413:
                $message = "Twitter request too large";
                break;
            case 500:
                $message = 'Twitter’s having server troubles. Check their status: http://status.twitter.com/</a>';
                break;
            case 502:
                $message = "Twitter’s down or being upgraded. Try again later";
                break;
            case 503:
                $message = "Twitter’s temporarily overloaded. Try again";
                break;
            default:
                $message = "Twitter request failed";
            }
            if ($eMessage = $e->getMessage()) {
                $message .= " - $eMessage";
            }
            throw new TwitterException($message, $e->getCode(), $method, $url, $callParams, $headers, $e->getResponse(), $e->getResponseHeaders(), $e->getHttpError());
        }
        return $response;
    }
    protected function buildUrl($path, $api = true) {
        if ($api) {
            $baseUrl = self::API_ROOT_URL . self::API_VERSION . '/';
        } else {
            $baseUrl = self::ROOT_URL;
        }
        return $baseUrl . $path;
    }
    protected function parseBodyString($body) {
        $params = array();
        parse_str($body, $params);
        return $params;
    }
    
    public function setCredentials($token, $secret) {
        $this->token = $token;
        $this->secret = $secret;
    }
    public function setCredentialsFromPerson($person) {
        $this->setCredentials($person->twitter_access_token, $person->twitter_access_token_secret);
    }
    
    /**
     * Public API methods
    **/
    public function get($url, $params = array()) {
        $response = $this->callUrl($this->buildUrl($url), 'GET', $params);
        return json_decode($response);
    }
    public function post($url, $params = array()) {
        $response = $this->callUrl($this->buildUrl($url), 'POST', $params);
        return json_decode($response);
    }
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-users%C2%A0show
    public function getProfileInfo($person) {
        return $this->get('users/show.json', array(
            'user_id' => $person->twitter_id,
        ));
    }
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-followers%C2%A0ids
    public function getFollowers($person, $cursor = '-1') {
        return $this->get('followers/ids.json', array(
            'user_id' => $person->twitter_id,
            'cursor' => $cursor,
        ));
    }
    public function updateStatus($person, $status, $reply = null, $place = null, $lat = null, $lon = null) {
        $this->setCredentialsFromPerson($person);
        $params = array(
            'status' => $status,
        );
        if ($reply) {
            $params['in_reply_to_status_id'] = $reply;
        }
        if ($place) {
            $params['place_id'] = $place;
        } else if ($lat && $lon) {
            $params['lat'] = $lat;
            $params['long'] = $lon;
        }
        return $this->post('statuses/update.json', $params);
    }
    public function retweet($person, $id) {
        $this->setCredentialsFromPerson($person);
        return $this->post("statuses/retweet/$id.json");
    }
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-friends%C2%A0ids
    public function getFriends($person, $cursor = '-1') {
        return $this->get('friends/ids.json', array(
            'user_id' => $person->twitter_id,
            'cursor' => $cursor,
        ));
    }
    // oAuth Step 1 - Get request token
    // http://oauth.net/core/1.0a/#auth_step1
    // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-request_token
    public function getRequestToken(Request $request, $callback = null) {
        $url = $this->buildUrl('oauth/request_token', false);
        $method = 'GET';
        $params = array();
        $requiredParams = array(
            'oauth_token',
            'oauth_token_secret',
        );
        if ($callback) {
            $params['oauth_callback'] = Url::addHost($callback);
            $requiredParams[] = 'oauth_callback_confirmed';
        }
        $response = $this->callUrl($url, $method, $params);
        $requestTokenParams = $this->parseBodyString($response);
        if (empty($requestTokenParams)) {
            throw new TwitterException('Missing parameters in Twitter response', 502, $method, $url, null, null, $response);
        }
        foreach ($requiredParams as $param) {
            if (!isset($requestTokenParams[$param])) {
                throw new TwitterException("Missing $param in Twitter response", 502, $method, $url, null, null, $response);
            }
        }
        return $requestTokenParams;
    }
    
    // oAuth Step 2 - Redirect to auth URL
    // http://oauth.net/core/1.0a/#auth_step2
    public function getAuthorizationUrl($forceLogin = false) {
        // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-authenticate
        $params = array(
            'oauth_token' => $this->token,
        );
        if ($forceLogin) {
            $params['force_login'] = 'true';
        }
        $url = Url::build($this->buildUrl('oauth/authenticate', false), $params);
        return $url;
    }
    
    // oAuth Step 3 - Exchange request token stored in the session for an oAuth token and secret.
    // http://oauth.net/core/1.0a/#auth_step3
    public function getAccessToken($authTokenVerifier) {
        // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-access_token
        $url = $this->buildUrl('oauth/access_token', false);
        $method = 'POST';
        $response = $this->callUrl($url, $method, array(
            'oauth_verifier' => $authTokenVerifier,
        ));
        $accessTokenParams = $this->parseBodyString($response);
        if (empty($accessTokenParams)) {
            throw new TwitterException('Missing parameters in Twitter response', 400, $method, $url, null, null, $response);
        }
        return $accessTokenParams;
    }
    
    public function verifyCredentials() {
        // http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-account%C2%A0verify_credentials
        return $this->get('account/verify_credentials.json');
    }
    
    /**
     * Auth params
    **/
    
    // http://oauth.net/core/1.0a/#auth_header
    protected function buildAuthorizationHeader($url, $method, $callParams = array()) {
        // Merge in extra params
        $authParams = $this->getOAuthParams();
        // Sign
        $signature = $this->generateSignature(array_merge($authParams, $callParams), $url, $method);
        $authParams['oauth_signature'] = $signature;
        // Encode pairs
        $encodedPairs = Url::encodePairs($authParams, true);
        // Write the header
        $header = 'Authorization: OAuth realm="' . $url . '", '
               . implode(', ', $encodedPairs);
        return $header;
    }
    // Common OAuth params needed for every request
    protected function getOAuthParams() {
        $params = array(
            'oauth_consumer_key'     => self::KEY,
            'oauth_signature_method' => "HMAC-SHA1",
            'oauth_timestamp'        => time(),
            'oauth_nonce'            => md5(uniqid(mt_rand(), true)),
            'oauth_version'          => "1.0",
            'oauth_token'            => $this->token,
        );
        return $params;
    }
    
    /**
     * Signing
     * http://oauth.net/core/1.0a/#signing_process
    **/
    protected function generateSignature($params, $url, $method) {
        // Base string = [METHOD][url][normalised pairs]
        $baseParts = array(
            Url::encodeRFC3986(strtoupper($method)),
            Url::encodeRFC3986($url),
            Url::encodeRFC3986(Url::normaliseParams($params)),
        );
        $baseString = implode('&', $baseParts);
        // Sign with HMAC-SHA1
        $key = $this->buildSigningKey();
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));
        // echo("baseString: $baseString\nkey: $key\nsignature: $signature\n==============\n");
        return $signature;
    }
    // Uses a saved request/access token secret if there is one
    protected function buildSigningKey() {
        $key = Url::encodeRFC3986(self::SECRET) . '&' . Url::encodeRFC3986($this->secret);
        return $key;
    }
}

class TwitterException extends Exception {
    var $method;
    var $url;
    var $params;
    var $headers;
    var $response;
    var $responseHeaders;
    var $previous;
    public function __construct($message, $code = 0, $method = '', $url = '', $params = array(), $headers = array(), $response = '', $responseHeaders = array(), Exception $previous = null) {
        $this->method = $method;
        $this->url = $url;
        $this->params = $params;
        $this->headers = $headers;
        $this->response = $response;
        $this->responseHeaders = $responseHeaders;
        parent::__construct($message, $code, $previous);
    }
    
    public function __toString() {
        return __CLASS__ . " {$this->getStatusLine()}: {$this->method} {$this->url}";
    }
    public function getStatusLine() {
        return "{$this->getCode()} {$this->getMessage()}";
    }
    // TODO fix for new framework
    public function handle() {
        $status = $this->previous ? $this->previous->getStatusLine() : $this->getStatusLine();
        if (acceptJson()) {
            errorJson($status, array(
                'error' => $this->getMessage(),
                'code' => $this->getCode(),
                'method' => $this->method,
                'url' => $this->url,
                'headers' => $this->headers,
                'params' => $this->params,
                'response' => $this->response,
                'responseHeaders' => $this->responseHeaders,
            ));
        } else {
            // User friendly info
            $errorHeading = "Twitter sign in error";
            $errorMessage = "<p>{$this->getMessage()}</p>";
            // Debug info
            $errorMessage .= '<div class="debug">';
            $errorMessage .= '<h2>Debug info</h2>';
            $errorMessage .= "<p>({$this->getCode()}) {$this->method} {$this->url}</p>";
            
            if (!empty($this->headers)) {
                $errorMessage .= '<h2>Request headers</h2>';
                $errorMessage .= '<pre>' . safe(implode("\n", $this->headers)) . '</pre>';
            }
            
            if (!empty($this->params)) {
                $errorMessage .= '<h2>Request parameters</h2>';
                $errorMessage .= '<pre>' . safe(print_r(Url::encodePairs($this->params), true)) . '</pre>';
            }
            
            if ($this->response) {
                $errorMessage .= '<h2>HTTP response body</h2>';
                $errorMessage .= '<pre>' . safe($this->response) . '</pre>';
            }
            if (!empty($this->responseHeaders)) {
                $errorMessage .= '<h2>HTTP response headers</h2>';
                $errorMessage .= '<pre>' . safe(print_r(Url::encodePairs($this->responseHeaders), true)) . '</pre>';
            }
            $errorMessage .= '</div>';
            error($status, $errorHeading, $errorMessage);
        }
    }
}
