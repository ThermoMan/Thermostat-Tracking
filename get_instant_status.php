<?php
require_once 'common.php';

/** When this function is called properly, the $userThermostats will NOT be pre-populated because it's not part of the same session.
	*
	* Or is it?
	*
	*/

$returnString = '';

$lastZIP = '';

/*
ob_start();
var_dump($_POST);
$result = ob_get_clean();
$log->logInfo( 'POST ' . $result );

ob_start();
var_dump($_GET);
$result = ob_get_clean();
$log->logInfo( 'GET ' . $result );
*/

/*
ob_start();
var_dump($_SESSION);
$result = ob_get_clean();
$log->logInfo( '_SESSION ' . $result );
*/

/*
Need to compare who the user claims to be (via his session ID) and his IP address with what the user log in DB says is the present state

If he is properly logged in, then and only htn look up the data requested and return it.

Otherwise fail silently (or maybe send back an alert icon with an error "not authorized for this content")


*/


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
			logIt( "get_instant_status: Could not write to lock file $lockFileName" );
			continue;
		}
		$setPoint = '';

		if( flock($lock, LOCK_EX) )
		{
			$log->logInfo( "get_instant_status: Connecting to Thermostat ID = ({$thermostatRec['id']})  uuid  = ({$thermostatRec['tstat_uuid']}) ip = ({$thermostatRec['ip']}) name = ({$thermostatRec['name']})" );

			//$stat = new Stat( $thermostatRec['ip'], $thermostatRec['tstat_id'] );
			$stat = new Stat( $thermostatRec['ip'] );

			$statData = $stat->getStat();
			}
			catch( Exception $e )
			{
				$log->logInfo( 'get_instant_status: $stat->getStat() threw an unpleasant error and could not talk to the stat' );
			}
			$heatStatus = ($stat->tstate == 1) ? 'on' : 'off';
			$coolStatus = ($stat->tstate == 2) ? 'on' : 'off';
			// (later?) If any of the the devices are on ask the DB how long they have been running (in hours:minutes)

			$fanStatus  = ($stat->fstate == 1) ? 'on' : 'off';
			$setPoint   = ' The target is ' . (string)(($stat->tstate == 1) ? $stat->t_heat : $stat->t_cool);

			/** Get outside info
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
					$log->logInfo( "get_instant_status: Outside Weather for {$ZIP}: Temp $outdoorTemp Humidity $outdoorHumidity" );
				}

				//$returnString = $returnString . "<p>Right now at ".date('H:i', time())." the outside temperature for $thermostatRec[name] is $outdoorTemp &deg;$weatherConfig[units]</p>";
				// Change to display using the thermostats own time.
				$returnString = $returnString . "<p>At $thermostatRec[name] it's $stat->time and $outdoorTemp &deg;$weatherConfig[units] outside and $stat->temp &deg;$weatherConfig[units] inside.</p>";

				$returnString = $returnString . "<p><img src='images/img_trans.gif' width='1' height='1' class='heater_$heatStatus'     alt='heat' title='The heater is $heatStatus' /> The heater is $heatStatus.".(($heatStatus == 'on') ? "$setPoint" : '').'</p>';
				$returnString = $returnString . "<p><img src='images/img_trans.gif' width='1' height='1' class='compressor_$coolStatus' alt='cool' title='The compressor is $coolStatus' /> The compressor is $coolStatus.".(($coolStatus == 'on') ? "$setPoint" : '').'</p>';
				$returnString = $returnString . "<p><img src='images/img_trans.gif' width='1' height='1' class='fan_$fanStatus' alt='fan' title='The fan is $fanStatus'/> The fan is $fanStatus.</p>";
			}
			catch( Exception $e )
			{
				$log->logError( 'External weather failed: ' . $e->getMessage() );
				// Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
				$returnString = $returnString . "<p><img src='images/Alert.png'/ alt='alert'>Presently unable to read outside information.</p>";
				$returnString = $returnString . "<p>$thermostatRec[name] says it is $stat->time</p>";
			}
		}
		fclose( $lock );
	}
}
catch( Exception $e )
{
	$log->logError( 'Thermostat failed: ' . $e->getMessage() );
	// die();
	$returnString = "<p>No response from unit, please check WiFi connection at unit location.";
}



// Need to JSON the text so that there is an object with values passed back?

echo $returnString;

?>