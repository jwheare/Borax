<?php

namespace App\Service;
use Core\ServiceManager;
use Memcached;

$services = new ServiceManager();
$services->register('memcached', new Memcached());
$services->get('memcached')->addServer(MEMCACHE_HOST, MEMCACHE_PORT);
