<?php
$section = 'xyzzy'; // This is the magic word that allows a non-logged-in user to see this content.
require_once( 'session.php' );
require_once( 'user.php' );
$util::logDebug( 'index.php 0' );

if( is_null( $user ) ) $user = new USER();
$util::logDebug( 'index.php 1' );
if( $user->isLoggedIn() ){
$util::logDebug( 'index.php 2' );
  // Do NOT let already logged in user stay here
  header( 'Location: dashboard' );
//  exit();
  exit( '<meta http-equiv="refresh" content="0;url=dashboard" />' );
}
$util::logDebug( 'index.php 3' );

// Standard page top owns the form whose action sends user to this code.
if( isset( $_POST[ 'btn-login' ] ) ){
$util::logDebug( 'index.php 4' );
  $uname = strip_tags( $_POST[ 'txt_uname' ] );
  $upass = strip_tags( $_POST[ 'txt_password' ] );

  $expiration_date = date( 'Y-m-d H:i:s', strtotime( '+' . MAX_SESSION_LENGTH . ' day' ) );
  if( $user->doLogin( $uname, $upass, $expiration_date ) ){
$util::logDebug( 'index.php 5' );
$util::logDebug( "index.php: user $uname succesfully logged in.  Cookie set to expire in " . $util::secondsToTime( ini_get( 'session.cookie_lifetime' ) ) );
    header( 'Location: dashboard' );
//    exit();
    exit( '<meta http-equiv="refresh" content="0;url=dashboard" />' );
  }
  else{
$util::logDebug( 'index.php 6' );
    $error = 'Wrong Details!';
  }
}
$util::logDebug( 'index.php 7' );

require_once( 'standard_page_top.php' );
?>

If you were logged in, you'd see some really cool stuff right here.

<?php
require_once( 'standard_page_foot.php' );
?>