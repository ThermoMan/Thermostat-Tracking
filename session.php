<?php
require_once( 'common.php' ); // To let me do logging
// QQQ Maybe rename this user_session so the user stuff hangs together in a file list and can be pulled out for code reuse more easily.

global $util;
$util::logDebug( 'Start' );

// Force to https from http
if( $_SERVER[ 'HTTPS' ] != 'on'){
  $util::logError( 'Forced user to https from http' );
  header( 'Location: https://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER['REQUEST_URI'] );
  exit( '<meta http-equiv="refresh" content="0;url=https://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER['REQUEST_URI'] . '" />' );
}

$util::session_start();

$user = null;

// I may not need this explicit list of allowed pages (although a belt AND suspenders for security is not a bad thing)
// It seems to be working for register.php and recover.php without inclusion
$allowedInsecurePages[] = [ 'about.php', 'index.php' ];

//QQQ should this be || or && ???
//QQQ The idea here is that if you are not logged in you are forced to to go index.php
//QQQ  UNLESS you're looking at one of these allowed pages.

//$util::logDebug( "I think you are on page " . basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) );
if( !isset( $section ) ) $section = 'undefined';
if( ( ! in_array( basename( $_SERVER[ 'SCRIPT_FILENAME' ] ), $allowedInsecurePages ) ) && ( 'xyzzy' !== $section ) ){
//$util::logDebug( "I think you are on page " . basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) . " which is not an open page" );
  require_once( 'user.php' );
  $user = new USER();
  if( !$user->isLoggedIn() ){
    $util::logError( 'Evicting unknown user from secure page' );
    header( 'Location: index' );
    exit( '<meta http-equiv="refresh" content="0;url=index" />' );
  }
//  else{
//    $util::logDebug( "I think you are logged in, so everything is hunky dory" );
//  }
}
//else{
//  $util::logDebug( "I think everyone is allowed to see that page" );
//}
?>