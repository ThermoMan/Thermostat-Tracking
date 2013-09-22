<?php
/**
	* This is a separate file because I *think* it's where all the user ID code will go.
	*
	*/

//Things that show in the common header
$htmlString = '';
if( $isLoggedIn )
{	// If the user is logged in show one thing
$htmlString .= "<form action='thermo.php' method='post'>" .
							 "Welcome " . $_SESSION[ 'login_user' ] .
							 " <input type='hidden' name='ac' value='logout'>" .
							 " <input type='submit' value='Logout'>" .
							 " Manage <input type='button' onClick='javascript: location.href=\"#account\";' value='Account'>" .
							 "</form>";
}
else
{	// If the user is logged out, show them a different thing

	// If they are logged out and yet the action was to log in, then they tried and failed.  Tell them they messed up.
	$warningString = '';
	if( isset( $_POST[ 'ac' ] ) &&
	   !empty( $_POST[ 'ac' ] ) &&
	    $_POST[ 'ac' ] == 'log' )
	{
		$warningString = '<span style="color: red;">Incorrect username and/or password.</span> ';
	}

$htmlString .= "<form action='thermo.php' method='post'>" .
							 "<input type='hidden' name='ac' value='log'>" .
							 $warningString .
							 "Username <input type='text' name='username' />" .
							 "Password <input type='password' name='password' />" .
							 "Log me on automatically each visit <input type='checkbox' name='remember' />" .
							 "<input type='submit' value='Login'>" .
							 "<input type='button' onClick='javascript: location.href=\"#register\";' value='Register'>" .
							 "</form>";
}

echo $htmlString;
?>
