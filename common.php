<?php
require 'config.php';
require 'lib/t_lib.php';
require 'lib/ExternalWeather.php';

// In order to move the pChart library to an external location I found I had to modify the includepath
function add_include_path( $path )
{
	foreach( func_get_args() AS $path )
    {
		if( !file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir') )
        {
			trigger_error( "Include path '{$path}' not exists", E_USER_WARNING );
            continue;
        }

        $paths = explode(PATH_SEPARATOR, get_include_path());

		if( array_search( $path, $paths ) === false )
		{
            array_push($paths, $path);
		}

        set_include_path(implode(PATH_SEPARATOR, $paths));
    }
}

function write_ini_file( $assoc_arr, $path, $has_sections = FALSE )
{
	$content = '';
	if( $has_sections )
	{
		foreach( $assoc_arr as $key => $elem )
		{
			$content .= '[' . $key . "]\n";
			foreach( $elem as $key2 => $elem2 )
			{
				if( is_array( $elem2 ) )
				{
					for( $i = 0; $i < count( $elem2 ); $i++ )
					{
						$content .= $key2 . "[] = \"" . $elem2[$i] . "\"\n";
					}
				}
				else if( $elem2 == '' )
				{
					$content .= $key2 . " = \n";
				}
				else
				{
					$content .= $key2 . " = \"" . $elem2 . "\"\n";
				}
			}
		}
	}
	else
	{
		foreach( $assoc_arr as $key=>$elem )
		{
			if( is_array( $elem ) )
			{
				for( $i = 0; $i < count( $elem ); $i++ )
				{
					$content .= $key2 . "[] = \"" . $elem[$i] . "\"\n";
				}
			}
			else if( $elem == '' )
			{
				$content .= $key2 . " = \n";
			}
			else
			{
				$content .= $key2 . " = \"" . $elem . "\"\n";
			}
		}
	}

	if( !$handle = fopen( $path, 'w' ) )
	{
		return false;
	}
	if( !fwrite( $handle, $content ) )
	{
		return false;
	}
	fclose( $handle );
	return true;
}

function save_settings()
{
	$settingsFile = $rootDir . 'config.ini';
	write_ini_file( $assoc_arr, $settingsFile, TRUE );
}

/**
  * Need to set levels to control verbosity.  Here are the typical levels of verbosity I've seen. Not sure where to
	* draw the line on what is needed.
	*
	* FATAL
	* ERROR
	* WARN
	* INFO
	* DEBUG
	* TRACE
	*
	*/
function logIt( $msg )
{
	// Old - write to screen only
	//echo $msg . "\n";
	global $logDir;

	$logFile = $logDir . 'thermo.' . date( 'Y.m.d' ) . '.log';
	//echo $logFile . "\n";
	$fh = fopen( $logFile, 'a' );
	fwrite( $fh, date( 'H:i', time() ) . ' ' . $msg . "\n" );
	fclose( $fh );
}

/*
// Need to replace all instances of doError() in code with calls to logIt() using error flag.
// And then delete this function
function doError( $msg )
{
	echo $msg . "\n";
	file_put_contents( 'php://stderr', $msg . "\n" );

	logIt( "ERROR: " . $msg );
}
*/

// Common code that should run for EVERY page follows here

global $timezone;

// Set timezone for all PHP functions
date_default_timezone_set( $timezone );

// Always connect to the database, don't wait for a request to connect
//$pdo = new PDO( $dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'] );
$pdo = new PDO( "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']}", $dbConfig['username'], $dbConfig['password'] );
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// Set timezone for all MySQL functions
$pdo->exec( "SET time_zone = '$timezone'" );

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
	logIt( 'Error getting thermostat list' );
}
?>