<?php

namespace Core;
use Exception;
use Core\HttpStatus;

class HttpRequest {
    static $lastRequestInfo;
    protected $curl;
    protected $response_headers = array();
    protected $cookies = array();
    protected $cookieString = '';
    
    public function __construct() {
        $curl = curl_init();
        /* Curl settings */
        curl_setopt($curl, CURLOPT_USERAGENT, SITE_NAME . ' | ' . HOST_NAME);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR, '/dev/null');
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        $this->curl = $curl;
    }
    protected function setHeaders($headers = array()) {
        // Filter blanks
        array_filter($headers);
        // Don't cache
        $headers[] = 'Cache-Control: no-cache, max-age=0';
        
        // A couple of request headers cause trouble with the Twitter API
        // It seems PHP curl just started setting them, so let's strip them
        // Content-Length: -1
        // $headers[] = 'Content-Length:'; // TODO disabled: this causes issues for POST requests, maybe needs special casing for GET on some servers
        // Expect: 100-Continue
        // http://matthom.com/archive/2008/12/29/php-curl-disable-100-continue-expectation
        // http://groups.google.com/group/twitter-development-talk/browse_thread/thread/7c67ff1a2407dee7
        $headers[] = 'Expect:';
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }
    
    protected function prepareGet($url, $requestParams = array()) {
        $url = Url::build($url, $requestParams);
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, false);
        return $url;
    }
    protected function preparePost($requestParams = array()) {
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, Url::encodePairsToString($requestParams));
    }
    protected function preparePut($requestParams = array()) {
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $requestParams);
    }
    protected function prepareDelete($requestParams = array()) {
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $requestParams);
    }
    public function send($url, $method = 'GET', $requestParams = array(), $headers = array()) {
        // Initialise curl 
        $this->setHeaders($headers);
        curl_setopt($this->curl, CURLOPT_COOKIE, $this->cookieString);
        $this->response_headers = array();
        // Build the request
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method); 
        switch ($method) {
        case 'POST':
            $this->preparePost($requestParams);
            break;
        case 'PUT':
            $this->preparePut($requestParams);
            break;
        case 'DELETE':
            $this->prepareDelete($requestParams);
            break;
        case 'GET':
            $url = $this->prepareGet($url, $requestParams);
            break;
        default:
            throw new HttpRequestException('NotImplemented', $method, $url, $requestParams, null, $this->response_headers, "The HttpRequest class doesn’t know how to make $method requests");
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        // Send request response
        $response = curl_exec($this->curl);
        // echo("$response\n==============\n");
        // echo("url: $url\nparams: " . Url::encodePairsToString($requestParams) . "\n==============\n");
        // echo(curl_getinfo($this->curl, CURLINFO_HEADER_OUT));
        $httpInfo = curl_getinfo($this->curl);
        $httpInfo['response_headers'] = $this->response_headers;
        self::$lastRequestInfo = $httpInfo;
        $httpCode = $httpInfo['http_code'];
        // Store cookies
        if (isset($this->response_headers['set_cookie'])) {
            $this->response_headers['set_cookie'] = (array) $this->response_headers['set_cookie'];
            foreach ($this->response_headers['set_cookie'] as $cookie) {
                $cookieParts = explode(';', $cookie);
                $cookieKV = explode('=', $cookieParts[0]);
                $this->cookies[$cookieKV[0]] = $cookieKV[1];
                $this->cookieString .= "{$cookieKV[0]}={$cookieKV[1]}; ";
            }
        }
        $httpInfo['cookies'] = $this->cookies;
        $httpInfo['cookieString'] = $this->cookieString;
        // Throw exception for errors
        if ($response === false) {
            // cURL error
            $curlError = "cURL error: " . curl_error($this->curl) . " (" . curl_errno($this->curl) . ")";
            throw new HttpRequestException(HttpStatus\Base::mapCodeToStatus(504), $method, $url, $requestParams, null, $this->response_headers, $curlError);
        }
        if ($httpCode !== 200) {
            $message = '';
            if (!$httpErrorClass = HttpStatus\Base::mapCodeToStatus($httpCode)) {
                $httpErrorClass = HttpStatus\Base::mapCodeToStatus(502); // BadGateway
                $message = "Unhandled HTTP Error: $httpCode";
            }
            throw new HttpRequestException($httpErrorClass, $method, $url, $requestParams, $response, $this->response_headers, $message);
        }
        // Close handle
        curl_close($this->curl);
        // Return response data
        return array($response, $httpInfo);
    }
    
    protected function getHeader($ch, $header) {
        // echo($header);
        $i = strpos($header, ':');
        if (!empty($i)) {
            $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
            $value = trim(substr($header, $i + 2));
            if (isset($this->response_headers[$key])) {
                $this->response_headers[$key] = (array) $this->response_headers[$key];
                $this->response_headers[$key][] = $value;
            } else {
                $this->response_headers[$key] = $value;
            }
        }
        return strlen($header);
    }
}

class HttpRequestException extends Exception {
    protected $httpError;
    protected $method;
    protected $url;
    protected $response;
    protected $responseHeaders;
    
    public function __construct ($httpError, $method, $url, $params, $response = '', $responseHeaders = array(), $message = null) {
        $this->method = $method;
        $this->url = $url;
        $this->params = $params;
        $this->response = $response;
        $this->responseHeaders = $responseHeaders;
        $this->httpError = new $httpError($message, $this);
        parent::__construct($message, $this->httpError->getCode());
    }
    
    public function __toString () {
        return get_called_class() . " {$this->httpError->getStatus()}: {$this->method} {$this->url}";
    }
    public function getHttpError () {
        return $this->httpError;
    }
    public function getResponse () {
        return $this->response;
    }
    public function getResponseHeaders () {
        return $this->responseHeaders;
    }
}
