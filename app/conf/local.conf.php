<?php

// There should be one of these files for each of systems you're running the site on.
// This is an example development config.
// The file MUST be named as the hostname of your system, followed by ".conf.php" on the end

//  Remove this line for production conf
define('DEV', true);

// While this is true, any emails the system sends (if it does send any) will instead
// go to a log file mail.log in the project root. Remove the line if you want emails to be
// sent instead.
define('DISABLE_EMAILS', true);

define('SITE_EMAIL', 'your@email.address');
define('HOST_NAME', 'your.own.hostname');

define('MYSQL_DB', 'example_db_name');
define('MYSQL_USER', "example_db_user");
define('MYSQL_PASSWORD', "example_db_password");

// You might need to change the path to your socket or comment this out and
// use the host and port settings depending on your mysql setup
define('MYSQL_SOCKET', '/tmp/mysql.sock');
// define('MYSQL_HOST', 'localhost');
// define('MYSQL_PORT', '3306');

define('MEMCACHE_HOST', 'localhost');
define('MEMCACHE_PORT', 11211);

define('TWITTER_KEY', 'example_twitter_api_key');
define('TWITTER_SECRET', 'example_twitter_api_secret');
