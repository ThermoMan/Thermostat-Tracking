<?php
require(dirname(__FILE__).'/../common.php');

//touch( '/home/fratell1/freitag.theinscrutable.us/thermo2/scripts/thermo_update_temps.start' );
$today = date( 'Y-m-d' );
$yesterday = date( 'Y-m-d', strtotime( 'yesterday' ));

/**
	* This script updates the indoor and outdoor temperatures and today's and yesterday total run time for each thermostat
	*/

try
{
	$sql = "INSERT INTO {$dbConfig['table_prefix']}temperatures( tstat_uuid, date, indoor_temp, outdoor_temp, indoor_humidity, outdoor_humidity, set_point ) VALUES ( ?, CONCAT( SUBSTR( NOW() , 1, 15 ) , \"0:00\" ), ?, ?, ?, ?, ? )";
	$queryTemp = $pdo->prepare($sql);

	$sql = "DELETE FROM {$dbConfig['table_prefix']}run_times WHERE date = ? AND tstat_uuid = ?";
	$queryRunDelete = $pdo->prepare($sql);

	$sql = "INSERT INTO {$dbConfig['table_prefix']}run_times( tstat_uuid, date, heat_runtime, cool_runtime ) VALUES ( ?, ?, ?, ? )";
	$queryRunInsert = $pdo->prepare($sql);
}
catch( Exception $e )
{
	logIt( 'temps: DB Exception while preparing SQL: ' . $e->getMessage() );
	die();
}

$outdoorTemp = null;						// Default outside temp
$outdoorHumidity = null;				// Default outside humidity (in case not working with CT80 or similar unit)
try
{
	$externalWeatherAPI = new ExternalWeather( $weatherConfig );
	$outsideData = $externalWeatherAPI->getOutdoorWeather( $ZIP );
	$outdoorTemp = $outsideData['temp'];
	$outdoorHumidity = $outsideData['humidity'];
	logIt( "temps: Outside Weather for {$ZIP}: Temp $outdoorTemp Humidity $outdoorHumidity" );
}
catch( Exception $e )
{
	logIt( 'temps: External weather failed: ' . $e->getMessage() );
}

foreach( $thermostats as $thermostatRec )
{
	$lockFileName = $lockFile . $thermostatRec['id'];
	$lock = @fopen( $lockFileName, 'w' );
	if( !$lock )
	{
		logIt( "temps: Could not write to lock file $lockFileName" );
		continue;
	}

	if( flock( $lock, LOCK_EX ) )
	{
		try
		{
			// Query thermostat info
			$indoorHumidity = null;
			logIt( "temps: Connecting to {$thermostatRec['id']} {$thermostatRec['tstat_uuid']} {$thermostatRec['ip']} {$thermostatRec['name']}" );
			$stat = new Stat( $thermostatRec['ip'] );

			//$sysInfo = $stat->getSysInfo();
			$stat->getSysInfo();
			//$uuid = $sysInfo['uuid'];
			//sleep(2); // allow thermostat to catch up
			//$model = $stat->getModel();
			$stat->getModel();
			//sleep(2); // allow thermostat to catch up
			//$statData = $stat->getStat();
			$stat->getStat();
			//sleep(2); // allow thermostat to catch up


// Instead of asking the thermostat what his model is, rely upon the entry in the thermostat table
			if( strstr($stat->model, 'CT80') !== false )
			{ // Get indoor humidity for CT80
				//sleep(2); // let thermostat catch up
				//$indoorHumidity = $stat->getHumidity();
				$stat->getHumidity();
// Actually, won't this have come back from the getStat() call already if it's available????
			}

			// Log the indoor and outdoor temperatures for this half-hour increment
// t_heat or t_cool may not exist if thermostat is running in battery mode
			$target = ($stat->tmode == 1) ? $stat->t_heat : $stat->t_cool;

			logIt( "temps: Target $target" );
			logit( "temps: UUID $stat->uuid IT " . $stat->temp . " OT $outdoorTemp IH $stat->humidity OH $outdoorHumidity TARGT $target" );
			$queryTemp->execute(array( $stat->uuid, $stat->temp, $outdoorTemp, $stat->humidity, $outdoorHumidity, $target ) );

			//$runTimeData = $stat->getDataLog();
			$stat->getDataLog();

			// Need to verify success of thermostat query before deleting/inserting data...
			// Unless it throws an exception?

			// Remove zero or one rows for today and then insert one row for today.
			$queryRunDelete->execute( array($today, $stat->uuid) );
			logIt( "temps: Run Time Today - Inserting RTH {$stat->runTimeHeat} RTC {$stat->runTimeCool} U $stat->uuid T $today" );
			$queryRunInsert->execute( array($stat->uuid, $today, $stat->runTimeHeat, $stat->runTimeCool) );

			// Remove zero or one rows for yesterday and then insert one row for yesterday.
			$queryRunDelete->execute( array($yesterday, $stat->uuid) );
			logIt( "temps: Run Time Yesterday - Inserting RTH {$stat->runTimeHeatYesterday} RTC {$stat->runTimeCoolYesterday} U $stat->uuid T $yesterday" );
			$queryRunInsert->execute( array($stat->uuid, $yesterday, $stat->runTimeHeatYesterday, $stat->runTimeCoolYesterday) );
		}
		catch( Exception $e )
		{
			logIt( 'temps: Thermostat Exception: ' . $e->getMessage() );
		}
		flock( $lock, LOCK_UN );
	}
	else
	{
		logIt( "temps: Couldn't get file lock for thermostat {$thermostatRec['id']}" );
		die();
	}
	fclose($lock);
}

//touch( '/home/fratell1/freitag.theinscrutable.us/thermo2/scripts/thermo_update_temps.end' );
?>