<?php
require_once 'common.php';

/* Put usefuyl comments here and either merge code with get_instant_status,php or make this a virtual clone of that file */

$lastZIP = '';


/** Get stat info
	*
	*/
try
{
	foreach( $thermostats as $thermostatRec )
	{
		$returnString = '';
		$lockFileName = $lockFile . $thermostatRec['id'];
		$lock = @fopen( $lockFileName, 'w' );
		if( !$lock )
		{
			logIt( "get_instant_forecast: Could not write to lock file $lockFileName" );
			continue;
		}
		if( flock($lock, LOCK_EX) )
		{
			logIt( "get_instant_forecast: Getching forecast" );

			//$stat = new Stat( $thermostatRec['ip'], $thermostatRec['tstat_id'] );
			$stat = new Stat( $thermostatRec['ip'] );


			/** Get environmental info
				*
				*/
			try
			{
				if( $lastZIP != $ZIP )
				{	// Only get outside info for subsequent locations if the location has changed
					$lastZIP = $ZIP;

					$externalWeatherAPI = new ExternalWeather( $weatherConfig );

					$forecastData = $externalWeatherAPI->getOutdoorForcast( $ZIP );

					$returnString .= "<p>The forecast for {$ZIP} is</p><table><tr>";
					foreach( $forecastData as $day )
					{
						$returnString .= "<td style='text-align: center;'>" . $day->high->fahrenheit . "</td>";
					}
					$returnString .= "</tr><tr>";
					foreach( $forecastData as $day )
					{
						$returnString .= "<td style='text-align: center; padding: 10px;'><img src='" . $day->icon_url ."'></td>";
					}
					$returnString .= "</tr></table>";


					//logIt( "get_instant_forecast: I got data" );
				}

			}
			catch( Exception $e )
			{
				logIt( 'get_instant_forecast: External forecast failed: ' . $e->getMessage() );
				// Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
				$returnString = $returnString . "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
			}
		}
		fclose( $lock );
	}
}
catch( Exception $e )
{
	logIt( 'Thermostat failed: ' . $e->getMessage() );
	// die();
	$returnString = "<p>No response from unit, please check WiFi connection at unit location.";
}



// Need to JSON the text so that there is an object with values passed back

echo $returnString;

?>