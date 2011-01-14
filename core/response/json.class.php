<?php

namespace Core\Response;
use Core\Request;

class Json extends Base {
    protected $_data;
    public function __construct (Request $request) {
        parent::__construct($request);
        $this->setHeader('Content-type', 'application/json; charset=utf-8');
    }
    public function __get ($name) {
        return $this->getData($name);
    }
    public function __set ($name, $value) {
        $this->setData($name, $value);
    }
    public function getData ($key) {
        return $this->_data[$key];
    }
    public function setData ($name, $value) {
        $this->_data[$name] = $value;
    }
    public function getBody () {
        return json_encode($this->_data);
    }
}
