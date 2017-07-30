<?php
  require_once( 'user.php' );
  $user = new USER();
?>

<!DOCTYPE html>
<html lang='en'>
<head>
  <meta http-equiv='content-type' content='text/html; charset=utf-8' />
  <title>3M-50 Thermostat Tracking</title>
  <link rel='shortcut icon' type='image/x-icon' href='favicon.ico' />
  <link rel='stylesheet' type='text/css' href='/common/css/reset.css' >
  <link rel='stylesheet' type='text/css' href='/common/bootstrap/css/bootstrap.min.css' />
  <link rel='stylesheet' type='text/css' href='resources/thermo.css' />

  <script type='text/javascript' src='/common/js/jquery-3.1.0.min.js'></script>
  <script type='text/javascript' src='/common/bootstrap/js/bootstrap.min.js'></script>

  <link rel='stylesheet' type='text/css' href='/common/js/jquery.dataTables.min.css' >
  <script type='text/javascript' src='/common/js/jquery.dataTables.min.js'></script>
<!--  <link rel='stylesheet' type='text/css' href='/common/js/jquery.dataTables.scroller.min.css' > -->
<!--  <script type='text/javascript' src='/common/js/jquery.dataTables.scroller.min.js'></script> -->

  <script type='text/javascript' src='/common/js/amcharts/amcharts.js'></script>
  <script type='text/javascript' src='/common/js/amcharts/serial.js'></script>
  <script type='text/javascript' src='/common/js/amcharts/themes/light.js'></script>
</head>

<body>
  <div style='background: linear-gradient(#007700, #77DD77); height: 50px; top: 0px; position: absolute; width: 100%;'>
    <span style='float: left; position: relative; top: 25%;'>
      <div style='display: inline-block; top: 4px; position: relative;'>
<?php
if( $user->isLoggedIn() ){
?>
        &nbsp;&nbsp;<a href='admin.php'>Site Admin</a>
<?php
}
?>
        &nbsp;&nbsp;<a href='about.php'><span class='glyphicon glyphicon-info-sign'></span> About</a>
      </div>
    </span>

    <span style='float: right; position: relative; top: -4px;'>

<?php
if( $user->isLoggedIn() ){
?>
      <div style='display: inline-block; top: 18px; position: relative;'>
        Welcome <?php print( $user->getName() ); ?>
        <select onchange='location = $(this).find( "option:selected" ).data( "action" );' onfocus='this.selectedIndex = 0;'>
          <option style='height: 32px; position: relative; top: 40%; text-indent: 32px;'>Choose Action</option>
          <option data-action='profile.php' title='View Profile' style='height: 32px; position: relative; top: 40%; text-indent: 32px;'>View Profile</option>
          <option data-action='logout.php'  title='Logout'       style='height: 32px; position: relative; top: 40%; text-indent: 32px;'><span class='glyphicon glyphicon-log-out'></span> Logout</option>
        <select>&nbsp;&nbsp;
      </div>
<?php
}
else{
  if( isset( $error ) ){
?>
      <div id='error' style='display: inline-block; top: -7px; position: relative; color: red;'>
        <?php echo $error; ?>!
      </div>
<?php
  }
?>
      <div style='display: inline-block; top: -7px; position: relative;'>
        <form class='form_signin' method='post' id='login-form' action='index.php'>
          <input type='text' class='form_signin_input' name='txt_uname' placeholder='Username' required />
          <input type='password' class='form_signin_input' name='txt_password' placeholder='Your Password' />
          <button type='submit' name='btn-login' class='btn btn-default'>SIGN IN</button>&nbsp;&nbsp;
        </form>
      </div>
      <div style='display: inline-block; width: 200px; top: 9px; position: relative;'>
        <div style='display: block; float: left;'>
          No account? <a href='sign-up.php'>Sign Up!</a>&nbsp;&nbsp;
        </div>
        <div style='display: block; float: left;'>
          Forgot ID/PW? <a href='recover.php'>Recover</a>&nbsp;&nbsp;
        </div>
    </div>
<?php
}
?>
    </span>
  </div>

<?php
if( $user->isLoggedIn() ){
?>
  <div style='background: linear-gradient(#007700, #77DD77); height: 50px; top: 50px; position: absolute; width: 100%;'>
    <span style="float: left; position: relative; top: 25%;">
      <div style='display: inline-block; top: 4px; position: relative;'>
        &nbsp;&nbsp;<a href='dashboard.php'><span class='glyphicon glyphicon-home'></span> Dashboard</a>
        &nbsp;&nbsp;<a href='daily_detail.php'>Daily Detail</a>
        &nbsp;&nbsp;<a href='history.php'>History</a>
        &nbsp;&nbsp;<a href='compare.php'>Compare</a>
        &nbsp;&nbsp;<a href='schedule.php'>Schedule</a>
        &nbsp;&nbsp;<a href='profile.php'><img class='sprite sprite_wheels' src='images/img_trans.gif' width='1' height='1' alt='icon: about'/> profile</a>
      </div>
    </span>
  </div>
<?php
}
?>

  <div id='bigbox'>