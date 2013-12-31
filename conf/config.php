<?php

/* This is the database configuration */
$dsn = array(
		'user' => 'healthcheck',
		'pass' => 'ldkfj08972r3jhkwef',
		'name' => 'healthcheck',
		'type' => 'mysql',
		'host' => '127.0.0.1',
		'port' => '3306',
	);

/* This is the application configuration */
$config = array('token' => sha1('This is my Random String for Securing my HEALTHCHECK from random people'), 'file_monitor' => '/var/www/healthcheck/MAINTENANCE', 'sql_slave' => true);
$config = array('file_monitor' => '/var/www/healthcheck/MAINTENANCE', 'sql_slave' => true);
$config = array('file_monitor' => '/var/www/healthcheck/MAINTENANCE');


include_once(INCLUDE_PATH . '/app/healthcheck.php');
