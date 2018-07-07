<?php
$section = 'xyzzy'; // This is the magic word tha allows a non-logged-in user to see this content.
require_once( 'session.php' );
require_once( 'common.php' );
require_once( 'user.php' );

if( is_null( $user ) ){
  $util::logDebug( 'recover: $user was null, had to make my own' );
  $user = new USER();
}
else{
  $util::logDebug( 'recover: $user already exists' );
}

if( $user->isLoggedIn() ){
  // Do not let logged in user stay here
  header( 'Location: home' );
//  exit();
  exit( '<meta http-equiv="refresh" content="0;url=home" />' );
}

if( isset( $_POST[ 'btn-recover' ] ) ){
  $uname = strip_tags( $_POST[ 'txt_uname' ] );
  $umail = strip_tags( $_POST[ 'txt_umail' ] );

// In the MVC scheme of things...  This code is the V
// The C is sort of virtual.  You click a link and go the right page by default.
// The C is also in checking if we're in here because of the button click.
// The M is over in user.php - that is also where the business logic should hide.
  $user = null;
  $msg = null;
  if( strlen( $uname ) > 0 ){
    // Look for a user name of $uname
    $user = USER::findUserByName( $uname );
    // if not found, tell user not found and suggest search by email or register.
$msg = 'Search by name not implemented yet !';
  }
  else if( strlen( $umail ) > 0 ){
    // Check email format filter_var( $umail, FILTER_VALIDATE_EMAIL )
    $user = USER::findUserByEmail( $umail );
// Need a static $user = USER::findUserByEmail( $umail ) - returns a $user object (logged in better = false!) or null
    //  If not valid, complain
    // Look for user with that email address
    // if not found, tell user not found and suggest search by name or register.
$msg = 'Search by email not implemented yet !';
    // If more than one address found ask user to recover ALL of them or make him recover by name (or is this to hackable?)
  }
  else{
    $msg = 'Provide one of Username or Email Address in order to recover your account !';
  }

  if( $user != null && !isset( $error ) ){
    // If I get in here then the user was found and data populated

    //  Is user already validated?  Y -> send password change email
    //  Is user already validated?  N -> resend validation email (with a new validation_key)
    $error[] = $msg;

  }
  else{
    $error[] = $msg;
  }

}
  require_once( 'standard_page_top.php' );
?>

<h2>Password recovery is not yet implemented.  Don't lose your password!</h2>

<div style='margin: 0 auto; text-align: left; width: 600px;'>
  <form method='post' class='form-recover'>
    <h2 class='form-recover-heading'>Recover ID/PW.</h2>
<?php
if( isset( $error ) ){
  foreach( $error as $error ){
?>
      <div class='alert alert-danger'>
        <i class='glyphicon glyphicon-warning-sign'></i> &nbsp; <?php echo $error; ?>
      </div>
<?php
  }
}
?>
      <div class='form-group'>
        <input type='text' class='form-control' name='txt_uname' placeholder='Enter Username' value='<?php if(isset($error)){echo $uname;}?>' />
      </div>
      <div class='form-group'>
        <input type='text' class='form-control' name='txt_umail' placeholder='Enter Email Address' value='<?php if(isset($error)){echo $umail;}?>' />
      </div>
      <div class='clearfix'></div>
      <div class='form-group'>
        <button type='submit' class='btn btn-primary' name='btn-recover'>
          <i class='glyphicon glyphicon-open-file'></i>&nbsp;RECOVER
        </button> including resend of registration email.
      </div>
  </form>
</div>

<?php
  require_once( 'standard_page_foot.php' );
?>