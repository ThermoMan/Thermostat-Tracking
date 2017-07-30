<?php
  session_start();
  require_once( 'user.php' );
  $user = new USER();

  if( $user->is_loggedin() != '' ){
    $user->redirect( 'profile.php' );
  }

// Move the register logic to a function inside the user.php file
  if( isset( $_POST[ 'btn-signup' ] ) ){
    $uname = strip_tags( $_POST[ 'txt_uname' ] );
    $umail = strip_tags( $_POST[ 'txt_umail' ] );
    $upass = strip_tags( $_POST[ 'txt_upass' ] );
    $upass2 = strip_tags( $_POST[ 'txt_upass2' ] );
    if( $uname == '' ){
      $error[] = 'provide username !';
    }
    else if( $umail == '' ){
      $error[] = 'provide email id !';
    }
    else if( !filter_var( $umail, FILTER_VALIDATE_EMAIL ) ){
      $error[] = 'Please enter a valid email address !';
    }
    else if( $upass == '' ){
      $error[] = 'Password is required!';
    }
    else if( strlen( $upass ) < 6 ) {
      $error[] = 'Password must be atleast 6 characters';
    }
    else if( $upass != $upass2 ){
      $error[] = 'Passwords do not match !';
    }
    else{
      try{
//        $stmt = $user->runQuery( 'SELECT user_name, email FROM thermo2__users WHERE user_name = :uname' );
// Bandaid to keep things moving
$database = new Database();
        $stmt = $user->runQuery( "SELECT user_name, email FROM {$database->table_prefix}users WHERE user_name = :uname" );
//        $stmt->execute( array( ':uname' => $uname ) );
        $stmt->bindparam( ':uname', $uname );
        $stmt->execute();

        $row = $stmt->fetch( PDO::FETCH_ASSOC );

        if( $row[ 'user_name' ] == $uname ){
          $error[] = 'sorry username already taken !';
        }
        else{
          if( $user->register( $uname, $umail, $upass ) ){
            $user->redirect( 'sign-up.php?joined' );
          }
          else{
            $error[] = 'Unknown bolluxing (a)!';
          }
        }
      }
      catch( Exception $e ){
// Log that exception message, but do not show it to the user.
//        echo $e->getMessage();
        $error[] = 'Unknown bolluxing (b)!';
      }
    }
  }

  require_once( 'standard_page_top.php' );
?>

<div style='margin: 0 auto; text-align: left; width: 600px;'>

  <form method='post' class='form-signin'>
    <h2 class='form-signin-heading'>Sign up.</h2>
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
else if( isset( $_GET[ 'joined' ] ) ){
?>
      <div class='alert alert-info'>
        <i class='glyphicon glyphicon-log-in'></i> &nbsp; Successfully registered <a href='index.php'>login</a> here after clicking the link in the validation email.
      </div>
<?php
}
?>
      <div class='form-group'>
        <input type='text' class='form-control' name='txt_uname' placeholder='Enter Username' value='<?php if(isset($error)){echo $uname;}?>' />
      </div>
      <div class='form-group'>
        <input type='text' class='form-control' name='txt_umail' placeholder='Enter E-Mail ID' value='<?php if(isset($error)){echo $umail;}?>' />
      </div>
      <div class='form-group'>
        <input type='password' class='form-control' name='txt_upass' placeholder='Enter Password' />
      </div>
      <div class='form-group'>
        <input type='password' class='form-control' name='txt_upass2' placeholder='Confirm Password' />
      </div>
      <div class='clearfix'></div>
      <div class='form-group'>
        <button type='submit' class='btn btn-primary' name='btn-signup'>
          <i class='glyphicon glyphicon-open-file'></i>&nbsp;SIGN UP
        </button>
      </div>
      <br />
  </form>

</div>

<?php
  require_once( 'standard_page_foot.php' );
?>