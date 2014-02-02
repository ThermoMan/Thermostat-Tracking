<?php
$start_time = microtime(true);
require_once 'common.php';

$log->logInfo( 'get_instant_forecast: start' );

/* Put useful comments here and either merge code with get_instant_status.php or make this a virtual clone of that file */

$lastZIP = '';
$returnString = '';

if( $weatherConfig['useForecast'] )
{	// Only check forecast if we're asking for it.
	try
	{
	/** Get stat info
		*
		*/
	foreach( $thermostats as $thermostatRec )
	{
		$returnString = '';
				//$log->logInfo( "get_instant_forecast: Fetching forecast" );

			//$stat = new Stat( $thermostatRec['ip'], $thermostatRec['tstat_id'] );
			$stat = new Stat( $thermostatRec['ip'] );

			try
			{
				if( $lastZIP != $ZIP )
				{	// Only get outside info for subsequent locations if the location has changed
					$lastZIP = $ZIP;

					$externalWeatherAPI = new ExternalWeather( $weatherConfig );
						/** Bad code follows.
							* I'm directly loading the data structure from weatherunderground and I ought to be using my own structure
							*
							* So the following code is specific to one data supplier and not generic at all.
							* To fix it, I need to change not only this, but also ExternalWeather.php
							*/

					/** Get environmental info
						*
						*/
					$forecastData = $externalWeatherAPI->getOutdoorForcast( $ZIP );
						//$log->logInfo( "get_instant_forecast: I got data" );

					// Format data for screen presentation
						if( is_array( $forecastData ) )
						{
					$returnString .= "<p>The forecast for {$ZIP} is</p><br><table><tr>";
					foreach( $forecastData as $day )
					{
						$returnString .= "<td style='text-align: center;'>{$day->date->weekday}</td>";
					}
					$returnString .= "</tr><tr>";
					foreach( $forecastData as $day )
					{
								$returnString .= "<td style='text-align: center; width: 90px;'><img src='$day->icon_url' alt='$day->icon' title='$day->conditions'></td>";
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
							$returnString .= '</tr></table>';
						}
						else
						{
							$log->logError( 'Expected to get an array back from $externalWeatherAPI->getOutdoorForcast( $ZIP ) but did not.' );
							$returnString .= 'No response from forecast provider.';
						}
				}

			}
			catch( Exception $e )
			{
					$log-logError( 'get_instant_forecast: External forecast failed: ' . $e->getMessage() );
				// Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
				$returnString = $returnString . "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
			}
		}
	}
	catch( Exception $e )
	{
		$log->logError( 'get_instant_forecast: Some bugs failure or other ' . $e->getMessage() );
	$returnString = "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
	}
}
echo $returnString;
$log->logInfo( 'get_instant_forecast: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>