<?php

namespace Core\Controller;
use Core\Request;

abstract class Shared {
    protected $_controller;
    protected $_action;
    public function __construct (Base $controller, $name, $action) {
        $this->_controller = $controller;
        $this->_name = $name;
        $this->_action = $action;
    }
    public function __get ($name) {
        return $this->_controller->$name;
    }
    public function __set ($name, $value) {
        $this->_controller->$name = $value;
    }
    public function __call ($method, $args) {
        return call_user_func_array(array($this->_controller, $method), $args);
    }
    public function setUp () {
    }
    public function processSetUp () {
        $this->setUp();
        if (is_callable(array($this, $this->_action))) {
            $this->{$this->_action}();
        }
    }
}