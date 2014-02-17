<?php
require_once 'config.php';
require_once 'lib/t_lib.php';
require_once 'lib/ExternalWeather.php';
require_once 'lib/KLogger.php';

// In order to move the pChart library to an external location I found I had to modify the includepath
function add_include_path( $path )
{
	foreach( func_get_args() AS $path )
    {
		if( !file_exists( $path ) OR ( file_exists( $path ) && filetype( $path ) !== 'dir' ) )
        {
			trigger_error( "Include path '{$path}' not exists", E_USER_WARNING );
            continue;
        }

		$paths = explode( PATH_SEPARATOR, get_include_path() );

		if( array_search( $path, $paths ) === false )
		{
			array_push( $paths, $path );
		}

		set_include_path( implode( PATH_SEPARATOR, $paths ) );
    }
}

global $timezone;

// Set timezone for all PHP functions
date_default_timezone_set( $timezone );

// Always connect to the database, don't wait for a request to connect
//$pdo = new PDO( $dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'] );
$pdo = new PDO( "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']}", $dbConfig['username'], $dbConfig['password'] );
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// Set timezone for all MySQL functions
$pdo->exec( "SET time_zone = '$timezone'" );


$log = KLogger::instance( $logDir );

// Wrapper function so I can update the other procs later....
function logIt( $msg )
{
	global $log;

	$log->logInfo( 'WRAPPER: ' . $msg );
}

// Common code that should run for EVERY page follows here

// Get list of thermostats
// Move this to after user login in future and get only stats for the selected user
try
{
	$thermostats = array();
	$sql = "SELECT * FROM {$dbConfig['table_prefix']}thermostats ORDER BY name asc";
	foreach( $pdo->query($sql) as $row )
	{
			$thermostats[] = $row;
	}
}
catch( Exception $e )
{
	$log->logFatal( 'Error getting thermostat list' );
}
?>