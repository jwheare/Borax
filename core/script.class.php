<?php

namespace Core;

abstract class Script {
    private $defaultOptions = array(
        'dry-run' => null,
    );
    private $argv = array();
    private $args = array();
    
    private $end;
    private $error;
    
    protected $dryRun;
    protected $options = array();
    
    public function __construct () {
        $this->setArgs();
        $this->dryRun = $this->argExists('dry-run');
        
        register_shutdown_function(array($this, 'shutdownHandler'));
    }
    
    public function shutdownHandler () {
        if (!$this->end) {
            if ($lastError = error_get_last()) {
                $this->error("Error {$lastError['type']}: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}");
            } else {
                $this->end();
            }
        }
    }
    protected function onEnd () {
        // overrite in subclass
    }
    protected function onError () {
        // overrite in subclass
    }
    
    public function argvExists ($key) {
        return array_key_exists($key, $this->argv);
    }
    public function argv ($key, $default = null) {
        return $this->argvExists($key) ? $this->argv[$key] : $default;
    }
    
    public function argExists ($key) {
        return array_key_exists($key, $this->args);
    }
    public function arg ($key, $default = null) {
        return $this->argExists($key) ? $this->args[$key] : $default;
    }
    private function setArg ($key, $value) {
        if ($this->argExists($key)) {
            $this->args[$key] = array_merge((array) $this->args[$key], (array) $value);
        } else {
            $this->args[$key] = $value;
        }
    }
    
    // Parse command line options to an array keyed to long option names
    // Takes an array of long -> short option name mappings
    private function setArgs () {
        $shortopts = '';
        $longopts = array();
        $longToShort = array_merge($this->defaultOptions, $this->options);
        $normLookup = array();
        foreach ($longToShort as $long => $short) {
            // Add lookup values for the normalised long name
            $normLong = str_replace(':', '', $long);
            $normShort = str_replace(':', '', $short);
            $normLookup[$normLong] = $normLong;
            $normLookup[$normShort] = $normLong;
            // Build getopt arguments
            $longopts[] = $long;
            if ($short) {
                // Add short option to long option list too for more flexible argument passing
                $longopts[] = $short;
                $shortopts .= $short;
            }
        }
        // Parse options
        $opts = getopt($shortopts, $longopts);
        // Set args
        $args = array();
        foreach ($opts as $key => $val) {
            $this->setArg($normLookup[$key], $val);
        }
        // Set Argv
        $this->argv = $_SERVER["argv"];
        foreach ($this->argv as $arg) {
            if (strpos($arg, '-') === 0) {
                if (!array_key_exists(ltrim($arg, '-'), $normLookup)) {
                    $this->error("Invalid argument: $arg");
                }
            }
        }
    }
    abstract public function run();
    
    static function getScriptPath ($name) {
        return realpath(SCRIPT_DIR . "/$name.php");
    }
    protected function out ($string) {
        echo $string;
    }
    protected function error ($string = null, $status = 1) {
        $this->end = true;
        $this->error = true;
        $this->onError();
        if ($string) {
            error_log($string);
        }
        exit($status);
    }
    protected function end () {
        $this->end = true;
        $this->onEnd();
        exit(0);
    }
}
