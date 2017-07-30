<?php
  require_once( 'user.php' );
  require_once( 'session.php' );

  $auth_user = new USER();
  $user_id = $_SESSION[ 'user_session' ];

  $stmt = $auth_user->runQuery( "SELECT * FROM demo_login7_users WHERE user_id = :user_id" );
//  $stmt = $auth_user->runQuery( "SELECT * FROM thermo2__users WHERE user_id = :user_id" );

  $stmt->execute( array( ':user_id' => $user_id ) );
  $userRow = $stmt->fetch( PDO::FETCH_ASSOC );
?>