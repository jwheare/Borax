<?php

namespace Core\Response;
use Core\Request;
use Core\HttpStatus;
use Core\Dump;

abstract class Base {
    protected $_headers = array();
    protected $_statusCode = 200;
    protected $_statusText = 'OK';
    public $request;
    public $session;
    public $user;
    protected $exception;
    public function __construct (Request $request) {
        $this->request = $request;
        $this->session = $this->request->getSession();
        $this->user = $this->session->getUser();
    }
    public function getStatus () {
        return "{$this->_statusCode} {$this->_statusText}";
    }
    public function setStatus($statusCode, $statusText) {
        $this->_statusCode = $statusCode;
        $this->_statusText = $statusText;
    }
    public function setException (HttpStatus\BaseError $exception) {
        $this->exception = $exception;
        $this->setStatus($this->exception->getCode(), $this->exception->getText());
    }
    public function setHeader ($name, $value) {
        $this->_headers[$name] = $value;
    }
    public function setHeaders ($headers) {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }
    public static function setCookie ($key, $value, $expires) {
        setcookie($key, $value, time() + $expires, '/', "." . HOST_NAME);
    }
    public static function deleteCookie ($key) {
        self::setCookie($key, '', -60*60*24);
    }
    public function getBody () {
        return '';
    }
    public function beforeRespond () {
        
    }
    public function respond () {
        $this->beforeRespond();
        header("HTTP/1.1 {$this->getStatus()}");
        foreach ($this->_headers as $name => $value) {
            header("$name: $value");
        }
        Dump::flush();
        echo $this->getBody();
    }
}
