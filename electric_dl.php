<?php
$start_time = microtime(true);
require_once( 'common.php' );
// _dl .... the "dl" means "data layer".  In MVC speak, this is the M.
$util::logInfo( "electric_dl: 0" );

// Common code that should run for EVERY CHART page follows here
$mtu_id = (isset($_REQUEST['mtu_id'])) ? $_REQUEST['mtu_id'] : null;    // Set id to chosen MTU (or null if not chosen)

if( $mtu_id == null ){
  // If there still is not one chosen then abort
  $util::logError( 'electric_dl: MTU ID was NULL!' );
  // Need to redirect output to some image showing user there was an error and suggesting to read the logs.
  throw new Config_Exception( 'electric_dl: error 3.  check logs.' );
$util::logInfo( "electric_dl: should NEVER see this message in logs!" );
}
$util::logInfo( "electric_dl: 1" );


// Get ending date for chart
$to_date = (isset($_GET['chart_daily_toDate'])) ? htmlspecialchars( $_GET['chart_daily_toDate'] ) : date( 'Y-m-d' );
if( ! $util::isValidDate( $to_date, 'Y-m-d' ) ){
  throw new Config_Exception( 'electric_dl: error 4.  check logs.' );
$util::logInfo( "electric_dl: should NEVER see this message in logs!" );
}
//$to_date = date( 'Y-m-d 00:00', strtotime( "$to_date + 1 day" ) );
$to_date = date( 'Y-m-d 00:00', strtotime( "$to_date" ) );

// Verify that date is not future?

$interval_measure = (isset($_GET['chart_daily_interval_group'])) ? $_GET['chart_daily_interval_group'] : 0;
if( $interval_measure < 0 || $interval_measure > 3 ){
  // 0: minutes, 1: hours, 2: days, 3: weeks, 4: years
  $interval_measure = 0;
}

if( isset( $_GET['chart_daily_interval_length'] ) ){
  $interval_length = $_GET['chart_daily_interval_length'];

  // Bounds checking
  if( $interval_length < 1 ) $interval_length = 1;
  if( $interval_length > 1096 ) $interval_length = 1096;
}

$date_text = array( 0 => 'minutes', 1 => 'hours', 2 => 'days', 3 => 'weeks', 3 => 'years' );
$interval_string = $to_date . ' - ' . $interval_length . ' ' . $date_text[$interval_measure];

// Compute the "from date"
$from_date = date( 'Y-m-d %H:%i', strtotime( $interval_string ) );

/*
$util::logDebug( "electric_dl: mtu_id = $mtu_id" );
$util::logDebug( "electric_dl: from_date = $from_date" );
$util::logDebug( "electric_dl: to_date = $to_date" );
$util::logDebug( "electric_dl: interval_string = $interval_string" );
$util::logDebug( "electric_dl: interval_length = $interval_length" );
$util::logDebug( "electric_dl: interval_measure = $interval_measure" );
*/

$database = new Database();
$pdo = $database->dbConnection();

// QQQ Need to test that mtu_id belongs to user_id
// QQQ Should start IDs from number other than 1!


$allData = array();

$sqlGetAllData =
"SELECT mtu_id
       ,date_format( date, '%Y/%m/%d %H:%i' )
       ,watts
       ,volts
   FROM {$database->table_prefix}meter_data
  WHERE mtu_id = ?
    AND date BETWEEN ? AND ?";
$util::logDebug( "electric_dl: sqlGetAllData = $sqlGetAllData" );

$queryGetAllData = $pdo->prepare( $sqlGetAllData );
$queryGetAllData->execute( array( $mtu_id, $from_date, $to_date ) );

$allData = $queryGetAllData->fetchAll( PDO::FETCH_NUM );
// $allData = $queryGetAllData->fetchAll( PDO::FETCH_OBJ );

$answer = array();
$answer[ 'allData' ] = $allData;
echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );

$util::logInfo( 'electric_dl: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>