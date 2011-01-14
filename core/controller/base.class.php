<?php

namespace Core\Controller;
use Core\Request;
use Core\Response;

function getClasses () {
    return array(
        "text/html" => array(
            "Html" => "GET",
            "Form" => "POST",
        ),
        "application/json" => array(
            "JsonGet" => "GET",
            "JsonPost" => "POST",
            "JsonPut" => "PUT",
            "JsonDelete" => "DELETE",
        ),
    );
}

abstract class Base {
    public $request;
    public $response;
    protected $_name;
    protected $_action;
    protected $_args;
    public function __construct (Request $request, $name, $action, array $args, Response\Base $response) {
        $this->request = $request;
        $this->_name = $name;
        $this->_action = $action;
        $this->_args = $args;
        $this->response = $response;
    }
    public function __get ($name) {
        return $this->response->$name;
    }
    public function __set ($name, $value) {
        $this->response->$name = $value;
    }
    public function arg ($key, $default = null) {
        return array_key_exists($key, $this->_args) ? $this->_args[$key] : $default;
    }
    public function generateResponse () {
        return $this->{$this->_action}();
    }
}
