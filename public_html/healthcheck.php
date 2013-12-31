<?php
	define('INCLUDE_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' ) . DIRECTORY_SEPARATOR );
	include_once(INCLUDE_PATH . '/conf/config.php');


	$healthcheck = new healthcheck($dsn, $config);
	echo($healthcheck->check());

