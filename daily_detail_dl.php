<?php
$start_time = microtime(true);
require_once( 'common_chart.php' );
// _dl .... the "dl" means "data layer".  In MVC speak, this is the M.
$util::logInfo( '0' );

// Do not use this method as it assumes things about the quality of the data.
// $table_flag = (isset($_REQUEST['table_flag'])) ? $_REQUEST['table_flag'] : false;


// QQQ need to change this.  I want two separate flags fetch_indoor_temps = true/false  fetch_outdoor_temps = true/false
// QQQ ALL arguments need a name review.  1) consistency  2) brevity  3) comprehension
$source = 2;  // Default to showing both
if( isset( $_GET['chart_daily_source'] ) ){
  // The "." character in the URL is somehow converted to an "_" character when PHP goes to look at it.
  $source = $_GET['chart_daily_source'];
}
if( $source < 0 || $source > 2 ){
  // If it is out of bounds, show both.  0: outdoor, 1: indoor, 2: both
  $source = 2;
}

// Default to off
$show_indoor_temp = 0;
$show_outoor_temp = 0;
switch( $source ){
  case 0:
    $show_indoor_temp = 1;
    $show_outoor_temp = 0;
  break;
  case 1:
    $show_indoor_temp = 0;
    $show_outoor_temp = 1;
  break;
  case 2:
    $show_indoor_temp = 1;
    $show_outoor_temp = 1;
  break;
}

// Get ending date for chart
$to_date = (isset($_GET['chart_daily_toDate'])) ? htmlspecialchars( $_GET['chart_daily_toDate'] ) : date( 'Y-m-d' );
if( ! validate_date( $to_date ) ) return;
// Verify that date is not future?

$interval_measure = (isset($_GET['chart_daily_interval_group'])) ? $_GET['chart_daily_interval_group'] : 0;
if( $interval_measure < 0 || $interval_measure > 3 ){
  // 0: days, 1: weeks, 2: months, 3: years
  $interval_measure = 0;
}

if( isset( $_GET['chart_daily_interval_length'] ) ){
  $interval_length = $_GET['chart_daily_interval_length'];

  // Bounds checking
  if( $interval_length < 1 ) $interval_length = 1;
  if( $interval_length > 1096 ) $interval_length = 1096;
}

$date_text = array( 0 => 'days', 1 => 'weeks', 2 => 'months', 3 => 'years' );
$interval_string = $to_date . ' -' . $interval_length . ' ' . $date_text[$interval_measure];

// Compute the "from date"
$from_date = date( 'Y-m-d 00:00', strtotime( $interval_string ) );

// There is the appearance of one extra day on every chart...
//$from_date = date( 'Y-m-d 00:00', strtotime( "$from_date + 1 day" ) );

$to_date = date( 'Y-m-d 23:59', strtotime( "$to_date + 1 day" ) );

// Set default cycle display to none
$show_heat_cycles = (isset($_GET['chart_daily_showHeat']) && ($_GET['chart_daily_showHeat'] == 'false')) ? 0 : 1;
$show_cool_cycles = (isset($_GET['chart_daily_showCool']) && ($_GET['chart_daily_showCool'] == 'false')) ? 0 : 1;
$show_fan_cycles  = (isset($_GET['chart_daily_showFan'])  && ($_GET['chart_daily_showFan']  == 'false')) ? 0 : 1;
// Set default for displaying set point temp to "off"
$show_setpoint    = (isset($_GET['chart_daily_setpoint']) && ($_GET['chart_daily_setpoint']  == 'false')) ? 0 : 1;

// Set default humidity display to none
$show_indoor_humidity = (isset($_GET['chart_daily_showIndoorHumidity']) && ($_GET['chart_daily_showIndoorHumidity'] == 'false')) ? 0 : 1;
$show_outdoor_humidity = (isset($_GET['chart_daily_showOutdoorHumidity']) && ($_GET['chart_daily_showOutdoorHumidity'] == 'false')) ? 0 : 1;

/*
$util::logDebug( "source = $source" );
$util::logDebug( "show_indoor_temp = $show_indoor_temp" );
$util::logDebug( "show_outoor_temp = $show_outoor_temp" );
$util::logDebug( "from_date = $from_date" );
$util::logDebug( "to_date = $to_date" );
$util::logDebug( "interval_string = $interval_string" );
$util::logDebug( "interval_length = $interval_length" );
$util::logDebug( "interval_measure = $interval_measure" );
$util::logDebug( "show_heat_cycles = $show_heat_cycles" );
$util::logDebug( "show_cool_cycles = $show_cool_cycles" );
$util::logDebug( "show_fan_cycles = $show_fan_cycles" );
$util::logDebug( "show_setpoint = $show_setpoint" );
$util::logDebug( "show_indoor_humidity = $show_indoor_humidity" );
$util::logDebug( "show_outdoor_humidity = $show_outdoor_humidity" );
*/

$database = new Database();
$pdo = $database->dbConnection();

// Need to tweak the date ranges on thee SELECTs to get all since the structure of the code changed so much.

$indoorTemp = array();
$indoorHumidity = array();

// Find all the data that is present
$sqlGetIndoorData =
"SELECT DATE_FORMAT( all_ranger.period, '%Y-%m-%d %H:%i' ) date
      ,temps.indoor_temp
      ,temps.indoor_humidity
FROM (SELECT period
        FROM (
           SELECT period
             FROM (
               SELECT DATE_FORMAT( DATE_ADD('2011-01-01', INTERVAL (POWER(6,6)*t6 + POWER(6,5)*t5 + POWER(6,4)*t4 + POWER(6,3)*t3 + POWER(6,2)*t2 + POWER(6,1)*t1 + t0) HOUR), '%Y-%m-%d %H:%i' ) AS period
                 FROM  (SELECT 0 t0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t0
                      ,(SELECT 0 t1 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t1
                      ,(SELECT 0 t2 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t2
                      ,(SELECT 0 t3 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t3
                      ,(SELECT 0 t4 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t4
                      ,(SELECT 0 t5 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t5
                      ,(SELECT 0 t6 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t6
             ) ranger0
   UNION ALL
           SELECT period
             FROM (
               SELECT DATE_FORMAT( DATE_ADD('2011-01-01', INTERVAL (POWER(6,6)*t6 + POWER(6,5)*t5 + POWER(6,4)*t4 + POWER(6,3)*t3 + POWER(6,2)*t2 + POWER(6,1)*t1 + t0) HOUR) + INTERVAL 30 MINUTE, '%Y-%m-%d %H:%i' ) AS period
                 FROM  (SELECT 0 t0 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t0
                      ,(SELECT 0 t1 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t1
                      ,(SELECT 0 t2 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t2
                      ,(SELECT 0 t3 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t3
                      ,(SELECT 0 t4 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t4
                      ,(SELECT 0 t5 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t5
                      ,(SELECT 0 t6 UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t6
             ) ranger1
        ) big_ranger
     ) all_ranger
LEFT OUTER JOIN {$database->table_prefix}thermostat_data temps
ON all_ranger.period = temps.date
AND temps.thermostat_id = ?
WHERE all_ranger.period BETWEEN ? AND ?
ORDER BY all_ranger.period ASC";
$queryGetIndoorData = $pdo->prepare( $sqlGetIndoorData );
$queryGetIndoorData->execute( array( $thermostat_id, $from_date, $to_date ) );


while( $row = $queryGetIndoorData->fetch( PDO::FETCH_ASSOC ) ){
  // Insted of this loop might be able to do something clever with PDO::bindColumn() - although I would have to bring back two instances of the date column
  // one each for the two target arrays.  Then a PDO::fetchAll() might get everything without having to touch a loop?
  $indoorTemp[ $row['date'] ] =  $row['indoor_temp'];
  $indoorHumidity[ $row['date'] ] = str_replace( -1, 'void', $row['indoor_humidity'] );
}




$outdoorTemp = array();
$outdoorHumidity = array();

$sqlGetOutdoorData =
"SELECT source.date, source.outdoor_temp, source.outdoor_humidity
FROM (
SELECT temps.date date, temps.outdoor_temp, temps.outdoor_humidity
  FROM {$database->table_prefix}location_data temps
 WHERE temps.date BETWEEN ? AND ?
   AND temps.location_id = ?
UNION ALL
SELECT temps.date + INTERVAL 30 MINUTE date, NULL outdoor_temp, NULL outdoor_humidity
 FROM {$database->table_prefix}location_data temps
   LEFT OUTER JOIN {$database->table_prefix}location_data missing
                ON temps.date + INTERVAL 30 MINUTE = missing.date
               AND missing.date BETWEEN ? AND ?
               AND missing.location_id = ?
 WHERE missing.date IS NULL
  AND temps.date BETWEEN ? AND ?
  AND temps.location_id = ? ) source
ORDER BY date ASC";

// QQQ need to look up location id.
// 1. Using the thermostat_id find the location_id.  Write the SQL so it tests the user to amke sure no one is scamming data.
$location_id = 1;

$queryGetOutdoorData = $pdo->prepare( $sqlGetOutdoorData );

$queryGetOutdoorData->execute( array( $from_date, $to_date, $location_id, $from_date, $to_date, $location_id, $from_date, $to_date, $location_id ) );

while( $row = $queryGetOutdoorData->fetch( PDO::FETCH_ASSOC ) ){
  $outdoorTemp[ $row['date'] ] =  $row['outdoor_temp'];
  $outdoorHumidity[ $row['date'] ] = $row['outdoor_humidity'];
}


$minutes = '30';


$dates = '';


// For a $show_date of '2012-07-10' get the start and end bounding datetimes
$start_date = strftime( '%Y-%m-%d 00:00:00', strtotime($from_date));  // "2012-07-10 00:00:00";
$end_date = strftime( '%Y-%m-%d 23:59:59', strtotime($to_date));      // "2012-07-10 23:59:59";

if( ($show_heat_cycles + $show_cool_cycles + $show_fan_cycles) > 0 ){
  /**
    * This SQL should include cycles that started on the previous night or ended on the
    *  following morning for any given date.
    *
    * Ought to graphically differentiate those open ended cycles somehow?
    */
  $sqlTwo =
  "SELECT system,
          DATEDIFF( start_time, ? ) AS start_day,
          DATEDIFF( end_time, ? ) AS end_day,
          DATE_FORMAT( GREATEST( start_time, ? ), '%k' ) AS start_hour,
          TRIM(LEADING '0' FROM DATE_FORMAT( GREATEST( start_time, ? ), '%i' ) ) AS start_minute,
          DATE_FORMAT( LEAST( end_time, ? ), '%k' ) AS end_hour,
          TRIM( LEADING '0' FROM DATE_FORMAT( LEAST( end_time, ? ), '%i' ) ) AS end_minute
  FROM {$database->table_prefix}hvac_cycles
  WHERE start_time >= ? AND end_time <= ? AND thermostat_id = ?
  ORDER BY start_time ASC";


  $queryTwo = $pdo->prepare( $sqlTwo );

  $result = $queryTwo->execute(array( $start_date, $start_date, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $thermostat_id ) );

//$util::logInfo( "Executing sqlTwo ($sqlTwo) for values $start_date, $start_date, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $thermostat_id" );

  $sqlThree = "SELECT heat_status
          ,DATEDIFF( start_date_heat, ? ) AS start_day_heat
          ,DATE_FORMAT( start_date_heat, '%k' ) AS start_hour_heat
          ,TRIM(LEADING '0' FROM DATE_FORMAT( start_date_heat, '%i' ) ) AS start_minute_heat

          ,cool_status
          ,DATEDIFF( start_date_cool, ? ) AS start_day_cool
          ,DATE_FORMAT( start_date_cool, '%k' ) AS start_hour_cool
          ,TRIM(LEADING '0' FROM DATE_FORMAT( start_date_cool, '%i' ) ) AS start_minute_cool

          ,fan_status
          ,DATEDIFF( start_date_fan, ? ) AS start_day_fan
          ,DATE_FORMAT( start_date_fan, '%k' ) AS start_hour_fan
          ,TRIM(LEADING '0' FROM DATE_FORMAT( start_date_fan, '%i' ) ) AS start_minute_fan

          ,DATEDIFF( date, ? ) AS end_day
          ,DATE_FORMAT( date, '%k' ) AS end_hour
          ,TRIM( LEADING '0' FROM DATE_FORMAT( date, '%i' ) ) AS end_minute

          FROM {$database->table_prefix}hvac_status
          WHERE tstat_uuid = ?";

  $queryThree = $pdo->prepare( $sqlThree );

  $result = $queryThree->execute(array( $from_date, $from_date, $from_date, $from_date, $thermostat_id ) );
}
$util::logDebug( "14" );

if( $show_setpoint == 1 ){
  $sqlFour =
  "SELECT set_point, switch_time
   FROM {$database->table_prefix}setpoints
   WHERE thermostat_id = ?
    AND switch_time BETWEEN ? AND ?
   UNION ALL
   SELECT set_point, switch_time
   FROM (
    SELECT *
    FROM {$database->table_prefix}setpoints
    WHERE switch_time < ?
    ORDER BY switch_time DESC
    LIMIT 1
    ) AS one_before_start
   ORDER BY switch_time ASC";

  $queryFour = $pdo->prepare( $sqlFour );
$util::logDebug( "Executing sqlFour ($sqlFour) for values $thermostat_id, $start_date, $end_date, $start_date" );
  $result = $queryFour->execute(array( $thermostat_id, $start_date, $end_date, $start_date ) );
  while( $row = $queryFour->fetch( PDO::FETCH_ASSOC ) ){
    $queryFourData[] = $row;
  }
}

$util::logDebug( "15" );


if( ($show_heat_cycles + $show_cool_cycles + $show_fan_cycles) > 0 ){
  // The SQL has already been executed.  Now just draw it.


  while( $row = $queryTwo->fetch( PDO::FETCH_ASSOC ) ){

    // 'YYYY-MM-DD HH:mm:00'  There are NO seconds in these data points.
    $cycle_start = $LeftMargin + ((($row['start_day'] * 1440) + ($row['start_hour'] * 60) + $row['start_minute'] ) * $PixelsPerMinute);
    $cycle_end   = $LeftMargin + ((($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute);

    if( $row['system'] == 1 && $show_heat_cycles == 1 ){
      // Heat
    }
    else if( $row['system'] == 2 && $show_cool_cycles == 1 ){
      // A/C
    }
    else if( $row['system']== 3 && $show_fan_cycles == 1 ){
      // Fan
    }
  }

  // Now draw boxes for a presently running heat/cool/fan sessions.

  while( $row = $queryThree->fetch( PDO::FETCH_ASSOC ) ){
    // Should be only one row!
    if( $row['heat_status'] == 1 && $show_heat_cycles == 1 ){
      // If the AC is on now AND we want to draw it
      $cycle_start = $LeftMargin + (($row['start_day_heat'] * 1440) + ($row['start_hour_heat'] * 60) + $row['start_minute_heat'] ) * $PixelsPerMinute;
      $cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

    }
    if( $row['cool_status'] == 1 && $show_cool_cycles == 1 ){
      // If the AC is on now AND we want to draw it
      $cycle_start = $LeftMargin + (($row['start_day_cool'] * 1440) + ($row['start_hour_cool'] * 60) + $row['start_minute_cool'] ) * $PixelsPerMinute;
      $cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

    }
    if( $row['fan_status'] == 1 && $show_fan_cycles == 1 ){
      // If the AC is on now AND we want to draw it
      $cycle_start = $LeftMargin + (($row['start_day_fan'] * 1440) + ($row['start_hour_fan'] * 60) + $row['start_minute_fan'] ) * $PixelsPerMinute;
      $cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

    }
  }
}

$util::logDebug( "18" );

if( $show_setpoint == 1 ){

  $first_row = 1;
//  while( $row = $queryFour->fetch( PDO::FETCH_ASSOC ) )
  foreach( $queryFourData as $row ){
    /** The query returns one row prior to the current date range so that
      * we can determine the setpoint leading into the first drawn day
      *** This falls apart currently if there is not a setpoint for the prior day
      *** but there should always be one unless the database table is just starting
      *** to become populated with data.
      */
    if( $first_row == 1 ){
      $first_row = 0;
      $prev_setpoint = $row['set_point'];
      $prev_switch_time = date_create( $from_date );
      $start_px = $LeftMargin;
      continue;
    }

    // Compute the switch time delta
    $setpoint = $row['set_point'];
    $switch_time = date_create( $row['switch_time'] );
    $interval = $prev_switch_time->diff( $switch_time );

    // Compute the next end pixel based on the switch time difference
    $end_px = $start_px + ( $interval->format('%h') * 60 + $interval->format('%i') ) * $PixelsPerMinute;

    // Reset parameters for next iteration
    $prev_switch_time = $switch_time;
    $prev_setpoint = $setpoint;
    $start_px = $end_px;
  }

  $now = date_create();
  $interval = $prev_switch_time->diff($now);
}

$util::logDebug( "19" );

$answer = array();

if( $show_indoor_temp == 1 ) $answer[ 'indoorTemp' ] = $indoorTemp;
if( $show_indoor_humidity == 1 ) $answer[ 'indoorHumidity' ] = $indoorHumidity;

if( $show_outoor_temp == 1 ) $answer[ 'outdoorTemp' ] = $outdoorTemp;
if( $show_outdoor_humidity == 1 ) $answer[ 'outdoorHumidity' ] = $outdoorHumidity;

//if( $show_setpoint == 1 ) $answer[ 'setpoint' ] = $setpoint;

echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );

$util::logInfo( 'execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>