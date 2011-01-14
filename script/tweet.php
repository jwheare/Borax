#!/usr/bin/env php
<?php

$_SERVER['ROOT_DIR'] = realpath(dirname(__FILE__) . '/..');
require_once($_SERVER['ROOT_DIR'] . '/init.php');

$options = getopts(array(
    'user' => 'u',
    'tweet' => 't',
    'reply' => 'r',
    'place' => 'p',
    'lat' => null,
    'lon' => null,
));

$userId  = null;
$tweet   = null;
$reply   = null;
$placeId = null;
$lat     = null;
$lon     = null;

foreach ($options as $opt => $val) switch ($opt) {
    case 'u':
    case 'user':
        $userId = $val;
        break;
    case 't':
    case 'tweet':
        $tweet = $val;
        break;
    case 'r':
    case 'reply':
        $reply = $val;
        break;
    case 'p':
    case 'place':
        $placeId = $val;
        break;
    case 'lat':
        $lat = $val;
        break;
    case 'lon':
        $lon = $val;
        break;
}

if (!$userId) {
    die("Missing user id\n");
}
if (!$tweet) {
    die("Missing status\n");
}

$person = new App\Model\Person();
if ($person->loadByTwitter_Id($userId)) {
    $t = new Core\Twitter();
    try {
        $t->updateStatus($person, $tweet, $reply, $placeId, $lat, $lon);
    } catch (Core\TwitterException $e) {
        
    }
}
