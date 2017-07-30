<?php
  session_start();

  // Do not let logged in user stay here
  require_once( 'user.php' );
  $login = new USER();
  if( $login->is_loggedin() ){
    $login->redirect( 'dashboard.php' );
  }

  require_once( 'standard_page_top.php' );
?>

Password recovery is not yet implemented.  Don't lose your password!

<?php
  require_once( 'standard_page_foot.php' );
?>