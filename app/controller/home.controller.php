<?php

namespace App\Controller;
use Core\Controller;

class HomeHtml extends Controller\Html {
    public function index () {
        return $this->response;
    }
}
