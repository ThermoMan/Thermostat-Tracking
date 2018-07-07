<?php
require_once( 'session.php' );
require_once( 'common.php' );
require_once( 'user.php' );

if( is_null( $user ) ){
  // $user already null, no user to remove.
  header( 'Location: index' );
//  exit();
  exit( '<meta http-equiv="refresh" content="0;url=index" />' );
// the next line never executes!
//  $user = new USER();
}
else{
  $util::logDebug( 'logout: $user exists, need to log him out' );
}

/** This vvvvv block might not be needed here **/
/** This vvvvv block might not be needed here **/
if( ! $user->isLoggedIn() ){
  // If user is not logged in they must be expelled!
  header( 'Location: index' );
//  exit();
  exit( '<meta http-equiv="refresh" content="0;url=index" />' );
}
/** This ^^^^^ block might not be needed here **/
/** This ^^^^^ block might not be needed here **/

// Now, log them out and expel them
$user->doLogout();
header( 'Location: index' );
//exit();
exit( '<meta http-equiv="refresh" content="0;url=index" />' );
?>