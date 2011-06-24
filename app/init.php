<?php

require_once('db.service.php');

$memcacheClientType = class_exists('Memcache') ? "memcache" : (class_exists('Memcached') ? "memcached" : FALSE);
if ($memcacheClientType) {
    require_once($memcacheClientType.'.service.php');
}