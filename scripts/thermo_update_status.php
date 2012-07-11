<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

// session_start();

$link = mysql_connect( $host, $user, $pass );
if( !$link )
{
  die( "Could not connect: " . mysql_error() );
}

mysql_select_db( $db, $link ) or die( "cannot select DB" );            // Really should log this?

$stat = new Stat( $thermostatIP, $ZIP );

$stat->getStat();

$heat_status = 0;
$cool_status = 0;
if( $stat->tstate == 1 )
{
  $heat_status = 1;
}
else if( $stat->tstate == 2 )
{
  $cool_status = 1;
}

$fan_status = $stat->fstate;

//if( ($heat_status + $cool_status + $fan_status) > 0 )
//{ // Only log the results if any of them have activity.  This saves space in the DB

  // Log the runtimes for yesterday and today
  $sql = "INSERT INTO " . $table_prefix . "hvac_status (date, heat_status, cool_status, fan_status ) VALUES ( concat( substr( now( ) , 1, 17 ) , \"00\" ) , ".$heat_status.", ".$cool_status.", ".$fan_status." )";

  //echo "Here is the SQL: " . $sql;
  $result = mysql_query( $sql );
  //echo "Result is: " . $result;
//}

// Only when EVERYTHING is off can the upkeep scripts be run.

mysql_close( $link );

?>