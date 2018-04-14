<?php
	set_time_limit(0);
	error_reporting(E_ALL);
	ini_set('memory_limit', "1024M");
	date_default_timezone_set('UTC');
	
	define('DB_HOST', "");
	define('DB_USER', "");
	define('DB_PASSWORD', "");
	define('DB_NAME', "");
	
	require_once(__DIR__ ."/vendor/autoload.php");
	
	require_once(__DIR__ ."/lib/database.mysql.pdo.php");
	require_once(__DIR__ ."/lib/process.php");