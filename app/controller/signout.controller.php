<?php

namespace App\Controller;
use Core\Controller;

class SignoutHtml extends Controller\Html {
    public function index () {
        throw $this->session->signOut();
    }
}
