<?php
/**
  * This script updates outdoor temperature and the humidity for each outdoor location each half hour.
  */
$start_time = microtime(true);
require_once( dirname(__FILE__).'/../common.php' );
global $util;
$util::logDebug( 'outdoor_temps: Start.' );

if( $argc < 2 ){
  $util::logError( 'outdoor_temps: required argument missing.  Must send unix timestamp!' );
  die();
}
$unixTime = $argv[1]; // argv[0] is this files name

$database = new Database();
$pdo = $database->dbConnection();

try{
  $sql = "
SELECT outdoor_id
      ,outdoor_string AS zip_code
  FROM {$database->table_prefix}outdoors";
  $stmt = $pdo->prepare( $sql );
  $stmt->execute();
  $alloutdoors = $stmt->fetchAll( PDO::FETCH_ASSOC );
}
catch( Exception $e ){
  $util::logError( 'outdoor_temps: DB Exception while getting list of outdoor locations to query : ' . $e->getMessage() );
  die();
}

try{
  $sqlMySQLServer = "
SELECT NOW() as now_time
      ,CONCAT( SUBSTR( NOW() , 1, 15 ) , '0:00' ) as magic_time;";
  $queryMySQLServer = $pdo->prepare( $sqlMySQLServer );

  $sqlInsertTemp = "
INSERT INTO {$database->table_prefix}outdoor_data(
              outdoor_id
             ,date
             ,outdoor_temp
             ,outdoor_humidity
            )
     VALUES (
              ?
             ,\"$unixTime\"
             ,?
             ,?
            )";
  $queryInsertTemp = $pdo->prepare( $sqlInsertTemp );
}
catch( Exception $e ){
  $util::logError( "outdoor_temps: DB Exception while preparing SQL: {$e->getMessage()}" );
  die();
}

$queryMySQLServer->execute();
$row = $queryMySQLServer->fetch( PDO::FETCH_ASSOC );
//$util::logDebug( "outdoor_temps: The MySQL server thinks that the magic formatted time is {$row['magic_time']} where unix (on the webserver) thinks it is $unixTime" );

foreach( $alloutdoors as $outdoorRec ){
  $outdoorTemp = null;            // Default outside temp
  $outdoorHumidity = null;        // Default outside humidity
  try{
    $externalWeatherAPI = new ExternalWeather( $weatherConfig );
    $outsideData = $externalWeatherAPI->getOutdoorWeather( $outdoorRec[ 'zip_code' ] );
    $outdoorTemp = $outsideData['temp'];
    $outdoorHumidity = $outsideData['humidity'];
//$util::logDebug( "thermo_update_outdoor_temps: Outside Weather for {$outdoorRec[ 'zip_code' ]}: Temp $outdoorTemp Humidity $outdoorHumidity" );

    try{
//$util::logDebug( 'outdoor_temps: G' );
      $queryInsertTemp->execute(array( $outdoorRec[ 'outdoor_id' ], $outdoorTemp, $outdoorHumidity ) );
// QQQ Need to test that one row was written or complain/throw (in order to save outdoor data).
$util::logDebug( "outdoor_temps: I think there were ( {$queryInsertTemp->rowCount()} ) rows inserted." );
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