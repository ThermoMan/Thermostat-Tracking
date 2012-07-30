<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

function session_upkeep()
{
  global $table_prefix;

  $sql = "SELECT date, heat_status, cool_status, fan_status FROM " . $table_prefix . "hvac_status ORDER BY date ASC";
	$result = mysql_query( $sql );


	$heat_system = 1;
	$cool_system = 2;
	$fan_system = 3;

	$heat_on = 0;
	$cool_on = 0;
	$fan_on = 0;
	while( $row = mysql_fetch_array( $result ) )
	{
		if( $heat_on == 1 )
		{
			if( $row['heat_status'] == 0 )
			{
				$heat_on = 0;
				$heat_end = $row['date'];

        $sql_heat = "INSERT INTO " . $table_prefix . "hvac_cycles (system, start_time, end_time) VALUES (".$heat_system.", \"".$heat_start."\", \"".$heat_end."\")";
				mysql_query( $sql_heat );
			}
		}

		if( $heat_on == 0 )
		{
			if( $row['heat_status'] == 1 )
			{
				$heat_on = 1;
				$heat_start = $row['date'];
			}
		}

		if( $cool_on == 1 )
		{
			if( $row['cool_status'] == 0 )
			{
				$cool_on = 0;
				$cool_end = $row['date'];

        $sql_cool = "INSERT INTO " . $table_prefix . "hvac_cycles (system, start_time, end_time) VALUES (".$cool_system.", \"".$cool_start."\", \"".$cool_end."\")";
				mysql_query( $sql_cool );
			}
		}

		if( $cool_on == 0 )
		{
			if( $row['cool_status'] == 1 )
			{
				$cool_on = 1;
				$cool_start = $row['date'];
			}
		}

		if( $fan_on == 1 )
		{
			if( $row['fan_status'] == 0 )
			{
				$fan_on = 0;
				$fan_end = $row['date'];

        $sql_fan = "INSERT INTO " . $table_prefix . "hvac_cycles (system, start_time, end_time) VALUES (".$fan_system.", \"".$fan_start."\", \"".$fan_end."\")";
				mysql_query( $sql_fan );
			}
		}

		if( $fan_on == 0 )
		{
			if( $row['fan_status'] == 1 )
			{
				$fan_on = 1;
				$fan_start = $row['date'];
			}
		}
	}

  /*
   * This cleanup code is dangerous and requires that this routine run while no part of the HVAC is running
   * Consider this.  If the fan is still on after the compressor has quit.  All of the run records that are part of
   * the fan's current run session will be deleted, leaving an inital status of running creating a bad situation
   * for the next time this script runs.
   */
  $sql = "DELETE FROM " . $table_prefix . "hvac_status WHERE date <= (SELECT MAX(end_time) FROM " . $table_prefix . "hvac_cycles)";
	$result = mysql_query( $sql );

}

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

// Log the runtimes for yesterday and today
$sql = "INSERT INTO " . $table_prefix . "hvac_status (date, heat_status, cool_status, fan_status ) VALUES ( concat( substr( now( ) , 1, 17 ) , \"00\" ) , ".$heat_status.", ".$cool_status.", ".$fan_status." )";

$result = mysql_query( $sql );

if( ($heat_status + $cool_status + $fan_status) == 0 )
{ // Only when EVERYTHING is off can the upkeep scripts be run.
  session_upkeep();
}

mysql_close( $link );

?>