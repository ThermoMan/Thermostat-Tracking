<?php
$start_time = microtime(true);
require_once( 'common.php' );
require_once( 'user.php' );
$util::logInfo( 'admin_dl: Start' );

$reply['result'] = false;

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
$login = new USER( $uname, $session );

$util::logDebug( 'admin_dl: Test user ' . $login->getName() );
if( ! $login->isLoggedin() || ! $login->isSiteAdmin() ){
  $util::logError( "admin_dl: called by someone other than admin" );
  die();
}
//$util::logDebug( 'admin_dl: User OK' );

function backupAllTables(){
  global $util;

// QQQ Really should make a global database connection that everyone shares instead of reinstantiating it all over the place.
// QQQ That means doing it like I have done the util with static functiond and everything.
// QQQ Need an abstract-ish version that has generic stuff and a project level one that extends the vase class
  $database = new Database();
// QQQ Really should make a global database connection that everyone shares instead of reinstantiating it all over the place.

  $now = date( 'Y-m-d-H-i', time() );

  $util::logInfo( 'admin_dl: backupAllTables: Backup starting.' );
  $tableList = array( 'hvac_cycles', 'hvac_status', 'locations', 'location_data', 'meters', 'meter_data', 'run_times', 'setpoints', 'thermostats', 'thermostat_data', 'users' );
  foreach( $tableList as $tableName ){
    $util::logInfo( 'admin_dl: backupAllTables: Backup starting for table: (' . $tableName . ')' );
    $database->backupOneTable( $tableName, $now );
  }
  $util::logInfo( 'admin_dl: backupAllTables: Backup complete.' );
  return true;
}

$validActions = [ 'backup'
                 ,'clean_logs' ];


if( isset( $_GET[ 'action' ] ) ){
  $action = $_GET[ 'action' ];
  $reply['action'] = $action;

  if( ! in_array( $action, $validActions, true ) ){
    $util::logError( "admin_dl: front end is trying invalid action: ($action)" );
  }
  else{
    switch( $action ){
      case 'clean_logs':
        $howMany = $util::logClean();
        $reply['result'] = true;
        $reply['clean_logs'] = $howMany;
      break;

      case 'backup':
        $reply['result'] = backupAllTables();
        // $reply['backup'] =
      break;

      default:
        $util::logError( "admin_dl: action deemed valid, but unsupported: ($action)" );
      break;
    }
  }
}



$answer = array();
$answer[ 'result' ] = $reply;
echo json_encode( array( 'answer' => $answer), JSON_NUMERIC_CHECK );
$log::logInfo( 'admin_dl: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );
return 0;
?>