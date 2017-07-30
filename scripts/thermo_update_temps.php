<?php
$start_time = microtime(true);
require_once( dirname(__FILE__).'/../common.php' );

global $util;


$util::logDebug( 'temps: Start.' );
$today = date( 'Y-m-d' );
$yesterday = date( 'Y-m-d', strtotime( 'yesterday' ));
if( $argc < 2 )
{
  $util::logError( 'temps: required argument missing.  Must send unix timestamp!' );
  die();
}
$unixTime = $argv[1]; // argv[0] is this files name

/**
  * This script updates the indoor and outdoor temperatures and today's and yesterday total run time for each thermostat.
  */

// Bandaid to keep things moving
$database = new Database();
$pdo = $database->dbConnection();
$stmt = $pdo->prepare( 'SELECT stat.id, stat.tstat_uuid, stat.model, stat.fw_version, stat.wlan_fw_version, stat.ip, stat.name, stat.description, stat.user_id, stat.zip_code, stat.timezone FROM thermo2__thermostats AS stat' );
$stmt->execute();
$allThermostats = $stmt->fetchAll( PDO::FETCH_ASSOC );

try{
  $sql = "SELECT NOW() as now_time, CONCAT( SUBSTR( NOW() , 1, 15 ) , '0:00' ) as magic_time;";
  $queryMySQLServer = $pdo->prepare( $sql );

  //$sql = "INSERT INTO {$database->table_prefix}temperatures( tstat_uuid, date, indoor_temp, outdoor_temp, indoor_humidity, outdoor_humidity ) VALUES ( ?, CONCAT( SUBSTR( NOW() , 1, 15 ) , \"0:00\" ), ?, ?, ?, ? )";
  $sql = "INSERT INTO {$database->table_prefix}temperatures( tstat_uuid, date, indoor_temp, outdoor_temp, indoor_humidity, outdoor_humidity ) VALUES ( ?, \"$unixTime\", ?, ?, ?, ? )";
  $queryTemp = $pdo->prepare( $sql );

  $sql = "DELETE FROM {$database->table_prefix}run_times WHERE date = ? AND tstat_uuid = ?";
  $queryRunDelete = $pdo->prepare( $sql );

  $sql = "INSERT INTO {$database->table_prefix}run_times( tstat_uuid, date, heat_runtime, cool_runtime ) VALUES ( ?, ?, ?, ? )";
  $queryRunInsert = $pdo->prepare( $sql );
}
catch( Exception $e ){
  $util::logError( 'temps: DB Exception while preparing SQL: ' . $e->getMessage() );
  die();
}

$queryMySQLServer->execute();
$row = $queryMySQLServer->fetch( PDO::FETCH_ASSOC );
//$util::logDebug( "temps: The MySQL server thinks that the magic formatted time is {$row['magic_time']} where unix (on the webserver) thinks it is $unixTime" );

foreach( $allThermostats as $thermostatRec ){
  $lockFileName = $lockFile . $thermostatRec['id'];
  $lock = @fopen( $lockFileName, 'w' );
  if( !$lock ){
    $util::logError( "temps: Could not write to lock file $lockFileName" );
    continue;
  }

  if( flock( $lock, LOCK_EX ) ){

$outdoorTemp = null;            // Default outside temp
$outdoorHumidity = null;        // Default outside humidity (in case not working with CT80 or similar unit)
try{
  $externalWeatherAPI = new ExternalWeather( $weatherConfig );
  $outsideData = $externalWeatherAPI->getOutdoorWeather( $thermostatRec[ 'zip_code' ] );
  $outdoorTemp = $outsideData['temp'];
  $outdoorHumidity = $outsideData['humidity'];
//$util::logDebug( "thermo_update_temps: Outside Weather for {$thermostatRec[ 'zip_code' ]}: Temp $outdoorTemp Humidity $outdoorHumidity" );
}
catch( Exception $e ){
  $util::logError( 'temps: External weather failed: ' . $e->getMessage() );
  // Not a fatal error, keep going.
}
    try{
      // Query thermostat info
      $indoorHumidity = null;
//$util::logDebug( "temps: Connecting to {$thermostatRec['id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}" );
//      $stat = new Stat( $thermostatRec['ip'] );
      $stat = new Stat( $thermostatRec );

      $stat->getSysInfo();  // Get uuid for for insert key (yuck)
      if( $stat->connectOK != 0 ){
        $util::logError( "temps: Error getting UUID from {$thermostatRec['id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}.  Aborting communication with this unit." );
        continue;
      }

      $stat->getModel();    // Get model to know if humidity is available
// Instead of asking the thermostat what his model is, should rely upon the entry in the thermostat table?
      if( strstr($stat->model, 'CT80') !== false ){
        // Get indoor humidity for CT80
        $stat->getHumidity();
// Actually, won't the humidity come back from the getStat() call if it is available on the thermostat?
      }

      // Fetch and log the indoor and outdoor temperatures for this half-hour increment
      $stat->getStat();
//$util::logDebug( "temps: Back from low level communication I have the error code as ($stat->connectOK)" );
//$util::logDebug( "temps: Back from low level communication I have the temperature as ($stat->temp)" );
//$util::logDebug( "temps: UUID $stat->uuid IT " . $stat->temp . " OT $outdoorTemp IH $stat->humidity OH $outdoorHumidity  at PHP time = " . date("Y-m-d H:i:s") );
      if( $stat->connectOK == 0 ){
        $queryTemp->execute(array( $stat->uuid, $stat->temp, $outdoorTemp, $stat->humidity, $outdoorHumidity ) );
      }
      else{
        $util::logError( "temps: Error getting temperatures from {$thermostatRec['id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}.  No data stored." );
        $util::logWarn( "temps: Trying to save the outdoor temperature at least." );
        $queryTemp->execute(array( $stat->uuid, $stat->temp, $outdoorTemp, $stat->humidity, $outdoorHumidity ) );
      }


      // Fetch and log the run time for yesterday and today
      $stat->getDataLog();
//      $util::logDebug( "temps: Run Time Today - Inserting RTH {$stat->runTimeHeat} RTC {$stat->runTimeCool} U $stat->uuid T $today" );
//      $util::logDebug( "temps: Run Time Yesterday - Inserting RTH {$stat->runTimeHeatYesterday} RTC {$stat->runTimeCoolYesterday} U $stat->uuid T $yesterday" );

      if( $stat->connectOK == 0 ){
        $queryRunDelete->execute( array($today, $stat->uuid) ); // Remove zero or one rows for today and then insert one row for today.
        $queryRunInsert->execute( array($stat->uuid, $today, $stat->runTimeHeat, $stat->runTimeCool) ); // Add new run time record for today

// Ought to keep track of when "yesterday" was last updated and if it was any time "today" then skip this!
        $queryRunDelete->execute( array($yesterday, $stat->uuid) ); // Remove zero or one rows for yesterday and then insert one row for yesterday.
        $queryRunInsert->execute( array($stat->uuid, $yesterday, $stat->runTimeHeatYesterday, $stat->runTimeCoolYesterday) ); // Add new run time for yesterday
      }
      else{
        $util::logError( "temps: Error getting run times from {$thermostatRec['id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}.  No data stored." );
      }

    }
    catch( Exception $e ){
      $util::logError( 'temps: Thermostat Exception: ' . $e->getMessage() );
    }
    flock( $lock, LOCK_UN );
  }
  else{
    $util::logDebug( "temps: Couldn't get file lock for thermostat {$thermostatRec['id']}" );
    die();
  }
  fclose( $lock );
}
$util::logDebug( 'temps: End.  Execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>