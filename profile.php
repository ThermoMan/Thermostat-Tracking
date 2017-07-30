<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );

  if( isset( $_POST[ 'btn-update' ] ) ){
    $uname = strip_tags( $_POST[ 'txt_uname' ] );
    $umail = strip_tags( $_POST[ 'txt_umail' ] );
    $upass = strip_tags( $_POST[ 'txt_upass' ] );
    $newpass = strip_tags( $_POST[ 'txt_newpass' ] );
    $newpass = strip_tags( $_POST[ 'txt_newpass2' ] );

    if( $upass == '' ){
      $error[] = 'Password is required to change settings!';
    }
    else{
      // Optionally try to change username
      if( strlen( $uname ) > 0 && $uname != $user->getName() ){
// From here vvvv
// This validation test should be in some sort of user register class
// $error[] = $user->validateProposedUsername()
        // If there is a value in there and it is not the same as the existing username
        if( strlen( $uname ) < 6 ) {
          $error[] = 'Username must be atleast 6 characters';
        }
// To here ^^^^
      }

      // Optionally try to change email address
      if( $umail != '' ){
        if( !filter_var( $umail, FILTER_VALIDATE_EMAIL ) ){
          $error[] = 'Please enter a valid email address !';
        }
      }

      // Optionally try to change password
      if( strlen( $newpass ) > 0 ){
        if( strlen( $newpass ) < 6 ) {
          $error[] = 'Password must be atleast 6 characters';
        }
        else if( $newpass != $newpass2 ){
          $error[] = 'Passwords do not match !';
        }
      }
      if( ! $error ){
        // If no errors, then try to update what changed

        // duplicate username might fail.
        // email address should only change if link in confirmation email is clicked
        // send notificaiton of changes to old (and new) email address
      }
    }
  }
?>

<p class='h4'>Profile Page</p>
<hr />
<div>
  <div style='width: 550px; float: left; text-align: left;'>
    <form class='form-signin' method='post' id='login-form'>
      <h2 style='text-align: center;'>Edit profile.</h2>

<?php
if( isset( $error ) ){
?>
      <div id='error'>
        <div class='alert alert-danger'>
          <i class='glyphicon glyphicon-warning-sign'></i> &nbsp; <?php echo $error; ?> !
        </div>
      </div>
<?php
}
?>

      <div class='form-group'>
        <label for='txt_uname' style='width: 300px; display: inline-block; text-align: right;'>User name</label>
        <input type='text' style='width: 200px;' class='form-control' id='txt_uname' name='txt_uname' placeholder='Enter new user name' value='<?php if(isset($error)){echo $uname;}else{ echo $user->getName(); }?>' />
      </div>

      <div class='form-group'>
        <label for='txt_umail' style='width: 300px; display: inline-block; text-align: right;'>Email address</label>
        <input type='text' style='width: 200px;' class='form-control' id='txt_umail' name='txt_umail' placeholder='Enter E-Mail ID' value='<?php if(isset($error)){echo $umail;}?>' />
      </div>

      <div class='form-group'>
        <label for='txt_password' style='width: 300px; display: inline-block; text-align: right;'>Enter present PW to confirm any changes</label>
        <input type='password' style='width: 200px;' class='form-control' id='txt_password' name='txt_password' placeholder='Your Password' required/>
      </div>

      <div class='form-group'>
        <label for='txt_newpass' style='width: 300px; display: inline-block; text-align: right;'>Use this to change your present PW</label>
        <input type='password' style='width: 200px;' class='form-control' id='txt_newpass' name='txt_newpass' placeholder='New Password' />
      </div>

      <div class='form-group'>
        <label for='txt_newpass2' style='width: 300px; display: inline-block; text-align: right;'>Confirm your new PW</label>
        <input type='password' style='width: 200px;' class='form-control' id='txt_newpass2' name='txt_newpass2' placeholder='Confirm' />
      </div>


      <div class='form-group'>
        <button type='submit' style='float: right;' name='btn-update' class='btn btn-primary'>SAVE</button>
      </div>
    </form>
  </div>

  <div style='width: 550px; float: right;'>
    <br />Put another form here that lists all the present thermostats registered for this user with edit permissions
    <br />Have a button to add a thermostat
  </div>
  <hr />
</div>


<?php
  require_once( 'standard_page_foot.php' );
?>