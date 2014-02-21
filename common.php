<?php
/** Location for code that is common to all pages in the project.
	*
	* Performs database connection and log in based on credentials stored in config.php
	*/


/** For libraries that are not part of my code base, I have a common locaiton on my webserver so that all projects can use one instance of the library.
	*
	* In order to be able to reference those files without hard coded path names, the PHP include path needs to know about the relative location of
	*  those libraries.
	*/
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
add_include_path( '../common/php/' );

require_once 'config.php';
require_once 'lib/t_lib.php';
require_once 'lib/ExternalWeather.php';
require_once 'lib/KLogger.php';

global $timezone;

// Set timezone for all PHP functions
date_default_timezone_set( $timezone );

$pdo = new PDO( "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']}", $dbConfig['username'], $dbConfig['password'] );
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// Set timezone for all MySQL functions
$pdo->exec( "SET time_zone = '$timezone'" );

// Establish connection to log file
$log = KLogger::instance( $logDir );

/* I think that all references to the old LogIt() funciton have been replaced.  Time to remove this stub.
// Wrapper function so I can update the other procs later....
function logIt( $msg )
{
	global $log;

	$log->logInfo( 'WRAPPER: ' . $msg );
}
*/

// Get list of thermostats
// Move this to after user logs in future and get only stats for the selected user?
// Get two lists.  $allThermostats and $userThermostats.  "all" is for those scripts collecting data.  "user" is for user looking at instant status and charts.
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
{	// This is a fatal error, should I die()?
	$log->logFatal( 'Error getting thermostat list' );
}
?>