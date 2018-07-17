<?php
$start_time = microtime(true);
require_once( 'common_chart.php' );
// _dl .... the "dl" means "data layer".  In MVC speak, this is the M.
$util::logInfo( "compare_dl: Start" );


$get_from = (isset($_GET['get_from']) && ($_GET['get_from'] == 'true')) ? 1 : 0;
if( $get_from == 1){
  $from_dates = array();

// QQQ Need SQL to get the FROM dates, but for now, hard code....
$from_dates = [2012, 2013, 2014, 2015, 2016, 2017 ];

  $answer = array();
  $answer[ 'from_dates' ] = $from_dates;
  echo json_encode( array( 'answer' => $answer), JSON_NUMERIC_CHECK );
$util::logInfo( 'compare_dl: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );
  exit();
}

$get_to = (isset($_GET['get_to']) && ($_GET['get_to'] == 'true')) ? 1 : 0;
if( $get_to == 1){
  $to_dates = array();

// QQQ Need SQL to get the FROM dates, but for now, hard code....
$to_dates = [2013, 2014, 2015, 2016, 2017, 2018 ];

  $answer = array();
  $answer[ 'to_dates' ] = $to_dates;
  echo json_encode( array( 'answer' => $answer), JSON_NUMERIC_CHECK );
$util::logInfo( 'compare_dl: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );
  exit();
}

$hddBaseF = 65;
$cddBaseF = 65;

$hddBaseC = ( ( $hddBaseF - 32 ) * 5 ) / 9;
$cddBaseC = ( ( $cddBaseF - 32 ) * 5 ) / 9;

if( $config['units'] = 'F' ){
  $hddBase = $hddBaseF;
  $cddBase = $cddBaseF;
}
else{
  $hddBase = $hddBaseC;
  $cddBase = $cddBaseC;
}


//ROUND(SUM(heat_runtime)/60,0) AS heatHours,
//ROUND(SUM(cool_runtime)/60,0) AS coolHours
//AND   DATE_FORMAT(date, '%Y') = '2013'
// DATE_FORMAT(date, '%Y-%m')

$database = new Database();
$pdo = $database->dbConnection();


$sql = "SELECT DATE_FORMAT(date, '%m') AS theMonthNumber,
DATE_FORMAT(date, '%M') AS theMonth,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', heat_runtime, 0))/60,0) AS heatHours12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', heat_runtime, 0))/60,0) AS heatHours13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', heat_runtime, 0))/60,0) AS heatHours14,

ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', cool_runtime, 0))/60,0) AS coolHours12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', cool_runtime, 0))/60,0) AS coolHours13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', cool_runtime, 0))/60,0) AS coolHours14

FROM {$database->table_prefix}run_times
WHERE thermostat_id = ?
GROUP BY 1
ORDER BY 1";
//$queryRunTimes = $pdo->prepare( $sql );
//$queryRunTimes->execute( array( $thermostat_id ) );

// Perhaps count the rows and if fewer than 24 then give message like "not enough data, be patient grasshopper"?

$sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS date,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', avgTempHDD, 0)),1) AS hdd12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', avgTempHDD, 0)),1) AS hdd13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', avgTempHDD, 0)),1) AS hdd14,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', avgTempCDD, 0)),1) AS cdd12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', avgTempCDD, 0)),1) AS cdd13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', avgTempCDD, 0)),1) AS cdd14
FROM (
SELECT date_format(date, '%Y-%m-%d') AS date,
IF( AVG(outdoor_temp) <= $hddBase, $hddBase - AVG(outdoor_temp), 0 ) AS avgTempHDD,
IF( AVG(outdoor_temp) >= $cddBase, AVG(outdoor_temp) - $cddBase, 0 ) AS avgTempCDD
FROM {$database->table_prefix}run_times
WHERE thermostat_id = ?
GROUP BY DATE_FORMAT(date, '%Y-%m-%d') ) derivedTemperatures
GROUP BY 1
";
//$queryDegreeDays = $pdo->prepare( $sql );
//$queryDegreeDays->execute( array( $thermostat_id ) );

/**
  * To compute degree days....
  *
  * For heating degree days
  *  1. Determine average temperature for the whole day
  *  2. For each average in excess of 65 degrees F, count only those degrees above 65
  *  3. Sum those degrees for the month
  * For cooling degree days
  *  1. Determine average temperature for the whole day
  *  2. For each average below 65 degrees F, count only those degrees below 65
  *  3. Sum those degrees for the month
  *
  * To use these computed numbers.
  *  Compare the the heating degree days for a given month on two successive years to see which is
  *  the hotter month.
  *  Examine the HVAC runtime in hours for that same month on those two years.
  *  If all things are equal, the run time for a given number of degrees will remain the same.
  *  If your run time begins to increase then you may need to tune/refill/maintain your unit.
  *
  *  This is also very handy if one year your electricity bill is much higher, you can see if the
  *  Summer was very much hotter.
  *
  *  65 is the magic number in the US.  Your number may vary.  Your number WILL vary.
  *  http://www.degreedays.net/introduction
  *  See particularly the section about determining your own numbers
  *
  */

$giantSQL = "SELECT
  t1.theMonthNumber,
  t1.theMonth AS theMonth,
  heatHours12, heatHours13, heatHours14,
  coolHours12, coolHours13, coolHours14
  hdd12, hdd13, hdd14,
  cdd12, cdd13, cdd14
FROM
  (
    SELECT
      DATE_FORMAT(rt.date, '%m') AS theMonthNumber,
      DATE_FORMAT(rt.date, '%M') AS theMonth,
      ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2012', rt.heat_runtime, 0))/60,0) AS heatHours12,
      ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2013', rt.heat_runtime, 0))/60,0) AS heatHours13,
      ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2014', rt.heat_runtime, 0))/60,0) AS heatHours14,
      ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2012', rt.cool_runtime, 0))/60,0) AS coolHours12,
      ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2013', rt.cool_runtime, 0))/60,0) AS coolHours13,
      ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2014', rt.cool_runtime, 0))/60,0) AS coolHours14
    FROM {$database->table_prefix}run_times rt
    WHERE thermostat_id = ?
    GROUP BY 1, 2
  ) t1,
  (
    SELECT
      DATE_FORMAT(dt.date, '%m') AS theMonthNumber,
      DATE_FORMAT(dt.date, '%M') AS theMonth,
      ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2012', dt.avgTempHDD, 0)),1) AS hdd12,
      ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2013', dt.avgTempHDD, 0)),1) AS hdd13,
      ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2014', dt.avgTempHDD, 0)),1) AS hdd14,
      ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2012', dt.avgTempCDD, 0)),1) AS cdd12,
      ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2013', dt.avgTempCDD, 0)),1) AS cdd13,
      ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2014', dt.avgTempCDD, 0)),1) AS cdd14
    FROM
    (
      SELECT
        date_format(t.date, '%Y-%m-%d') AS date,
        IF( AVG(t.outdoor_temp) <= {$hddBase}, {$hddBase} - AVG(t.outdoor_temp), 0 ) AS avgTempHDD,
        IF( AVG(t.outdoor_temp) >= {$cddBase}, AVG(t.outdoor_temp) - {$cddBase}, 0 ) AS avgTempCDD
      FROM {$database->table_prefix}thermostat_data t
      WHERE thermostat_id = ?
      GROUP BY DATE_FORMAT(date, '%Y-%m-%d')
    ) dt
  GROUP BY 1, 2
  ) t2
WHERE
    t1.theMonthNumber = t2.theMonthNumber
AND t1.theMonth = t2.theMonth
";

$queryGiant = $pdo->prepare( $giantSQL );
$queryGiant->execute( array( $thermostat_id, $thermostat_id ) );

$allData = $queryGiant->fetchAll( PDO::FETCH_OBJ );

$answer = array();
$answer[ 'allData' ] = $allData;
echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );


$util::logInfo( 'compare_dl: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>