<?php
$start_time = microtime(true);
require_once( 'common.php' );
$util::logInfo( 'backup: 0' );

$database = new Database();

function backupAllTables(){
  global $util;
  global $database;

  $now = date( 'Y-m-d-H-i', time() );

  $util::logInfo( 'backup: backupAllTables: Backup starting.' );
  $tableList = array( 'hvac_cycles', 'hvac_status', 'locations', 'location_data', 'meters', 'meter_data', 'run_times', 'setpoints', 'thermostats', 'thermostat_data', 'users' );
  foreach( $tableList as $tableName ){
    $util::logInfo( 'backup: backupAllTables: Backup starting for table: (' . $database->table_prefix . $tableName . ')' );
    $database->backupOneTable( $database->table_prefix . $tableName, $now );
  }
  $util::logInfo( 'backup: backupAllTables: Backup complete.' );
  return true;
}

$result = backupAllTables();

$answer = array();
if( $result == true ) $answer[ 'result' ] = 'success';
else $answer[ 'result' ] = 'failure';
echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );

$log::logInfo( 'backup: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>