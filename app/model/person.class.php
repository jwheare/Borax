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
    protected $getByColumns = array('twitter_id', 'name');
    protected $relations = array(
        'poster' => array('person', 'id'),
    );
    
    public function getTwitterUrl() {
        return "http://twitter.com/{$this->twitter_name}";
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
    public function setSetting($key, $value) {
        $kv = new PersonKeyValue(array(
            'person' => $this->id,
            'key' => $key,
        ));
        $kv->load();
        $kv->value = $value;
        $kv->save();
        return $kv;
    }
    public function getSetting($key) {
        $kv = new PersonKeyValue(array(
            'person' => $this->id,
            'key' => $key,
        ));
        if ($kv->load()) {
            return $kv->getValue();
        }
        return null;
    }
}
