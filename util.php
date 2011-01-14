<?php

use Core\ServiceManager;
use Core\Dump;

function dump($var, $highlight = false) {
    Dump::var_dump($var, $highlight);
}

function safe($value) {
    // Recurse through arrays
    if (is_array($value)) {
        return array_map('safe', $value);
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function out($string) {
    echo safe($string);
}
function jsout($output) {
    echo json_encode($output);
}
function service($serviceName) {
    $manager = new ServiceManager();
    return $manager->get($serviceName);
}

class LinkifyCallback {
    public $style;
    
    public function handle ($matches) {
        $url = preg_replace("/^https?:\/\//i", '', $matches[2]);
        $url = preg_replace("/^www\./i", '', $url);
        $url = preg_replace("/\/$/", '', $url);
        $fullUrl = $matches[2];
        if (strtolower($matches[3]) == 'www.') {
            $fullUrl = "http://$fullUrl";
        }
        $style = '';
        if ($this->style) {
            $style = ' style="' . $this->style . '"';
        }
        return $matches[1] . '<a href="' . $fullUrl . '" title="' . $fullUrl . '"' . $style . '>' . truncate($url, 50) . '</a>';
    }
}
define('AUTOLINK_REGEX', "/(^|[\s(:']+)(((?:https?:\/\/)|(?:www\.))[^.-]+?\S+[^\.\s?!,:;\]}=')+])/i");
function linkify($string, $style = '') {
    $linkifyCallback = new LinkifyCallback();
    $linkifyCallback->style = $style;
    return preg_replace_callback(
        AUTOLINK_REGEX,
        array($linkifyCallback, 'handle'),
        $string
    );
}

// Parse command line options to an array keyed to long option names
// Takes an array of long -> short option name mappings
function getopts ($options) {
    $shortopts = '';
    $longopts = array();
    foreach ($options as $long => $short) {
        $longopts[] = "$long:";
        if ($short) {
            $longopts[] = "$short:";
            $shortopts .= "$short:";
        }
    }
    return getopt($shortopts, $longopts);
}
