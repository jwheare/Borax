<?php

namespace Core;

abstract class Script {
    private $defaultOptions = array(
        'dry-run' => null,
    );
    private $argv = array();
    private $args = array();
    
    protected $dryRun;
    protected $options = array();
    
    public function __construct () {
        $this->setArgs();
        $this->dryRun = array_key_exists('dry-run', $this->args);
    }
    
    public function argv ($key, $default = null) {
        return array_key_exists($key, $this->argv) ? $this->argv[$key] : $default;
    }
    
    public function arg ($key, $default = null) {
        return array_key_exists($key, $this->args) ? $this->args[$key] : $default;
    }
    private function setArg ($key, $value) {
        if (array_key_exists($key, $this->args)) {
            $this->args[$key] = array_merge((array) $this->args[$key], (array) $value);
        } else {
            $this->args[$key] = $value;
        }
    }
    
    // Parse command line options to an array keyed to long option names
    // Takes an array of long -> short option name mappings
    private function setArgs () {
        $this->argv = $_SERVER["argv"];
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
    }
    abstract public function run();
    
    static function getScriptPath ($name) {
        return realpath(SCRIPT_DIR . "/$name.php");
    }
    protected function out ($string) {
        echo $string;
    }
    protected function error ($string = null) {
        die("$string\n");
    }
    protected function end () {
        die();
    }
    protected function runJobAt ($job, $time) {
        // Build at schedule
        $atCmd = "at -t " . escapeshellarg(date("YmdHi.s", $time));
        $this->out("[" . date("H:m:s", $time) . "] $atCmd -> $job\n");
        
        if ($this->dryRun) {
            return;
        }
        
        $atProc = popen($atCmd, 'w');
        if (!is_resource($atProc)) {
            return false;
        }
        fwrite($atProc, "$job > /dev/null 2>&1");
        pclose($atProc);
    }
}
