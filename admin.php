<?php
  require_once( 'session.php' );

  // Reverify that the user is admin!
  require_once( 'user.php' );
  $login = new USER();
  if( ! $login->isLoggedin() || ! $login->isSiteAdmin() ){
    header( 'Location: index' );
    exit( '<meta http-equiv="refresh" content="0;url=index" />' );
  }

  require_once( 'standard_page_top.php' );
?>

<script type='text/javascript'>
  function backup(){
//    var url_string = 'backup';
    var url_string = 'admin_dl?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>&action=backup';
    $.getJSON( url_string, function( data ){
    });
  }
  function cleanLogs(){
    var url_string = 'admin_dl?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>&action=clean_logs';
    $.getJSON( url_string, function( data ){
debugger;
    });
  }
</script>

<div style='text-align: left;'>
<br><button type='button' onClick='backup();'>Backup</button>
<br><button type='button' onClick='cleanLogs();'>Delete old log files</button>
<br>Are there any DB sanity check type things that need to happen?
<br>Archive old data?
<br>Add an active flag to user record.  Admin can toggle the flag.  Flag can get set by too many failed log in attempts.
<br>Something else?
<br><br>
<?php
  echo '<br>display_errors = ' . ini_get( 'display_errors' );
  echo '<br>PHP_OS = ' . PHP_OS;
  echo '<br>IP = ' . $util::get_ip_address();
  echo '<br>session.use_cookies = ' . ini_get( 'session.use_cookies' );
  echo '<br>session.cookie_lifetime = ' . ini_get( 'session.cookie_lifetime' ) . ' ( ' . $util::secondsToTime( ini_get( 'session.cookie_lifetime' ) ) . ' )';
  echo '<br>DB user expiration = ' . $user->getSessionInfo( $_SESSION[ 'user_name' ], $_SESSION[ 'user_session' ] );
  echo '<br>now = ' . $util::secondsToDate( time() ) . ' why is this not in the present timezone???';
?>
</div>
<br/><br/>

<div style='text-align: center;'>
<?php
  $uptime = @exec( 'uptime' );
  if( strstr( $uptime, 'days' ) ){
    if( strstr( $uptime, 'min' ) ){
      preg_match( "/up\s+(\d+)\s+days,\s+(\d+)\s+min/", $uptime, $times );
      $days = $times[1];
      $hours = 0;
      $mins = $times[2];
    }
    else{
      preg_match( "/up\s+(\d+)\s+days,\s+(\d+):(\d+),/", $uptime, $times );
      $days = $times[1];
      $hours = $times[2];
      $mins = $times[3];
    }
  }
  else{
    preg_match( "/up\s+(\d+):(\d+),/", $uptime, $times );
    $days = 0;
    $hours = $times[1];
    $mins = $times[2];
  }
  preg_match( "/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $uptime, $avgs );
  $load = $avgs[1] . ', ' . $avgs[2] . ', ' . $avgs[3];
?>
  <br>Server Uptime: <?php echo $days ?> days <?php echo $hours ?> hours <?php echo $mins ?> minutes
  <br>Average Load: <?php echo $load ?>
</div>

<?php
  require_once( 'standard_page_foot.php' );
?>