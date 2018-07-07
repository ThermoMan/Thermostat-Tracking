<?php
/**
  * This guy needs some work.  It mixes up the MVC pretty badly.
  * This code should be considered 'M' since it fetches the actual data.
  * But it is acting like 'V' when it creates a reply that includes HTML.
  * It really ought to package the info up into some JSON like structure and return that to the caller in case the caller is not a web page.
  */
$start_time = microtime( true );
require_once( 'common.php' );
require_once( 'user.php' );

$util::logDebug( 'get_instant_status: 0' );

$returnString = '';
$greetingMsg = '';
$greetingMsgWeather = '';

$lastZIP = '';

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
//QQQ Do a test...  Log in, then with phpMyAdmin mess up the session id.  Then hit Refresh button on the dashboard.
//QQQ The expected result is that you'll be denied access.
$user = new USER( $uname, $session );
if( ! $util::checkThermostat( $user ) ){
  echo 'You have no thermostats added to your account.';
  return;
}

/** Get thermostat info
  *
  */
$util::logDebug( 'get_instant_status: 1' );
try{
//$util::logDebug( 'get_instant_status: 2 util root dir is ' . $util::$rootDir );
  foreach( $user->thermostats as $thermostatRec ){
    $lockFileName = $lockFile . $thermostatRec['thermostat_id'];
    $lock = @fopen( $lockFileName, 'w' );
    if( !$lock ){
      $util::logError( "indoor_temps: Could not write to lock file $lockFileName" );
      continue;
    }

    $setPoint = '';

    if( flock( $lock, LOCK_EX ) ){
$util::logInfo( "get_instant_status: Connecting to Thermostat ID = ({$thermostatRec['thermostat_id']})  uuid  = ({$thermostatRec['tstat_uuid']}) ip = ({$thermostatRec['ip']}) name = ({$thermostatRec['name']})" );

      //$stat = new Stat( $thermostatRec['ip'], $thermostatRec['thermostat_id'] );
      //$stat = new Stat( $thermostatRec['ip'] );
      $stat = new Stat( $thermostatRec );
$util::logDebug( 'get_instant_status: 5' );

      try{
$util::logInfo( 'get_instant_status: Trying to talk to thermostat' );
        $statData = $stat->getStat();
      }
      catch( Exception $e ){
$util::logError( 'get_instant_status: $stat->getStat() threw an unpleasant error and could not talk to the stat' );
      }
      $heatStatus = ($stat->tstate == 1) ? 'on' : 'off';
      $coolStatus = ($stat->tstate == 2) ? 'on' : 'off';
      // (later?) If any of the the devices are on ask the DB how long they have been running (in hours:minutes)

      $fanStatus  = ($stat->fstate == 1) ? 'on' : 'off';
      $setPoint   = ' The target is ' . (string)(($stat->tstate == 1) ? $stat->t_heat : $stat->t_cool);

      $greetingMsg = "<p>At $thermostatRec[name] ";

      /** Get outside info
        *
        */
      try{
        if( $lastZIP != $ZIP ){
          // Only get outside info for subsequent locations if the location has changed
          $lastZIP = $ZIP;

          $externalWeatherAPI = new ExternalWeather( $weatherConfig );
          $outsideData = $externalWeatherAPI->getOutdoorWeather( $ZIP );
          $outdoorTemp = $outsideData['temp'];
          $outdoorHumidity = $outsideData['humidity'];
$util::logInfo( "get_instant_status: Outside Weather for {$ZIP}: Temp $outdoorTemp Humidity $outdoorHumidity" );
          //$returnString = $returnString . "<p>At $thermostatRec[name] it's $stat->time and $outdoorTemp &deg;$weatherConfig[units] outside and $stat->temp &deg;$weatherConfig[units] inside.</p>";
          $greetingMsgWeather = "$outdoorTemp &deg;$weatherConfig[units] outside";
        }
      }
      catch( Exception $e ){
$util::logError( 'External weather failed: ' . $e->getMessage() );
        // Need to add the Alert icon to the sprite map and set relative position in the thermo.css file
        $returnString = $returnString . "<p><img src='images/Alert.png'/ alt='alert'>Presently unable to read outside information.</p>";
        $greetingMsgWeather = "<p><img src='images/Alert.png'/ alt='alert'>Presently unable to read outside information.</p>";
        //$returnString = $returnString . "<p>$thermostatRec[name] says it is $stat->time</p>";
      }

      if( $stat->connectOK == 0 ){
        // If we did talk to the thermostat
        //$returnString = $returnString . "<p>At $thermostatRec[name] it's $stat->time and $outdoorTemp &deg;$weatherConfig[units] outside and $stat->temp &deg;$weatherConfig[units] inside.</p>";
        $returnString = $returnString . $greetingMsg . "it's $stat->time and " . $greetingMsgWeather . " and $stat->temp &deg;$weatherConfig[units] inside.</p>";

        $returnString = $returnString . "<p><img src='images/img_trans.gif' width='1' height='1' class='large_sprite heater_$heatStatus'     alt='heat' title='The heater is $heatStatus' /> The heater is $heatStatus.".(($heatStatus == 'on') ? "$setPoint" : '').'</p>';
        $returnString = $returnString . "<p><img src='images/img_trans.gif' width='1' height='1' class='large_sprite compressor_$coolStatus' alt='cool' title='The compressor is $coolStatus' /> The compressor is $coolStatus.".(($coolStatus == 'on') ? "$setPoint" : '').'</p>';
        $returnString = $returnString . "<p><img src='images/img_trans.gif' width='1' height='1' class='large_sprite fan_$fanStatus'         alt='fan'  title='The fan is $fanStatus'/> The fan is $fanStatus.</p>";
      }
      else{
        // If we could not talk to the thermostat
        $returnString = $returnString . $greetingMsg . date('H:i', time()) . ' and ' . $greetingMsgWeather . ' and presently unable to communicate with the thermostat.</p>';
      }

    }
    fclose( $lock );
  }
}
catch( Exception $e ){
  $util::logError( 'get_instant_status: Thermostat failed: ' . $e->getMessage() );
  $returnString = "<p>No response from unit, please check WiFi connection at unit location.";
}



// Need to JSON the text so that there is an object with values passed back?

echo $returnString;
$util::logInfo( 'get_instant_status: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>