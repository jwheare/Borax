<?php

// There should be one of these files for each of systems you're running the site on.
// This is an example development config.
// The file MUST be named as the hostname of your system, followed by ".conf.php" on the end

define('DEV', true);
define('DISABLE_EMAILS', true);

define('HOST_NAME', 'a.dev.example.local');

define('MYSQL_DB', 'example_db_name');
define('MYSQL_SOCKET', '/tmp/mysql.sock');
define('MYSQL_USER', "example_db_user");
define('MYSQL_PASSWORD', "example_db_password");

define('MEMCACHE_HOST', 'localhost');
define('MEMCACHE_PORT', 11211);

define('TWITTER_KEY', 'example_twitter_api_key');
define('TWITTER_SECRET', 'example_twitter_api_secret');
