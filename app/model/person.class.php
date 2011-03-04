<?php

namespace App\Model;
use Core\Model;
use Core\Session;
use Core\Twitter;

class Person extends Model {
    var $table = 'person';
    protected $columns = array(
        'twitter_id',
        'twitter_name',
        'twitter_access_token',
        'twitter_access_token_secret',
        'access_token',
        'poster',
    );
    protected $getByColumns = array('twitter_id');
    protected $relations = array(
        'poster' => array('person', 'id'),
    );
    
    public function getTwitterUrl() {
        return "http://twitter.com/{$this->twitter_name}";
    }
    public function twitter () {
        $twitter = new Twitter($this->twitter_access_token, $this->twitter_access_token_secret);
        return $twitter;
    }
    
    protected function createAccessToken() {
        return sha1(uniqid(mt_rand(), true));
    }
    public function setAccessToken() {
        if (!$this->access_token) {
            $this->access_token = $this->createAccessToken();
            $this->save();
        }
    }
    public function checkAccessToken($accessToken) {
        return $this->access_token == $accessToken;
    }
    public function setTwitterCredentials($token, $secret) {
        $this->twitter_access_token = $token;
        $this->twitter_access_token_secret = $secret;
    }
    
    public function disconnectTwitter() {
        $this->setTwitterCredentials(null, null);
        $this->twitter_id = null;
        $this->twitter_name = null;
        $this->save();
        return true;
    }
    public function isSessionUser(Session $session) {
        return $this->equals($session->getUser());
    }
}
