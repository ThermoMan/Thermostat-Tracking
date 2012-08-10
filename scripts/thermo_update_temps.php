<?php
require "../lib/t_lib.php";
require "../config.php";

$link = mysql_connect( $host, $user, $pass );
if( !$link )
{
  die( "Could not connect: " . mysql_error() );
}



mysql_select_db( $db, $link ) or die( "cannot select DB" );            // Really should log this?

$stat = new Stat( $thermostatIP, $ZIP );

$stat->getStat();
$outside = $stat->getOutdoorTemp();

// Log the indoor and outdoor temperatures for this half-hour increment
$target = $stat->t_cool;
if( $stat->tmode == 1 )
{
  $target = $stat->t_heat;
}
$sql = "INSERT INTO " . $table_prefix . "temperatures ( date, indoor_temp, outdoor_temp, set_point ) VALUES ( CONCAT( SUBSTR( NOW() , 1, 17 ) , \"00\" ) , " . $stat->temp . ", " . $outside . ", " . $target ." )";



//echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
//echo "Result is: " . $result;

$stat->getDataLog();
// Log the runtimes for yesterday and today

// Remove yesterdays work in progress
$sql = "DELETE FROM " . $table_prefix . "run_times WHERE date = CURDATE()";
//echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
//echo "Result is: " . $result;

// Add todays present accumulated run time
$sql = "INSERT INTO " . $table_prefix . "run_times (date, heat_runtime, cool_runtime ) VALUES ( CURDATE(), ".$stat->runTimeHeat.", ".$stat->runTimeCool." )";
//echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
//echo "Result is: " . $result;

// Remove yesterdays total (if present)
$sql = "DELETE FROM " . $table_prefix . "run_times WHERE date = CURDATE() -  INTERVAL 1 DAY";
//echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
//echo "Result is: " . $result;

// Add yesterdays total
$sql = "INSERT INTO " . $table_prefix . "run_times (date, heat_runtime, cool_runtime ) VALUES ( CURDATE()- INTERVAL 1 DAY, ".$stat->runTimeHeatYesterday.", ".$stat->runTimeCoolYesterday." )";
//echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
//echo "Result is: " . $result;

mysql_close( $link );

?>
