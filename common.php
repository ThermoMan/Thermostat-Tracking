<?php
require 'config.php';
require 'lib/t_lib.php';
require 'lib/ExternalWeather.php';

// Future logging method
function logIt( $msg )
{
   echo $msg . "\n";
}

// Future logging method
function doError( $msg )
{
   echo $msg . "\n";
   file_put_contents( 'php://stderr', $msg . "\n" );
}


// Common code that should run for EVERY page follows here

global $timezone;

// Set timezone for all PHP functions
date_default_timezone_set( $timezone );

// Always connect to the database, don't wait for a request to connect
$pdo = new PDO( $dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'] );
$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// Set timezone for all MySQL functions
$pdo->exec( "SET time_zone = '$timezone'" );    // Like old one

// Get list of thermostats
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
  logIt( "Error getting thermostat list" );
}
?>