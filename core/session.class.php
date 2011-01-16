<?php

namespace Core;

class Session {
    private $session;
    protected $request;
    protected $user = null;
    const SESS_KEY = "session_var";
    
    public function __construct(Request $request, &$session = false) {
        $this->request = $request;
        // We need a reference to the global $_SESSION object so it can store state correctly
        // Assigning by reference doesn't work with ternary operators
        if ($session) {
            $this->session =& $session;
        } else {
            $this->session =& $_SESSION;
        }
        
        $this->setCacheHeaders();
        
        session_name(self::SESS_KEY);
    }
    protected function setCacheHeaders () {
        if ($this->isLoggedIn()) {
            $this->nocache();
        } else {
            $this->cache();
        }
    }
    protected function startSession () {
        if (!$this->isStarted()) {
            if ($this->session === false) {
                $this->session = array();
            } else {
                session_start();
                $this->session =& $_SESSION;
                // Set the headers again cos session_start() overrides them with session_cache_limiter()
                $this->nocache();
            }
        }
    }
    protected function endSession () {
        $expires = time() - 60*60*24*365;
        $params = session_get_cookie_params();
        // TODO use response class
        setcookie(session_name(), '', $expires, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        if ($this->isStarted()) {
            session_destroy();
        }
    }
    public function isStarted () {
        return is_array($this->session);
    }
    // TODO use request class
    public function hasCookie () {
        return isset($_COOKIE[session_name()]);
    }
    public function set ($key, $value) {
        $this->startSession();
        $this->session[$key] = $value;
    }
    public function get ($key, $default = false) {
        $this->startSession();
        return array_key_exists($key, $this->session) ? $this->session[$key] : $default;
    }
    public function delete ($key) {
        $this->startSession();
        $value = $this->get($key);
        $this->set($key, null);
        unset($this->session[$key]);
        return $value;
    }
    public function nocache () {
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() - 60*60*24*365) . ' GMT');
        header('Cache-control: no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
    public function cache () {
        $expires = 60*60*24*30;
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $expires) . ' GMT');
        header('Cache-control: public, max-age=' . $expires);
        header('Vary: cookie');
    }
    public function getUser() {
        return $this->user;
    }
    public function isLoggedIn() {
        return (bool) $this->getUser();
    }
}
