<?php

namespace App;
use App\Model;
use Core\Url;
use Core\Request;
use Core\Response;
use Core\HttpStatus;

class Session extends \Core\Session {
    const COOKIE = 'session';
    
    public function __construct (Request $request, &$session = null) {
        parent::__construct($request, $session);
        
        // Set backto from querystring
        if ($backto = $this->request->get('backto')) {
            $this->set('backto', $backto);
        }
        
        // Attempt to load a Person object from the session cookie
        if ($accessToken = $this->request->cookie(self::COOKIE)) {
            $person = new Model\Person();
            if ($person->loadByAccess_Token($accessToken)) {
                // Cache the logged in user object
                $this->user = $person;
            }
        }
    }
    
    public function getTwitterUser() {
        $authedTwitterUserId = $this->get('twitter_id');
        if ($this->isLoggedIn() && $this->getUser()->twitter_id) {
            $authedTwitterUserId = $this->getUser()->twitter_id;
        }
        return $authedTwitterUserId;
    }
    public function isTwitterConnected () {
        return (bool) $this->getTwitterUser();
    }
    public function isAdmin() {
        return $this->isLoggedIn() && $this->getUser()->name === 'jwheare';
    }
    public function message($message) {
        $this->set('message', $message);
    }
    public function getMessage() {
        return $this->delete('message');
    }
    public function signIn($person, $next = null) {
        // Make sure the user has an access token
        $person->setAccessToken();
        $person->save();
        // Set cookie
        Response\Base::setCookie(self::COOKIE, $person->access_token, 60*60*24*365*10);
        return $this->finalRedirect($next);
    }
    public function signOut($next = null) {
        // Delete cookie
        Response\Base::deleteCookie(self::COOKIE);
        $redirect = $this->finalRedirect();
        return $redirect;
    }
    public function finalRedirect($location = null) {
        if (!$location) {
            if ($next = $this->request->post('next', $this->request->get('next'))) {
                $location = $next;
            } else {
                $location = $this->get('backto');
                $this->delete('backto');
            }
        }
        $redirect = new HttpStatus\Found($location);
        return $redirect;
    }
    public function backToHere($url, $extraParams = array()) {
        // Add $extraParams to current URL
        $backtoUrl = Url::mergeQueryParams($this->request->getUrl(), $extraParams);
        
        // Add merged current URL as backto param to $url
        return Url::mergeQueryParams($url, array(
            'backto' => $backtoUrl,
        ));
    }
    public function addNext($url, $next) {
        return Url::build($url, array(
            'next' => $next,
        ));
    }
    public function formToken () {
        if ($this->isLoggedIn()) {
            return '<input type="hidden" name="accessToken" value="' . safe($this->getUser()->access_token) . '">';
        } else {
            return '';
        }
    }
    public function validateAccessToken () {
        $method = $this->request->getMethod();
        $accessToken = $this->request->$method('accessToken');
        if (!$this->isLoggedIn()) {
            throw new HttpStatus\Unauthorized('Not logged in');
        }
        if (!$this->getUser()->checkAccessToken($accessToken)) {
            throw new HttpStatus\Unauthorized('Missing access token. The access token is a security measure to protect against Cross-Site Request Forgery attacks. More info: http://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)');
        }
    }
}
