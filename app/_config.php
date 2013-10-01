<?php

global $project;
$project = 'orientdb-app';

require_once("conf/ConfigureFromEnv.php");

switch ($_SERVER['HTTP_HOST']) {

	//Use SQLite3 database
	case 'sqlite.orientdb-app.local':
		global $databaseConfig;
		$databaseConfig = array(
			'type' => 'SQLiteDatabase',
			'server' => '',
			'username' => '',
			'password' => '',
			'database' => 'SS_orientdb-app',
			'path' => '/Users/fmullenger/Sites/orientdb-app/assets/.db',
		);
		break;

	//TODO: Use OrientDB databse
	case 'orient.orientdb-app.local':
		break;
	
	//Use MySQL database
	case 'localhost':
	case 'orientdb-app.local':
	case 'mysql.orientdb-app.local':
	default:
		break;
}

//OrientDB configuration for testing
global $databaseConfigOrient;
$databaseConfigOrient = array(
	'type' => 'OrientDatabase',
	'server' => 'localhost',
	'port' => 2424,
	'username' => 'admin',
	'password' => 'admin',
	'database' => 'SS_orientdb-app',
	'cluster' => '',
	'path' => '/Users/fmullenger/Scripts/orientdb-graphed-1.5.0/databases',
);

if(!Director::isDev()) {
	SS_Log::add_writer(
		new SS_LogEmailWriter(Email::getAdminEmail()), 
		SS_Log::NOTICE, 
		'<='
	);
}

if(isset($_REQUEST['flush'])) {
	SS_Cache::set_cache_lifetime('any', -1, 100);
}

//Local dev environment
if (Director::isDev() && defined('SS_DEV_EMAIL')) {
	
	//Error reporting
	ini_set('display_errors', 1);
	ini_set("log_errors", 1);
	error_reporting(E_ALL ^ E_NOTICE);
	
	//Logging
	SS_Log::add_writer(new SS_LogFileWriter(ASSETS_PATH . '/notices.log'));
	SS_Log::add_writer(new SS_LogEmailWriter(SS_DEV_EMAIL), SS_Log::ERR, '<=');
	
	//Emails
	//Email::send_all_emails_to(SS_DEV_EMAIL);
	//Email::setAdminEmail(SS_DEV_EMAIL);
	
	//Caching
	SSViewer::flush_template_cache();
	//$_GET['flush'] = 1;
}
