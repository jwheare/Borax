<?php

namespace App\Script;
use Core;
use App\Model;
use Core\Twitter, Core\TwitterException;

class TwitterInfo extends Core\Script {
    protected $options = array(
        'token:'  => 't:',
        'secret:' => 's:',
        'auth:' => 'a:',
    );
    
    public function run () {
        // Get screen names from positional arguments
        $screenNames = $this->getArgv();
        if (empty($screenNames)) {
            $this->error("Missing screen names");
        }
        
        $twitter = new Twitter();
        
        // Auth calls if given a token and secret as keyword arguments
        if ($authUserName = $this->arg('auth')) {
            $authUser = new Model\Person;
            if (!$authUser->loadByTwitter_Name($authUserName)) {
                $this->error("No user: $authUserName");
            }
            $twitter = $authUser->twitter();
        } else {
            $token  = $this->arg('token');
            $secret  = $this->arg('secret');
            if ($token && $secret) {
                $twitter->setCredentials($token, $secret);
            }
        }
        
        foreach ($screenNames as $name) {
            try {
                $userInfo = $twitter->getProfileInfoFromName($name);
            } catch (TwitterException $e) {
                $this->warn("Error fetching info for $name\n\n{$e->debugString()}");
            }
            
            $this->out("\n");
            $this->tabulate($userInfo, $userInfo->screen_name);
        }
        
        $this->end();
    }
    private function tabulate ($object, $heading = null, $prefix = '') {
        $props = get_object_vars($object);
        $maxKeyLength = max(array_map('strlen', array_keys($props)));
        
        if ($heading) {
            $this->out("$heading\n" . str_repeat('=', $maxKeyLength + strlen($prefix) + 2) . "\n");
        }
        foreach ($props as $k => $v) {
            if (is_object($v)) {
                $this->tabulate($v, $k, '- ');
            } else {
                $this->out(sprintf("%s%-{$maxKeyLength}s | %s\n", $prefix, $k, $v));
            }
        }
    }
}
