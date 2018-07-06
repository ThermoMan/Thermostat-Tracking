<?php
$start_time = microtime(true);
require_once( dirname(__FILE__).'/../common.php' );

global $util;

$util::logDebug( 'indoor_temps: Start.' );
$today = date( 'Y-m-d' );
$yesterday = date( 'Y-m-d', strtotime( 'yesterday' ));
if( $argc < 2 ){
  $util::logError( 'indoor_temps: required argument missing.  Must send unix timestamp!' );
  die();
}
$unixTime = $argv[1]; // argv[0] is this files name

/**
  * This script updates the indoor and outdoor temperatures and today's and yesterday total run time for each thermostat.
  */

// Bandaid to keep things moving
$database = new Database();
$pdo = $database->dbConnection();

$sql = "
SELECT stat.thermostat_id
      ,stat.tstat_uuid
      ,stat.model
      ,stat.fw_version
      ,stat.wlan_fw_version
      ,stat.ip
  FROM {$database->table_prefix}thermostats AS stat";
$stmt = $pdo->prepare( $sql );
$stmt->execute();
$allThermostats = $stmt->fetchAll( PDO::FETCH_ASSOC );

try{
  $sql = "SELECT NOW() as now_time, CONCAT( SUBSTR( NOW() , 1, 15 ) , '0:00' ) as magic_time;";
  $queryMySQLServer = $pdo->prepare( $sql );

  $sql = "INSERT INTO {$database->table_prefix}thermostat_data( thermostat_id, date, indoor_temp, indoor_humidity ) VALUES ( ?, \"$unixTime\", ?, ? )";
  $queryTemp = $pdo->prepare( $sql );

  $sql = "DELETE FROM {$database->table_prefix}run_times WHERE date = ? AND thermostat_id = ?";
  $queryRunDelete = $pdo->prepare( $sql );

  $sql = "INSERT INTO {$database->table_prefix}run_times( thermostat_id, date, heat_runtime, cool_runtime ) VALUES ( ?, ?, ?, ? )";
  $queryRunInsert = $pdo->prepare( $sql );
}
catch( Exception $e ){
  $util::logError( 'indoor_temps: DB Exception while preparing SQL: ' . $e->getMessage() );
  die();
}

$queryMySQLServer->execute();
$row = $queryMySQLServer->fetch( PDO::FETCH_ASSOC );
$util::logDebug( "indoor_temps: The MySQL server thinks that the magic formatted time is {$row['magic_time']} where unix (on the webserver) thinks it is $unixTime" );

foreach( $allThermostats as $thermostatRec ){
  $lockFileName = $lockFile . $thermostatRec['thermostat_id'];
  $lock = @fopen( $lockFileName, 'w' );
  if( !$lock ){
    $util::logError( "indoor_temps: Could not write to lock file $lockFileName" );
    continue;
  }

  if( flock( $lock, LOCK_EX ) ){
    try{
      // Query thermostat info
      $indoorHumidity = null;
//$util::logDebug( "indoor_temps: Connecting to {$thermostatRec['id']} {$thermostatRec['thermostat_id']} {$thermostatRec['ip']} {$thermostatRec['name']}" );
//      $stat = new Stat( $thermostatRec['ip'] );
      $stat = new Stat( $thermostatRec );

/** Skip asking for UUID, just assume it has not changed
 ** Perhaps once a month check that the UUID has not changed - alert user if it has.
 ** If the user changes his thermostat, require him to go though an update process similar to his setup process
      try{
        $stat->getSysInfo();  // Get uuid for for insert key (yuck)
      }
      catch( Exception $e ){
        $util::logError( 'indoor_temps: Error getting UUID: ' . $e->getMessage() );
        $util::logError( "indoor_temps: Error getting UUID from {$thermostatRec['thermostat_id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}.  Aborting communication with this unit." );
        continue;
      }
**/

/** Skip asking for the model number, just assume it has not changed
 ** If the user changes his thermostat, require him to go though an update process similar to his setup process
      try{
        $stat->getModel();    // Get model to know if humidity is available
// Instead of asking the thermostat what his model is, should rely upon the entry in the thermostat table?
        if( strstr($stat->model, 'CT80') !== false ){
          // Get indoor humidity for CT80
          $stat->getHumidity();
// Actually, won't the humidity come back from the getStat() call if it is available on the thermostat?
        }
      }
      catch( Exception $e ){
        $util::logError( 'indoor_temps: Error getting model number (ignore this error): ' . $e->getMessage() );
      }
**/

      // Fetch and log the indoor and outdoor temperatures for this half-hour increment
      try{
//$util::logDebug( 'indoor_temps: G' );
        $stat->getStat();
$util::logDebug( 'indoor_temps: storing the data for ' . $stat->uuid );
        $queryTemp->execute(array( $stat->thermostat_id, $stat->temp, $stat->humidity ) );
// QQQ Need to test that one row was written or complain/throw (in order to save outdoor data).
$util::logDebug( 'indoor_temps: I think there were (' . $queryTemp->rowCount() . ') rows inserted.' );
      }
      catch( Exception $e ){
$util::logDebug( 'indoor_temps: I think there were (' . $queryTemp->rowCount() . ') rows inserted (and an exception was thrown).' );
        $util::logError( 'indoor_temps: Error getting temperatures: ' . $e->getMessage() );
        $util::logError( "indoor_temps: Error getting temperatures from {$thermostatRec['thermostat_id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']}.  No data stored." );
      }

/** QQQ This needs tobe smarter, only need to ask for yesterdays data ONE TIME and we never need to ask for today's data **/
      // Fetch and log the run time for yesterday and today
      try{
        $stat->getDataLog();
// Don't actually need todays run time do we?
        $queryRunDelete->execute( array($today, $stat->thermostat_id) ); // Remove zero or one rows for today and then insert one row for today.
        $queryRunInsert->execute( array($stat->thermostat_id, $today, $stat->runTimeHeat, $stat->runTimeCool) ); // Add new run time record for today
// Ought to keep track of when "yesterday" was last updated and if it was any time "today" then skip this!
// Need a meta data tracking value with last updated on date so that if yesterday was updated today do not do this
        $queryRunDelete->execute( array($yesterday, $stat->thermostat_id) ); // Remove zero or one rows for yesterday and then insert one row for yesterday.
        $queryRunInsert->execute( array($stat->thermostat_id, $yesterday, $stat->runTimeHeatYesterday, $stat->runTimeCoolYesterday) ); // Add new run time for yesterday
      }
      catch( Exception $e ){
        $util::logError( 'indoor_temps: Error getting run times: ' . $e->getMessage() );
        $util::logError( "indoor_temps: Error getting run times from {$thermostatRec['thermostat_id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}.  No data stored." );
      }

    }
    catch( Exception $e ){
      $util::logError( 'indoor_temps: Thermostat Exception: ' . $e->getMessage() );
    }
    flock( $lock, LOCK_UN );
  }
  else{
    $util::logDebug( "indoor_temps: Couldn't get file lock for thermostat {$thermostatRec['thermostat_id']}" );
    die();
  }
  fclose( $lock );
}
$util::logDebug( 'indoor_temps: End: time ' . (microtime(true) - $start_time) . ' secs.' );

?>