<?php
require_once( 'common.php' ); // To let me log

global $util;
$util::logDebug( 'session.php: 0' );

if( $_SERVER[ 'HTTPS' ] != 'on'){
  $log->logInfo( 'session.php: forced user to https from http' );
  header( 'Location: https://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER['REQUEST_URI'] );
  exit();
}

$util::session_start();

$user = null;

// I may not need this explicit list of allowed pages (although a belt AND suspenders for securioty is not a bad thing)
// It seems to be working for register.php and recover.php without inclusion
$allowedInsecurePages[] = [ 'about.php', 'index.php' ];
//QQQ should this be || or && ???
//QQQ The idea here is that if you are not logged in you are forced to to go index.php
//QQQ  UNLESS you're looking at one of these allowed pages.
$util::logDebug( "session.php: I think you are on page " . basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) );
if( ( ! in_array( basename( $_SERVER[ 'SCRIPT_FILENAME' ] ), $allowedInsecurePages ) ) && ( 'xyzzy' !== $section ) ){
$util::logDebug( "session.php: I think you are on page " . basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) . " which is not an open page" );
  require_once( 'user.php' );
  $user = new USER();
  if( !$user->isLoggedIn() ){
$util::logError( 'session.php: Evicting unknown user from secure page' );
    header( 'Location: index' );
    exit();
  }
  else{
    $util::logDebug( "session.php: I think you are logged in, so everything is hunky dory" );
  }
}
else{
  $util::logDebug( "session.php: I think everyone is allowed to see that page" );
}
?>