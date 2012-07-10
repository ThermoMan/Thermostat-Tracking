<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

$link = mysql_connect( $host, $user, $pass );
if( !$link )
{
  die( "Could not connect: " . mysql_error() );
}



mysql_select_db( $db, $link ) or die( "cannot select DB" );            // Really should log this?

$stat = new Stat( $thermostatIP, $ZIP );

$stat->getTemp();
$outside = $stat->getOutdoorTemp();
// Log the indoor and outdoor temperatures for this half-hour increment
$sql = "INSERT INTO thermo.temperatures ( date, indoor_temp, outdoor_temp ) VALUES ( concat( substr( now( ) , 1, 17 ) , \"00\" ) , ".$stat->temp.", ".$outside.")";
// echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
// echo "Result is: " . $result;

$stat->getDataLog();
// Log the runtimes for yesterday and today
$sql = "CALL save_runtime( ".$stat->runTimeHeat.", ".$stat->runTimeCool.", ".$stat->runTimeHeatYesterday.", ".$stat->runTimeCoolYesterday." )";
// echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
// echo "Result is: " . $result;


mysql_close( $link );

?>