<?php
$start_time = microtime(true);
require_once( 'common.php' );
require_once( 'user.php' );

$log->logInfo( 'get_instant_electro: start' );

/* Put useful comments here and either merge code with get_instant_status.php or make this a virtual clone of that file */

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
$user = new USER( $uname, $session );
if( ! $util::checkThermostat( $user ) ) return;
//if( ! $util::checkTED5000( $user ) ) return;
//Don't just return empty,  Tell them they have not TED 5000 configured

$returnString = '';

try{
  foreach( $user->TED5000_Gateways as $gatewayRec ){
    // This loop really ought to be per location, not per meter!

    $ted5000 = new TED5000_Gateway( $gatewayRec );

    /** Get electric usage info
      *
      */
    try{
//$log->logInfo( 'get_instant_electro: Trying to talk to TED 5000 Gateway' );

      $val = $ted5000->getStatus(); // Go get the present data for the gateway in question.
// Need it to throw exception on fail instead of return value.

//$log->logDebug( "get_instant_electro: TED 5000 Gateway said ($uptime)" );
      $returnString = 'Your TED 5000 thinks that it is ' . $ted5000->getTime() . '<br>';
      $returnString .= 'You are using ' . $ted5000->getPower() . ' kWh right now and are projected to use ' . $ted5000->getPowerProj() . ' kWh this month.';
    }
    catch( Exception $e ){
      $log->logError( 'get_instant_electro: $mtu->getStatus() threw an unpleasant error and could not talk to the MTU. ' . $e->getMessage() );
      $returnString = "<p><img src='images/Alert.png'/>Presently unable to read TED 5000.</p>";
    }
  }
}
catch( Exception $e ){
$log->logError( 'get_instant_electro: Some bugs failure or other ' . $e->getMessage() );
  $returnString = "<p><img src='images/Alert.png'/>Presently unable to read TED 5000.</p>";
}

echo $returnString;
$log->logInfo( 'get_instant_electro: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>