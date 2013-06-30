<?php
require_once 'common.php';

$returnString = "";

$lastZIP = '';

/** Get stat info
	*
	*/
try
{
	foreach( $thermostats as $thermostatRec )
	{
		$lockFileName = $lockFile . $thermostatRec['id'];
		$lock = @fopen( $lockFileName, 'w' );
		if( !$lock )
		{
			error( "get_instant_status: Could not write to lock file $lockFileName" );
			continue;
		}
		if( flock($lock, LOCK_EX) )
		{
			logIt( "get_instant_status: Connecting to Thermostat ID = ({$thermostatRec['id']})  uuid  = ({$thermostatRec['tstat_uuid']}) ip = ({$thermostatRec['ip']}) name = ({$thermostatRec['name']})" );

			$stat = new Stat( $thermostatRec['ip'] );

			$statData = $stat->getStat();
			$heatStatus = ($stat->tstate == 1) ? 'on' : 'off';
			$coolStatus = ($stat->tstate == 2) ? 'on' : 'off';
			$fanStatus  = ($stat->fstate == 1) ? 'on' : 'off';
			// Not sure why, but this just is not working.  The t_heat and t_cool are coming back blank
			//$setPoint   = ' and the target is ' . (string)(($stat->tstate == 1) ? $stat->t_heat : $stat->_t_cool);
			$setPoint = '';

			/** Get environmental info
				*
				*/
			try
			{
				if( $lastZIP != $ZIP )
				{	// Only get outside info for subsequent locations if the location has changed
					$lastZIP = $ZIP;

					$externalWeatherAPI = new ExternalWeather( $weatherConfig );
					$outsideData = $externalWeatherAPI->getOutdoorWeather( $ZIP );
					$outdoorTemp = $outsideData['temp'];
					$outdoorHumidity = $outsideData['humidity'];
					logIt( "get_instant_status: Outside Weather for {$ZIP}: Temp $outdoorTemp Humidity $outdoorHumidity" );
				}

				//$returnString = $returnString . "<p>Right now at ".date('H:i', time())." the outside temperature for $thermostatRec[name] is $outdoorTemp &deg;$weatherConfig[units]</p>";
				// Change to display using the thermostats own time.
				$returnString = $returnString . "<p>At $thermostatRec[name] it's $stat->time and $outdoorTemp &deg;$weatherConfig[units] outside and $stat->temp &deg;$weatherConfig[units] inside.</p>";
			}
			catch( Exception $e )
			{
				doError( 'External weather failed: ' . $e->getMessage() );
				// Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
				$returnString = $returnString . "<p><img src='images/Alert.png'/>Presently unable to read outside information.</p>";
				$returnString = $returnString . "<p>$thermostatRec[name] says it is $stat->time</p>";
			}
			$returnString = $returnString .  "<p><img src='images/img_trans.gif' width='1' height='1' class='heater_$heatStatus' /> The heater is $heatStatus".(($heatStatus == 'on') ? "$setPoint" : '').'.</p>';
			$returnString = $returnString .  "<p><img src='images/img_trans.gif' width='1' height='1' class='compressor_$coolStatus' /> The compressor is $coolStatus".(($coolStatus == 'on') ? "$setPoint" : '').'.</p>';
			$returnString = $returnString .  "<p><img src='images/img_trans.gif' width='1' height='1' class='fan_$fanStatus' /> The fan is $fanStatus</p>";
		}
		fclose( $lock );
	}

}
catch( Exception $e )
{
	doError( 'Thermostat failed: ' . $e->getMessage() );
}




echo $returnString;

?>