<?php
require_once 'common.php';

/* Put useful comments here and either merge code with get_instant_status.php or make this a virtual clone of that file */

$lastZIP = '';


try
{
	/** Get stat info
		*
		*/
	foreach( $thermostats as $thermostatRec )
	{
		$returnString = '';
		/* DO NOT NEED TO GET LOCK, NOT TALKING TO THERMOSTAT!
		$lockFileName = $lockFile . $thermostatRec['id'];
		$lock = @fopen( $lockFileName, 'w' );
		if( !$lock )
		{
			logIt( "get_instant_forecast: Could not write to lock file $lockFileName" );
			continue;
		}
		if( flock($lock, LOCK_EX) )
		{
		*/
			//logIt( "get_instant_forecast: Fetching forecast" );

			//$stat = new Stat( $thermostatRec['ip'], $thermostatRec['tstat_id'] );
			$stat = new Stat( $thermostatRec['ip'] );

			try
			{
				if( $lastZIP != $ZIP )
				{	// Only get outside info for subsequent locations if the location has changed
					$lastZIP = $ZIP;

					$externalWeatherAPI = new ExternalWeather( $weatherConfig );

					/** Get environmental info
						*
						*/
					$forecastData = $externalWeatherAPI->getOutdoorForcast( $ZIP );
					//logIt( "get_instant_forecast: I got data" );

					// Format data for screen presentation
					$returnString .= "<p>The forecast for {$ZIP} is</p><br><table><tr>";
					foreach( $forecastData as $day )
					{
						$returnString .= "<td style='text-align: center;'>{$day->date->weekday}</td>";
					}
					$returnString .= "</tr><tr>";
					foreach( $forecastData as $day )
					{
						$returnString .= "<td style='text-align: center; width: 90px;'><img src='" . $day->icon_url ."'></td>";
					}
					$returnString .= "</tr><tr>";
					foreach( $forecastData as $day )
					{
						if( $weatherConfig[units] == 'C' )
						{
							$tth = $day->high->celsius;
							$ttl = $day->low->celsius;
						}
						else
						{	// If it's not C assume it is F (what, you want Kelvin or Rankine?)
							$tth = $day->high->fahrenheit;
							$ttl = $day->low->fahrenheit;
					}

						$returnString .= "<td style='text-align: center;'>$tth&deg;$weatherConfig[units] / $ttl&deg;$weatherConfig[units]</td>";
					}
					$returnString .= "</tr></table>";
				}

			}
			catch( Exception $e )
			{
				logIt( 'get_instant_forecast: External forecast failed: ' . $e->getMessage() );
				// Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
				$returnString = $returnString . "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
			}
		/* DO NOT NEED TO GET LOCK, NOT TALKING TO THERMOSTAT!
		}
		fclose( $lock );
		*/
	}
}
catch( Exception $e )
{
	logIt( 'get_instant_forecast: Some bugs failure or other ' . $e->getMessage() );
	$returnString = "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
}

echo $returnString;

?>