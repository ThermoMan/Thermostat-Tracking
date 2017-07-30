<?php
  require_once( 'session.php' );

  require_once( 'user.php' );
  $user = new USER();
  $user->doLogout();

  // Taken from: http://php.net/manual/en/function.session-destroy.php
  // Unset all of the session variables.
  $_SESSION = array();

  // If it's desired to kill the session, also delete the session cookie.
  // Note: This will destroy the session, and not just the session data!
  if( ini_get( 'session.use_cookies' ) ){
      $params = session_get_cookie_params();
      setcookie( session_name(), '', time() - 42000,
          $params[ 'path'], $params[ 'domain' ],
          $params[ 'secure'], $params[ 'httponly' ]
      );
  }

  // Finally, destroy the session.
//  session_destroy();
// Commented out because of this warning
// Warning: session_destroy(): Trying to destroy uninitialized session in /home/fratell1/freitag.theinscrutable.us/thermo2/logout.php on line 22

  $user->redirect( 'index.php' );
?>