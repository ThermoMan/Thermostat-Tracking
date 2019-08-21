<?php
$start_time = microtime(true);
require_once( 'common.php' );
require_once( 'user.php' );

$util::logInfo( 'start' );

$message = '';
$status = 0; // Assume success

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
$user = new USER( $uname, $session );
if( ! $util::checkThermostat( $user ) ){
//QQQ message should NOT contain HTML!
      $message .= "<p><img src='images/Alert.png'/>You haven't added any meters yet.</p>";
      $status++;  // Increment number of errors
}
else{
  // Bandaid to keep things moving
  $database = new Database();
  $pdo = $database->dbConnection();

/*
  Presently this data structure is only designed for TED5000 - needs update if we are to support other usage monitors
  a user has one or more locations
  a location has one or more Gateways (PERHAPS A LOCATION HAS ZERO ELECTRIC MONITORS!!!)
  a Gateway has one to four MTUs (an MTU measures either usage or generation)

  foreach( user->locations as location ){
    set reply JSON location
    foreach( location->gateways as gateway ){
      contact gateway
      set reply JSON gateway time
      foreach( gatway->MTUs as MTU ){
        parse MTU data
        set reply JSON MTU present usage
        query DB for last data for this MTU
        set reply JSON last data fetch date
      }
    }
  }
  emit reply JSON
*/

  $mtu = array();

  try{
  // This old logic skips the location layer and dumps all the gateways for a user
  // This old logic uses names incorrectly and calls an MTU a gateway
    foreach( $user->TED5000_Gateways as $gatewayRec ){
//$util::logDebug( 'START ONE');
      // There ought to be an outer loop per location so that MTUs are grouped per location
      // Need to mock up a JSON for that scenario too.

      $ted5000 = new TED5000_Gateway( $gatewayRec );
//$util::logDebug( 'Communicating for MTU ' . $gatewayRec['mtu'] . '.' );

      // Get electric status info
      try{
//$util::logDebug( 'Trying to getStatus() from MTU ' . $gatewayRec['mtu'] . '.' );

        $val = $ted5000->getStatus(); // Go get the present data for the gateway in question.


//$util::logDebug( "TED 5000 Gateway said ($uptime)" );
        $mtuTimeNow = $ted5000->getTime();
//$util::logDebug( 'Talk to MTU ' . $gatewayRec['mtu'] . ' OK.' );
      }
      catch( Exception $e ){
        $util::logError( '$mtu->getStatus() threw an unpleasant error and could not talk to the MTU. ' . $e->getMessage() );
  //QQQ message should NOT contain HTML!
        $message .= "<p><img src='images/Alert.png'/>Presently unable to read TED 5000.</p>";
        $status++;  // Increment number of errors
      }

      // Get electric last contact date and time
      try{
//$util::logDebug( 'Trying to find last data date from MTU ' . $gatewayRec['mtu'] . '.' );
        $sql = "
SELECT ifnull( date_format( MAX(data.date), '%Y/%m/%d %H:%i' ), 'NEVER') AS date
FROM {$database->table_prefix}meter_data data
,{$database->table_prefix}meters met
WHERE met.mtu = :mtu
AND met.mtu_id = data.mtu_id";

        $queryLastContactDate = $pdo->prepare( $sql );
        $queryLastContactDate->bindParam( ':mtu', $gatewayRec['mtu'] );
        $queryLastContactDate->execute();
        $mtuLastTime = $queryLastContactDate->fetchColumn();
//$util::logDebug( 'Talk to MTU ' . $gatewayRec['mtu'] . ' OK. -> ' . $mtuLastTime );
        $mtu[] = array( "mtu" => $gatewayRec['mtu'], "key" => "QQQ", "date" => $mtuLastTime );
      }
      catch( Exception $e ){
        $util::logError( 'Some DB error prevented query. ' . $e->getMessage() );
//QQQ message should NOT contain HTML!
        $message .= "<p><img src='images/Alert.png'/>Can't talk to DB to find out when it last saw the MTU.</p>";
        $status++;  // Increment number of errors
      }
//$util::logDebug( 'FINISH ONE');

    }
//$util::logDebug( 'ALL DONE');
  }
  catch( Exception $e ){
    $util::logError( 'Some bugs failure or other ' . $e->getMessage() );
//QQQ message should NOT contain HTML!
    $message .= "<p><img src='images/Alert.png'/>Presently unable to read TED 5000.</p>";
    $status++;  // Increment number of errors
  }

  $meter = array( "present_date" => $mtuTimeNow,
                  "use" => ("" + $ted5000->getPower() + "kWh"),
                  "projected" => ("" + $ted5000->getPowerProj() + "kWh"),
                  "mtu" => $mtu );
  $answer[ 'meter' ] = $meter;
}

// QQQ These (message and status) ought to be per gateway?  Need an overall status and message?
$answer[ 'message' ] = $message;
$answer[ 'status' ] = $status;

echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );
/*
The output ought to be something like this
{
  "answer": {
     "message": "Some message here"
    ,"status": 0
    ,"location":[{
       "name": "Home"
      ,"gateway": {
         "present_date": "2018-08-26 16:25:10"
        ,"uptime": "689788 seconds"
        ,"present_use": "0.776 kWh"
        ,"projected_use": "534.152 kWh"
        ,"mtu": [{
           "key": "use"
          ,"last_read_date": "2018\/08\/26 14:00"
        }, {
           "key": "gen"
          ,"last_read_date": "2018\/08\/26 13:59"
        }]
      }
    }]
  }
}
*/


$util::logInfo( 'execution time was ' . (microtime(true) - $start_time) . ' seconds.' );
?>