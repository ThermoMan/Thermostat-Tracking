<?php
require_once 'common.php';

date_default_timezone_set( $timezone );
$show_date = date( 'Y-m-d', time() );	// Start with today's date
if( strftime( '%H%M' ) <= '0059' )
{ // If there is not enough (3 data points is 'enough') data to make a meaningful chart, default to yesterday
	$show_date =	date( 'Y-m-d', strtotime( '-1 day', time() ) );	// Start with yesterday's date
}

$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '';	// Set default thermostat selection

// Login status
$isLoggedIn = false;	// Default to logged out.
if( isset($_POST['password']) && ($_POST['password'] == $password ) )
{ // Update logged in status to true only when the correct password has been entered.
	//session_register( 'user_name' );
	$_SESSION[ 'login_user' ] = 'user_name';
	$isLoggedIn = true;
}

// Now do things that depend on that newly determined login status
// Set Config tab icon default value
$lockIcon = 'tab-sprite lock';			// Default to locked
$lockAlt = 'icon: lock';
$verifyText = 'No';
if( $isLoggedIn )
{
	// Set Config tab icon logged-in value
	$lockIcon = 'tab-sprite unlock';	// Change to UNlocked icon only when user is logged in
	$lockAlt = 'icon: unlock';

	if( isset($_POST['save_settings'] ) )
	{
		$verifyText = 'Yes';
		//save_settings();
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv=Content-Type content='text/html; charset=utf-8'>
		<title>3M-50 Thermostat Tracking</title>
		<link rel='shortcut icon' type='image/x-icon' href='favicon.ico' />

		<link rel='stylesheet' type='text/css' href='/common/css/reset.css' >
		<link rel='stylesheet' type='text/css' href='resources/thermo.css' />

		<link rel='stylesheet' type='text/css' href='lib/tabs/tabsE.css' />		 <!-- Add tab library and set default appearance -->
		<link rel='stylesheet' type='text/css' title='green' href='lib/tabs/tabs-green.css'>
		<meta http-equiv='Default-Style' content='green'>
		<link rel='stylesheet' type='text/css' title='white' href='lib/tabs/tabs-white.css'>

		<!-- Load the stuff that makes it go -->
		<script type='text/javascript' src='resources/thermo.js'></script>

		<!-- These styles are applied to the W3C HTML button on the About tab only and do not need to be part of the .css file -->
		<style>
			a > div.caveat
			{
				display: none;
				text-align: left;
			}
			a:hover > div.caveat
			{
				display: block;
				position: absolute;
				top: 60px;
				left: 100px;
				right: 100px;
				border: 3px double;
				padding: 0px 50px 0px 10px;
				z-index: 100;
				color: #000000;
				background-color: #DCDCDC;
				border-radius: 25px;
			}
			img.caveat
			{
				position: relative;
				width: 19px;
				height: 19px;
				top: 3px;
			}
		</style>
	</head>

	<body>
	<div class='header'><?php include( $rootDir . '/header.php' ); ?></div>
	<br><br><br>
	<div id='bigbox'>
		<!-- Internal variable declarations START -->
		<input type='hidden' name='id' value='<?php echo urlencode($id) ?>'>
		<!-- Internal variable declarations END -->

		<div class='all_tabs'>
			<div class='tab_gap'></div>
			<div class='tab_gap'></div>

			<div class='tab' id='dashboard'> <a href='#dashboard'> Dashboard </a>
				<div class='container'>
					<div class='tab-toolbar'>
						Present conditions
					</div>
					<div class='content'>
						<br><br>
						<input type='button' onClick='javascript: updateStatus(); updateForecast();' value='Refresh'>
						<br>
						<div id='status' class='status'>Javascript must be enabled to see this content</div>
						<div id='forecast' class='forecast'>Javascript must be enabled to see this content</div>
						<!-- Kick off dashboard refresh timers -->
						<script type='text/javascript'>
							updateStatus();
							updateForecast();
						</script>
					</div>
				</div>
			</div>
			<script>
				// Now that the dashbord tab is loaded, set it to be the target
				location.href = '#dashboard';
			</script>
			<div class='tab_gap'></div>



			<div class='tab' id='daily'> <a href='#daily'> Daily Detail </a>
				<div class='container'>
					<div class='tab-toolbar'>
						<input type='button' onClick='javascript: display_chart( "daily", "chart" );' value='Show'>
						<select id='chart.daily.thermostat'>
							<?php foreach( $thermostats as $thermostatRec ): ?>
								<option <?php if( $id == $thermostatRec['id'] ): echo 'selected '; endif; ?>value='<?php echo $thermostatRec['id'] ?>'><?php echo $thermostatRec['name'] ?></option>
							<?php endforeach; ?>
						</select>
						<select id='chart.daily.source'>
							<option value='0'>Outoor</option>
							<option value='1'>Indoor</option>
							<option value='2' selected>Both</option>
						</select>
						<!-- A checkbox to turn on/off the display of the set point temperature -->
						<input type='checkbox' id='chart.daily.setpoint' name='chart.daily.setpoint' onChange='javascript: toggle_daily_flag( "chart.daily.setpoint" );'/> Set Point
						&nbsp&nbsp&nbsp&nbsp&nbsp Timeframe <input type='text' id='chart.daily.interval.length' value='7' size='3'>
						<select id='chart.daily.interval.group' style='width: 65px'>
							<option value='0' selected>days</option>
							<option value='1'>weeks</option>
							<option value='2'>months</option>
							<option value='3'>years</option>
						</select>
						<!-- Need to change the max value to a date computed by Javascript so it stays current -->
						ending on <input type='date' id='chart.daily.toDate' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' step='1'/>
						&nbsp; showing Heat<input type='checkbox' id='chart.daily.showHeat' name='chart.daily.showHeat' onChange='javascript: toggle_daily_flag( "chart.daily.showHeat" );'/>
						&nbsp;Cool<input type='checkbox' id='chart.daily.showCool' name='chart.daily.showCool' onChange='javascript: toggle_daily_flag( "chart.daily.showCool" );'/>
						&nbsp;Fan<input type='checkbox' id='chart.daily.showFan'	name='chart.daily.showFan'	onChange='javascript: toggle_daily_flag( "chart.daily.showFan" );'/> cycles
						<input type='button' onClick='javascript: deleteCookies(0);' value='Un-save settings' style='float: right;'>
<!-- Not yet working so hide it from user until it does...
						<input type='checkbox' id='auto_refresh'		 name='auto_refresh'		 onChange='javascript: timedRefresh();'/>Auto refresh
						<span id='daily_update' style='float: right; vertical-align: middle; visibility: hidden;'>Countdown to refresh: 00:00</span>
-->
					</div>
					<div class='content'>
						<br>
						<div class='thermo_chart'>
							<img id='daily_temperature_chart' src='images/daily_temperature_placeholder.png' alt='The temperatures'>
						</div>
						<input type='button' onClick='javascript: display_chart( "daily", "table" );' value='Chart it' style='float: right;'>
						<div id='daily_temperature_table' class='daily_temperature_table'>
						</div>


						<!-- This initialization script must fall AFTER declaration of various inputs -->
						<script type='text/javascript'>
							// A literal value of "false" is a string of non zero length and so it tests as logically true uinless you look for the literal string "true"
							if( getCookie('chart.daily.showCool') == 'true' )
							{
								document.getElementById('chart.daily.showCool').checked = true;									// Set flag
								// Checkbox styles are not obeyed by browsers, they are styled by the OS.
								document.getElementById('chart.daily.showCool').className = 'remembered_input';	// Set visual cue that cookie retains this value
							}
							if( getCookie('chart.daily.setpoint') == 'true' )
							{
								document.getElementById('chart.daily.setpoint').checked = true;
								document.getElementById('chart.daily.setpoint').className = 'remembered_input';
							}

							if( getCookie('chart.daily.showHeat') == 'true' )
							{
								document.getElementById('chart.daily.showHeat').checked = true;
								document.getElementById('chart.daily.showHeat').className = 'remembered_input';
							}
							if( getCookie('chart.daily.showFan') == 'true' )
							{
								document.getElementById('chart.daily.showFan').checked = true;
								document.getElementById('chart.daily.showFan').className = 'remembered_input';
							}


							// Test values before use, don't let null (not set) change the defaults
							if( getCookie('chart.daily.fromDate') )
							{
								document.getElementById('chart.daily.fromDate').value = getCookie('chart.daily.fromDate');
								document.getElementById('chart.daily.fromDate').className = 'remembered_input';
							}
							if( getCookie('chart.daily.toDate') )
							{
								document.getElementById('chart.daily.toDate').value = getCookie('chart.daily.toDate');
								document.getElementById('chart.daily.toDate').className = 'remembered_input';
							}
/* Set a timer to implement the auto refresh
"chart.daily.autoRefresh"

		if( getCookie( 'auto_refresh' ) == 'true' )
		{
			document.getElementById( 'auto_refresh' ).checked = true;	 // Simply setting the check box does not start the timer.
			timedRefresh();																						 // So start the timer too
		}
*/
							display_chart( 'daily', 'chart' ); // Draw the chart using the applied settings
						</script>
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>



			<div class='tab' id='history'> <a href='#history'> History </a>
				<div class='container'>
					<div class='tab-toolbar'>
						<input type='button' onClick='javascript: display_historic_chart();' value='Show'>
						<select id='chart.history.thermostat'>
							<?php foreach( $thermostats as $thermostatRec ): ?>
								<option <?php if( $id == $thermostatRec['id'] ): echo 'selected '; endif; ?>value='<?php echo $thermostatRec['id'] ?>'><?php echo $thermostatRec['name'] ?></option>
							<?php endforeach; ?>
						</select>
						<select id='history_selection'>
							<option value='0' selected>Outoor</option>
							<option value='1'>Indoor</option>
							<option value='2'>Both</option>
						</select>
						<!-- Show initial range as from 3 weeks ago through today -->
<!--						From date <input type='date' id='history_from_date' size='10' value='<?php echo date( 'Y-m-d', strtotime( '3 weeks ago' ) ); ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_historic_chart();' step='1'/> -->
						Timeframe <input type='text' id='interval_length' value='21' size='3'>
						<select id='interval_measure' style='width: 65px'>
							<option value='0' selected>days</option>
							<option value='1'>weeks</option>
							<option value='2'>months</option>
						</select>
						ending on <input type='date' id='chart.history.toDate' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' step='1'/>
						&nbsp;&nbsp;Optionally show HVAC runtimes<input type='checkbox' id='show_hvac_runtime' name='show_hvac_runtime'/>
						<!-- <span style='float: right;'>UN-save settings<input type='button' onClick='javascript: deleteCookies(1);' value='Clear'></span>-->
						<input type='button' onClick='javascript: deleteCookies(1);' value='Un-save settings' style='float: right;'>
					</div>
					<div class='content'>
						<br>
						<div class='thermo_chart'>
							<img id='history_chart' src='need_default.png' alt='All Time History'>
						</div>
						<script type='text/javascript'>
							display_historic_chart(); // Draw the chart
						</script>
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>




<?php
		// Only show the account administration tab is the user is logged in.  Don't even hint that this tab exists unless they are logged in.
		if( $isLoggedIn )
		{
?>
			<div class='tab' id='account'> <a href='#account'> <img class='<?php echo $lockIcon;?>' src='images/img_trans.gif' width='1' height='1' alt='<?php echo $lockAlt;?>'/> Account </a>
				<div class='container'>
					<div class='tab-toolbar'>
					Edit your account details here
					</div>
					<div class='content'>
						<br>Manage login and thermostat details here in some kind of form.  This is where the add/edit/delete location and thermostat processes live.
						<br><br>
						<table>
							<tr>
								<th style="padding: 5px;">Name</th>
								<th style="padding: 5px;">Description</th>
								<th style="padding: 5px;">IP</th>
								<th style="padding: 5px;">Model</th>
								<th style="padding: 5px;">Firmware</th>
								<th style="padding: 5px;">WLAN Firmware</th>
								<th style="padding: 5px;">Action</th>
							</tr>
<?php
			//foreach( $userThermostats as $thermostatRec ):
			foreach( $thermostats as $thermostatRec ):
?>
							<tr>
								<td align='left' style="padding: 5px;"><?php echo $thermostatRec['name'] ?></td>
								<td align='left' style="padding: 5px;"><?php echo $thermostatRec['description'] ?></td>
								<td align='center' style="padding: 5px;"><?php echo $thermostatRec['ip'] ?></td>
								<td align='center' style="padding: 5px;"><?php echo $thermostatRec['model'] ?></td>
								<td align='left' style="padding: 5px;"><?php echo $thermostatRec['fw_version'] ?></td>
								<td align='left' style="padding: 5px;"><?php echo $thermostatRec['wlan_fw_version'] ?></td>
								<td align='center' style="padding: 5px;"><input type='button' value='Edit' onClick='javascript: alert("Not implemented");'></td>
						 </tr>
<?php
			endforeach;
?>
						</table>
						<br><br>
						<p>Choose appearance: <select id='colorPicker' onChange='javascript: switch_style( document.getElementById( "colorPicker" ).value )'>
							<option value='white'>Ice</option>
							<option value='green' selected>Leafy</option>
						</select></p>
						<br><br>
<form method='post'>
<input name='save_settings' type='hidden' value=''>
<!-- Would be better to handle this as an ajax call, but for now this is it -->
<input value='Save Changes' type='submit' onClick='javascript: save_settings.value="Save"; return( true );'>
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>
<?php
		}
?>



<?php
		// Only show the registration tab if no user is logged in.
		if( ! $isLoggedIn )
		{
?>
			<div class='tab' id='register'> <a href='#register'> <img class='<?php echo $lockIcon;?>' src='images/img_trans.gif' width='1' height='1' alt='<?php echo $lockAlt;?>'/> Register </a>
				<div class='container'>
					<div class='tab-toolbar'>
					Enter ysour log in details here.
					</div>
					<div class='content'>
						<br><hr>
<?php
						//require_once( __ROOT__ . '/lib/users/register.class' );
						//$user = new register();
						//$user->displayForm();
?>
						<br>Stuff goes in here to allow a user to create an ID on the system.
						<br>After they are verified and logged in, they can edit locations and thermostats on a different tab.
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>
<?php
		}
?>


			<div class='tab' id='about'> <a href='#about'><img class='tab-sprite info' src='images/img_trans.gif' width='1' height='1' alt='icon: about'/> About </a>
				<div class='container'>
					<div class='content'>
						<br>
						<p>
						<p>Source code for this project can be found on <a target='_blank' href='https://github.com/ThermoMan/3M-50-Thermostat-Tracking'>github</a>
						<p>
						<br><br>The project originated on Windows Home Server v1 running <a target='_blank' href='http://www.apachefriends.org/en/xampp.html'>xampp</a>. Migrated to a 'real host' to solve issues with Windows Scheduler.
						<br>I used <a target='_blank' href='http://www.winscp.net'>WinSCP</a> to connect and edited the code using <a target='_blank' href='http://www.textpad.com'>TextPad</a>.
						<p>
						<p>This project also uses code from the following external projects
						<ul style='list-style-type: circle; margin-left: 20px;'>
							<li style='margin-top: 11px;'><a target='_blank' href='http://www.pchart.net/'>pChart</a>.</li>
							<li style='margin-top: 11px;'><a target='_blank' href='https://github.com/ThermoMan/Tabbed-Interface-CSS-Only'>Tabbed-Interface-CSS-Only</a> by ThermoMan.</li>
							<li style='margin-top: 11px;'><a target='_blank' href='http://www.customicondesign.com//'>Free for non-commercial use icons from Custom Icon Designs</a>.	These icons are in the package <a target='_blank' href='http://www.veryicon.com/icons/system/mini-1/'>Mini 1 Icons</a>.</li>
							<li style='margin-top: 11px;'><a target='_blank' href='http://www.stevedawson.com/article0014.php'>Password access loosely based on code by Steve Dawson</a>.</li>
							<li >The external temperatures and forecast come from <a target='_blank' href='http://www.wunderground.com/weather/api/'><img style='position:relative; top:10px; height:31px; border:0;' src='http://icons.wxug.com/logos/PNG/wundergroundLogo_4c_horz.png' alt='Weather Underground Logo'></a></li>
						</ul>
						<br><p>This project is based on the <a target='_blank' href='http://www.radiothermostat.com/filtrete/products/3M-50/'>Filtrete 3M Radio Thermostat</a>.
						<br><br><br><br>
						<div style='text-align: center;'>
							<a target='_blank' href='http://validator.w3.org/check?uri=referer'><img style='border:0;width:88px;height:31px;' src='images/valid-html5.png' alt='Valid HTML 5'/><div class='caveat'><!-- ANY whitespace between the start of the anchor and the start of the div adds an underscore to the page -->
								<br>
								<ul>
									<li>The first warning '<b><img class='caveat' src='images/w3c_info.png' alt='Info'>Using experimental feature: HTML5 Conformance Checker.</b>' is provisional until the HTML5 specification is complete.</li>
									<li>The 2 reported errors '<b><img class='caveat' src='images/w3c_error.png' alt='Error'>Attribute size not allowed on element input at this point.</b>' reported on use of the attribute "size" where input type="date" are incorrect because the HTML 5 validator is provisional until the specification is complete.</li>
									<li>The 2 other reported warnings '<b><img class='caveat' src='images/w3c_warning.png' alt='Warning'>The date input type is so far supported properly only by Opera. Please be sure to test your page in Opera.</b>' may also be read to include Chrome.</li>
<!--									<li>The final warning '<b><img class='caveat' src='images/w3c_warning.png' alt='Warning'>The scoped attribute on the style element is not supported by browsers yet. It would probably be better to wait for implementations.'</b> complains if the style is not scoped and differently when it is. The style that it is complaining about is local only to this very message and therefore should <i>not</i> be global.</li> -->
								</ul>
								<br>
							</div></a> <!-- ANY whitespace between the end of the div and the end of the anchor adds an underscore to the page -->
							<a target='_blank' href='http://jigsaw.w3.org/css-validator/check/referer'><img style='border:0;width:88px;height:31px;' src='http://jigsaw.w3.org/css-validator/images/vcss' alt='Valid CSS!'/></a>
							<br><br><br>The HTML5 components are tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser.	They do not work (manually type in the date) in Firefox.	I've not tested the functionality in IE.	The HTML validator suggests that the HTML 5 components may also work in Opera.
 						</div>
						<br><br><br><br>

						<div style='text-align: center;'>
<?php
	$uptime = @exec( 'uptime' );
	if( strstr( $uptime, 'days' ) )
	{
		if( strstr( $uptime, 'min' ) )
		{
			preg_match( "/up\s+(\d+)\s+days,\s+(\d+)\s+min/", $uptime, $times );
			$days = $times[1];
			$hours = 0;
			$mins = $times[2];
		}
		else
		{
			preg_match( "/up\s+(\d+)\s+days,\s+(\d+):(\d+),/", $uptime, $times );
			$days = $times[1];
			$hours = $times[2];
			$mins = $times[3];
		}
	}
	else
	{
		preg_match( "/up\s+(\d+):(\d+),/", $uptime, $times );
		$days = 0;
		$hours = $times[1];
		$mins = $times[2];
	}
	preg_match( "/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $uptime, $avgs );
	$load = $avgs[1].", ".$avgs[2].", ".$avgs[3]."";

	echo "<br>Server Uptime: $days days $hours hours $mins minutes";
	echo "<br>Average Load: $load";
?>
						</div>
					</div>
				</div>
			</div>

		</div>

	</div>
	</body>
</html>