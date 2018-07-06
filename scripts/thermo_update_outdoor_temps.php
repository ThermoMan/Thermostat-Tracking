<?php
$start_time = microtime(true);
require_once( dirname(__FILE__).'/../common.php' );

global $util;

$util::logDebug( 'outdoor_temps: Start.' );
$unixTime = $argv[1]; // argv[0] is this files name

/**
  * This script updates outdoor temperature and the humidity for each location each half hour.
  */

// Get a database connection.  This does not need a USER object to continue.
$database = new Database();
$pdo = $database->dbConnection();


// Should not require DISTINCT keyword, but just in case...
$sql = "
SELECT loc.location_id
      ,loc.location_string AS zip_code
  FROM {$database->table_prefix}locations loc";
$stmt = $pdo->prepare( $sql );
$stmt->execute();
$allLocations = $stmt->fetchAll( PDO::FETCH_ASSOC );

try{
  $sql = "SELECT NOW() as now_time, CONCAT( SUBSTR( NOW() , 1, 15 ) , '0:00' ) as magic_time;";
  $queryMySQLServer = $pdo->prepare( $sql );

  $sql = "INSERT INTO {$database->table_prefix}location_data( location_id, date, outdoor_temp, outdoor_humidity ) VALUES ( ?, \"$unixTime\", ?, ? )";
  $queryTemp = $pdo->prepare( $sql );
}
catch( Exception $e ){
  $util::logError( 'outdoor_temps: DB Exception while preparing SQL: ' . $e->getMessage() );
  die();
}

$queryMySQLServer->execute();
$row = $queryMySQLServer->fetch( PDO::FETCH_ASSOC );
$util::logDebug( "outdoor_temps: The MySQL server thinks that the magic formatted time is {$row['magic_time']} where unix (on the webserver) thinks it is $unixTime" );

foreach( $allLocations as $locationRec ){
  $outdoorTemp = null;            // Default outside temp
  $outdoorHumidity = null;        // Default outside humidity
  try{
    $externalWeatherAPI = new ExternalWeather( $weatherConfig );
    $outsideData = $externalWeatherAPI->getOutdoorWeather( $locationRec[ 'zip_code' ] );
    $outdoorTemp = $outsideData['temp'];
    $outdoorHumidity = $outsideData['humidity'];
$util::logDebug( "thermo_update_outdoor_temps: Outside Weather for {$locationRec[ 'zip_code' ]}: Temp $outdoorTemp Humidity $outdoorHumidity" );

    try{
$util::logDebug( 'outdoor_temps: G' );
      $queryTemp->execute(array( $locationRec[ 'location_id' ], $outdoorTemp, $outdoorHumidity ) );
// QQQ Need to test that one row was written or complain/throw (in order to save outdoor data).
$util::logDebug( 'outdoor_temps: I think there were (' . $queryTemp->rowCount() . ') rows inserted.' );
    }
    catch( Exception $e ){
      $util::logError( 'outdoor_temps: Saving external weather failed: ' . $e->getMessage() );
    }
  }
  catch( Exception $e ){
    $util::logError( 'outdoor_temps: Fetching external weather failed: ' . $e->getMessage() );
  }


}
$util::logDebug( 'outdoor_temps: execution time ' . (microtime(true) - $start_time) . ' secs.' );

?>