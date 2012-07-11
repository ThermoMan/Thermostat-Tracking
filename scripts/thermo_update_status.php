<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

// session_start();

function session_upkeep( $table_prefix )
{
	$sql = "select date, heat_status, cool_status, fan_status from " . $table_prefix . "hvac_status order by date asc";
//echo "The SQL is $sql";
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

				$sql_heat = "insert into " . $table_prefix . "hvac_cycles (system, start_time, end_time) values (".$heat_system.", \"".$heat_start."\", \"".$heat_end."\")";
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
//			echo "<br>Cool is presently on";
			if( $row['cool_status'] == 0 )
			{
//				echo " | Cool turning off";
				$cool_on = 0;
				$cool_end = $row['date'];

				$sql_cool = "insert into " . $table_prefix . "hvac_cycles (system, start_time, end_time) values (".$cool_system.", \"".$cool_start."\", \"".$cool_end."\")";
//				echo "(SQL = $sql_cool)";
				mysql_query( $sql_cool );
			}
//			else
//			{
//				echo " | Cool staying on";
//			}
		}

		if( $cool_on == 0 )
		{
//			echo "<br>Cool is presently off";
			if( $row['cool_status'] == 1 )
			{
//				echo " | Cool turning on";
				$cool_on = 1;
				$cool_start = $row['date'];
			}
//			else
//			{
//				echo " | Cool staying off";
//			}
		}

		if( $fan_on == 1 )
		{
			if( $row['fan_status'] == 0 )
			{
				$fan_on = 0;
				$fan_end = $row['date'];

				$sql_fan = "insert into " . $table_prefix . "hvac_cycles (system, start_time, end_time) values (".$fan_system.", \"".$fan_start."\", \"".$fan_end."\")";
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

	// This cleanup code is dangerous and requires that this routine run while no part of the HVAC is running
	// Consider this.  If the fan is still on after the compressor has quit.  All of the run records that are part of
	// the fan's current run session will be deleted, leaving an inital status of running creating a bad situation
	// for the next time this script runs.
	$sql = "delete from " . $table_prefix . "hvac_status where date <= (select max(end_time) from " . $table_prefix . "hvac_cycles)";
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

//echo "Here is the SQL: " . $sql;
$result = mysql_query( $sql );
//echo "Result is: " . $result;

if( ($heat_status + $cool_status + $fan_status) == 0 )
{ // Only when EVERYTHING is off can the upkeep scripts be run.
//  echo "Doing upkeep";
  session_upkeep( $table_prefix );  // Not sure why, but subroutine thinks this variable is undefined.
}



mysql_close( $link );

?>