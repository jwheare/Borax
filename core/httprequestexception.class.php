<?php

namespace Core;
use \Exception;
use HttpStatus;

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
