<?php
// QQQ Put this line at the top of every file that must only appear as an include ??
//if( $REQUEST_URL == $URL_OF_CURRENT_PAGE ){ http_response_code(404); die(); }

$start_time = microtime( true );
require_once( 'common.php' );
require_once( 'user.php' );

//$util::logDebug( '0' );

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
//QQQ Do a test...  Log in, then with phpMyAdmin mess up the session id.  Then hit Refresh button on the dashboard.
//QQQ The expected result is that you'll be denied access.
$user = new USER( $uname, $session );
if( ! $util::checkThermostat( $user ) ){
  echo 'You have no thermostats added to your account.';
  return;
}

//
//$util::logInfo( '$user is ' . print_r($user, true) );

// Bandaid to keep things moving
$database = new Database();
$pdo = $database->dbConnection();

  // Find the most recent stored temperature for all the thermostats for the present user.
  $sql = "
  SELECT loc.location_string
        ,stat.name AS thermostat_name
        ,MAX(data.date) AS last_contact
    FROM {$database->table_prefix}users AS user
        ,{$database->table_prefix}thermostats AS stat
        ,{$database->table_prefix}thermostat_data AS data
        ,{$database->table_prefix}locations AS loc
   WHERE user.user_name = :uname
     AND stat.user_id = user.user_id
     AND data.thermostat_id = stat.thermostat_id
     AND loc.location_id = stat.location_id
GROUP BY stat.name
         ,loc.location_string
ORDER BY loc.location_string ASC";
//$util::logInfo( '$sql ' . $sql );

  $stmt = $pdo->prepare( $sql );
  $stmt->bindParam( ':uname', $uname );
  $stmt->execute();

  $allThermostats = $stmt->fetchAll( PDO::FETCH_ASSOC );

  $last_read_dates = array();
  foreach( $allThermostats as $thermostatRec ){
    $last_read_dates[ $thermostatRec[ 'location_string' ] ][ $thermostatRec[ 'thermostat_name' ] ] = $thermostatRec[ 'last_contact' ];
  }
//$util::logInfo( '$last_read_dates is ' . print_r($last_read_dates, true) );


$oldLocString = '';
$locations = array();
try{

  foreach( $user->thermostats as $thermostat ){
    // This is a list of thermostats ordered by location_string
    if( $oldLocString != $thermostat["location_string"] ){
      $oldLocString = $thermostat["location_string"];
      $loc = array();
      $thermostats = array();
      $loc['name'] = $oldLocString;
// No, do not code outdoor temp in this data structure!
//      $loc['temperature'] = 95.5; // QQQ Need to look up actual external temperature here.
    }

    try{
      $tStat = new Stat( $thermostat );

// When location and thermostat match, add the last conftact date (do this even if no lock)
$last_read_date = '2018-08-26 14:00';
$last_read_date = $last_read_dates[ $oldLocString ][ $thermostat['name'] ];
$util::logInfo( '$last_read_date is ' . $last_read_date );

      if( $tStat->getLock() ){
//$util::logDebug( 'I got lock' );
        $statData = $tStat->getStat();

        $heatStatus = ($tStat->tstate == 1) ? 'on' : 'off';
        $coolStatus = ($tStat->tstate == 2) ? 'on' : 'off';
        // (later?) If any of the the devices are on ask the DB how long they have been running (in hours:minutes)
        $fanStatus  = ($tStat->fstate == 1) ? 'on' : 'off';
        $setPoint   = ' The target is ' . (string)(($tStat->tstate == 1) ? $tStat->t_heat : $tStat->t_cool);
        $tStat->releaseLock();
        $message = 'OK';
        $status = 0;
      }
      else{
        $message = 'Can not get thermostat lock.';
        $util::logError( $message );
        $status = 3;
      }
    }
    catch( Exception $e ){
      $message = 'Thermostat communication failed: ' . $e->getMessage();
      $util::logError( $message );
      $status = 2;
    }

    $stat = array();
    $stat['name'] = $thermostat['name'];
    $stat['message'] = $message;
    $stat['status'] = $status;
//$util::logInfo( '$tStat is ' . print_r($tStat, true) );
    $stat['present_time'] = $tStat->time;
    $stat['set_point'] = $setPoint;
    $stat['temperature'] = $tStat->temp;
    $stat['humidity'] = $tStat->humidity;

    $stat['heater'] = $heatStatus;
    $stat['compressor'] = $coolStatus;
    $stat['fan'] = $fanStatus;
    $stat['last_read_date'] = $last_read_date;

    $loc['thermostats'][] = $stat;

    $locations[] = $loc;
  }
//$util::logDebug( 'After locations loop' );


  //$message = print_r($user, true);
  $message = 'OK';
  $status = 0;
}
catch( Exception $e ){
  $message = 'It all failed: ' . $e->getMessage();
  $util::logError( $message );
  $status = 1;
}


$answer[ 'locations' ] = $locations;
$answer[ 'message' ] = $message;;
$answer[ 'status' ] = $status;  // Need a code for partial success (perhaps negative numbers are partial fail while positive numbers are total fail - since all others use positive non-zero as fail)

echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );


/*
New output
The output ought to be something like this (the structure ought to strongly resemble that of the electric answer)
{
  "answer":{
    "message":"Connection worked",
    "status":0,
    "locations":[{
      "name":"Home",
      "temperature":95.5,
      "thermostats":[{
        "name":"Hallway",
        "present_date":"2018-09-04 03:52",
        "temperature":75.5,
        "heater":"off",
        "compressor":"on",
        "fan":"on",
        "last_read_date":"2018-08-26 14:00"
      },{
        "name":"Upstairs",
        "present_date":"2018-09-04 03:52",
        "temperature":77,
        "heater":"off",
        "compressor":"off",
        "fan":"off",
        "last_read_date":"2018-08-26 14:00"
      }]
    },{
      "name":"Vacation Home",
      "temperature":65,
      "thermostats":[{
        "name":"First floor",
        "present_date":"2018-09-04 03:52",
        "temperature":73,
        "heater":"on",
        "compressor":"off",
        "fan":"on",
        "last_read_date":"2018-08-26 14:00"
      },{
        "name":"Basement",
        "present_date":"2018-09-04 03:52",
        "temperature":77,
        "heater":"off",
        "compressor":"off",
        "fan":"off",
        "last_read_date":"2018-08-26 14:00"
      }]
    }]
  }
}
*/

/*
$thermostats = array();
$locations = array();

$thermostat[ 'name'] = 'Hallway';
$thermostat[ 'message' ] = 'OK';
$thermostat[ 'status' ] = 0;
$thermostat[ 'present_date'] = '2019-03-21 15:46';
$thermostat[ 'temperature'] = 66.5;
// Do I need set point in here?
$thermostat[ 'heater'] = 'off';
$thermostat[ 'compressor'] = 'on';
$thermostat[ 'fan'] = 'on';
$thermostat[ 'last_read_date'] = '2019-03-21 15:30';

$thermostats[] = $thermostat;

$thermostat[ 'name'] = 'Upstairs';
$thermostat[ 'message' ] = 'OK';
$thermostat[ 'status' ] = 0;
$thermostat[ 'present_date'] = '2019-03-21 15:47';   // It takes a few seconds to read the next one.
$thermostat[ 'temperature'] = 68;
$thermostat[ 'heater'] = 'off';
$thermostat[ 'compressor'] = 'off';
$thermostat[ 'fan'] = 'off';
$thermostat[ 'last_read_date'] = '2019-03-21 15:30';

$thermostats[] = $thermostat;

$location[ 'name'] = 'Home';
$location[ 'temperature'] = 81.9;    // This is the outside temperature (I do not think the outside temperature should be in here for the dashboard!  It should be part of the forecast card)
$location[ 'thermostats'] = $thermostats;

$thermostats = NULL;  // Reset the variable so I can fill it up again.
$locations[] = $location;

$thermostat[ 'name'] = 'First floor';
$thermostat[ 'message' ] = 'OK';
$thermostat[ 'status' ] = 0;
$thermostat[ 'present_date'] = '2019-03-21 15:49';   // It takes a few seconds to read the next one.
$thermostat[ 'temperature'] = 73;
$thermostat[ 'heater'] = 'on';
$thermostat[ 'compressor'] = 'off';
$thermostat[ 'fan'] = 'on';
$thermostat[ 'last_read_date'] = '2019-03-21 15:30';

$thermostats[] = $thermostat;

$thermostat[ 'name'] = 'Basement';
$thermostat[ 'message' ] = 'Error';
$thermostat[ 'status' ] = 12;        // Example of a failure of some kind.  The types of error that the front end cares about can be determined later.
$thermostat[ 'present_date'] = NULL;
$thermostat[ 'temperature'] = NULL;
$thermostat[ 'heater'] = NULL;
$thermostat[ 'compressor'] = NULL;
$thermostat[ 'fan'] = NULL;
$thermostat[ 'last_read_date'] = '2019-03-21 14:00';

$thermostats[] = $thermostat;


$location['name'] = 'Vacation Home';
$location['temperature'] = 65;    // This is the outside temperature (I do not think the outside temperature should be in here for the dashboard!  It should be part of the forecast card)
$location['thermostats'] = $thermostats;


$locations[] = $location;


$answer[ 'message' ] = 'OK';
$answer[ 'status' ] = 0;
$answer[ 'locations' ] = $locations;
echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );
*/

$util::logInfo( 'execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>