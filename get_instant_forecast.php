<?php
$start_time = microtime(true);
require_once( 'common.php' );
require_once( 'user.php' );

$util::logError( 'Forecast is DISABLED for now.' );
$returnString = "<p><img src='images/Alert.png'/>Forecast is presently disabled.</p>";
echo $returnString;
exit;

//$util::logDebug( '0' );

/* Put useful comments here and either merge code with get_instant_status.php or make this a virtual clone of that file */

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
$user = new USER( $uname, $session );
if( ! $util::checkThermostat( $user ) ) return;

$lastZIP = '';
$returnString = '';

if( $weatherConfig[ 'useForecast' ] ){
  // Only check forecast if we're asking for it.
  try{
    /** Get stat info
      *
      */
    foreach( $user->thermostats as $thermostatRec ){
      // This loop really ought to be per location, not per thermostat!

// QQQ error here!
//      $returnString = '';

      try{
//$util::logDebug( '1 zipcode = ' . $thermostatRec['location_string'] );
        if( $lastZIP != $thermostatRec['location_string'] ){
          // Only get outside info for subsequent locations if the location has changed
          $lastZIP = $thermostatRec['location_string'];

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
//$util::logDebug( '2' );
          $forecastData = $externalWeatherAPI->getOutdoorForecast( $lastZIP, $util );
//$util::logDebug( '3' );

          // Format data for screen presentation
          if( is_array( $forecastData ) ){
            $returnString .= "<p>The forecast for {$lastZIP} is</p><br><table><tr>";
            foreach( $forecastData as $day ){
              $returnString .= "<td style='text-align: center;'>{$day->date->weekday}</td>";
            }
            $returnString .= "</tr><tr>";
            foreach( $forecastData as $day ){
              $returnString .= "<td style='text-align: center; width: 90px;'><img src='$day->icon_url' alt='$day->icon' title='$day->conditions'></td>";
            }
            $returnString .= "</tr><tr>";
            foreach( $forecastData as $day ){
              if( $weatherConfig[units] == 'C' ){
                $tth = $day->high->celsius;
                $ttl = $day->low->celsius;
              }
              else{
                // If it's not C assume it is F (what, you want Kelvin or Rankine?)
                $tth = $day->high->fahrenheit;
                $ttl = $day->low->fahrenheit;
              }

              $returnString .= "<td style='text-align: center;'>$tth&deg;$weatherConfig[units] / $ttl&deg;$weatherConfig[units]</td>";
            }
            $returnString .= '</tr></table>';
          }
          else{
            $util::logError( 'Expected to get an array back from $externalWeatherAPI->getOutdoorForcast( $lastZIP ) but did not.  $lastZIP is [[[' . $lastZIP . ']]]' );
//$util::logInfo( 'return data is [[[' . $forecastData . ']]]' );
            $returnString .= 'No response from forecast provider.';
          }
        }

      }
      catch( Exception $e ){
        $util::logError( 'External forecast failed: ' . $e->getMessage() );
        // Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
        $returnString = $returnString . "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
      }
    }
  }
  catch( Exception $e ){
    $util::logError( 'Some bugs failure or other ' . $e->getMessage() );
    $returnString = "<p><img src='images/Alert.png'/>Presently unable to read forecast.</p>";
  }
}
// This is a little hacky, but change all http to https
$returnString = str_replace('http://', 'https://', $returnString );
echo $returnString;
//$util::logDebug( 'execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>