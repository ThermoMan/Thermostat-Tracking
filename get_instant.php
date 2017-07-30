<?php
//QQQ How much security to do in this module?  It's really just a decider for which status to fetch
//QQQ In MVC is this M or C?  The called routines are clearly M, does making a choice turn this into C?
$type = (isset($_REQUEST['type'])) ? $_REQUEST['type'] : null;

switch( $type ){
  case 'status':
    require_once( 'get_instant_status.php' );
  break;

  case 'forecast':
    require_once( 'get_instant_forecast.php' );
  break;

  case 'electro':
    require_once( 'get_instant_electro.php' );
  break;
}
?>