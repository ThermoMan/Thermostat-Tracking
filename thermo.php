<!DOCTYPE html>
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
$lockIcon = 'images/Lock.png';			// Default to locked
$lockAlt = 'icon: lock';
$verifyText = 'No';
if( $isLoggedIn )
{
	// Set Config tab icon logged-in value
	$lockIcon = 'images/Unlock.png';	// Change to UNlocked icon only when user is logged in
	$lockAlt = 'icon: unlock';

	if( isset($_POST['save_settings'] ) )
	{
		$verifyText = 'Yes';
		//save_settings();
	}
}
?>

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

		<script type='text/javascript'>
				/**
				* chart is one of 'daily' or 'history'
				* sytle is one of 'chart' or 'table'
				  *
				  */
			function display_chart( chart, style )
			{
				var chart_target;
				var table_flag = '';
				if( chart == 'daily' && style == 'chart' )
				{
					table_flag = 'table_flag=false';
					chart_target = document.getElementById( 'daily_temperature_chart' );
					chart_target.src = 'images/daily_temperature_placeholder.png';	// Redraw the placekeeper while the chart is rendering
				}
				else if( chart == 'daily' && style == 'table' )
				{
					table_flag = 'table_flag=true';
				}
				else
				{
					alert( 'You asked for '+chart+' and '+style+' and I do not know how to do that (yet)' );
					return;
				}

				var show_thermostat_id     = 'id='                          + document.getElementById( 'chart.daily.thermostat' ).value;
				var daily_setpoint_selection = 'chart.daily.setpoint='      + document.getElementById( 'chart.daily.setpoint' ).checked;
				var daily_source_selection = 'chart.daily.source='          + document.getElementById( 'chart.daily.source' ).value;
				var daily_interval_length  = 'chart.daily.interval.length=' + document.getElementById( 'chart.daily.interval.length' ).value;
				var daily_interval_group   = 'chart.daily.interval.group='  + document.getElementById( 'chart.daily.interval.group' ).value;
				var daily_to_date_string   = 'chart.daily.toDate='          + document.getElementById( 'chart.daily.toDate' ).value;
				var show_heat_cycle_string = 'chart.daily.showHeat='        + document.getElementById( 'chart.daily.showHeat' ).checked;
				var show_cool_cycle_string = 'chart.daily.showCool='        + document.getElementById( 'chart.daily.showCool' ).checked;
				var show_fan_cycle_string  = 'chart.daily.showFan='         + document.getElementById( 'chart.daily.showFan' ).checked;

				// Browsers are very clever with image caching.	In this case it breaks the web page function.
				var no_cache_string = 'nocache=' + Math.random();

				var url_string = '';
				if( chart == 'daily' )
				{
					url_string = 'draw_daily.php';
			}
				else if( chart == 'history' )
				{
				}

				url_string = url_string + '?' + show_thermostat_id + '&' + daily_source_selection + '&' + daily_setpoint_selection + '&' + table_flag + '&' +
										 daily_interval_length + '&' + daily_interval_group + '&' + daily_to_date_string + '&' +
										 show_heat_cycle_string	+ '&' + show_cool_cycle_string	+ '&' + show_fan_cycle_string + '&' +
										 no_cache_string;

				if( style == 'chart' )
			{
					chart_target.src = url_string;
				}
				else if( style == 'table' )
				{	// Right now it assumes the DAILY table.  Fix that later
					//document.getElementById( 'daily_temperature_table' ).innerHTML = '<iframe src="'+url_string+'"></iframe>';
					//document.getElementById( 'daily_temperature_table' ).innerHTML = '<iframe src="'+url_string+'" width="450"></iframe>';
					document.getElementById( 'daily_temperature_table' ).innerHTML = '<iframe src="'+url_string+'" height="100" width="530"></iframe>';
				}
			}


			/**
			  *	Save the value of the checkbox for later - and update the chart with the new value
			  */
			function toggle_daily_flag( flag )
			{
				setCookie( flag, document.getElementById(flag).checked );
				display_daily_temperature();
			}

			/**
			  *	Save the value of the field for later - and update the chart with the new value
			  */
			function update_daily_value( field )
			{
				setCookie( field, document.getElementById(field).value );
				display_daily_temperature();
			}

//Change names of the IDs to match this naming convention 'chart.history.toDate' instead of this convention 'history_to_date'
			function display_historic_chart()
			{
				var show_thermostat_id = 'id=' + document.getElementById( 'chart.history.thermostat' ).value;
				var show_indoor = 'Indoor=' + document.getElementById( 'history_selection' ).value;
				var show_hvac_runtime = 'show_hvac_runtime=' + document.getElementById( 'show_hvac_runtime' ).checked;

				var interval_measure_string = 'interval_measure=' + document.getElementById( 'interval_measure' ).value;
				var interval_length_string = 'interval_length=' + document.getElementById( 'interval_length' ).value;

				var history_to_date_string = 'history_to_date=' + document.getElementById( 'chart.history.toDate' ).value;

				var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.	That cleverness breaks this web page's function.

				var url_string = 'draw_weekly.php?' + show_thermostat_id + '&' + show_indoor + '&' + show_hvac_runtime + '&' + interval_measure_string + '&' + interval_length_string + '&' + history_to_date_string	+ '&' + no_cache_string;
				console.log( url_string );
				document.getElementById( 'history_chart' ).src = url_string;
			}


			var refreshInterval = 20 * 60 * 1000;	// It's measured in milliseconds and I want a unit of minutes.
			function timedRefresh()
			{
				// Save value (either true or false) for next time (keep cookie up to ten days)
				setCookie( 'auto_refresh', document.getElementById( 'auto_refresh' ).checked, 10 );

				if( document.getElementById( 'auto_refresh' ).checked == true )
				{
					document.getElementById( 'daily_update' ).style.visibility = 'visible';
					/**
					  * Need to add a check here for present day.	Only present day actually has changing data
					  * so don't bother with refresh on historic data.	But do leave the refresh flag set for later.
					  */
					update_daily_chart();
					setTimeout( 'timedRefresh();', refreshInterval );
				}
				else
				{
					document.getElementById( 'daily_update' ).style.visibility = 'hidden';
				}
			}

			/**
			  * Default cookies last for ten years.
			  *
			  * exdays is an optional parameter that defaults to ten years when missing.
			  */
			function setCookie( c_name, value, exdays )
			{
				// Chrome does not like to see '=' in the argument list of a function declaration.  Here is plan B.
				if( typeof( exdays ) === 'undefined' ) exdays = 3650;

				var exdate = new Date();
				exdate.setDate( exdate.getDate() + exdays );
				var c_value = escape(value) + ( ( exdays == null ) ? '' : '; expires = ' + exdate.toUTCString() );
				document.cookie = c_name + '=' + c_value;
			}

			function getCookie( c_name )
			{
				var i, key, value, ARRcookies = document.cookie.split( ';' );
				for( i = 0; i < ARRcookies.length; i++ )
				{
					key = ARRcookies[i].substr( 0, ARRcookies[i].indexOf( '=' ) );
					value = ARRcookies[i].substr( ARRcookies[i].indexOf( '=' ) + 1);
					key = key.replace( /^\s+|\s+$/g, '' );
					if( key == c_name )
					{
						return unescape( value );
					}
				}
			}

			/**
			  * To erase a cookie, set it with an expiration date prior to now.
			  */
			function deleteCookies( chart )
			{
				if( chart == 0 )
				{	// Clear cookies that remember daily settings
					setCookie( 'auto_refresh', '', -1 );
					setCookie( 'chart.daily.setpoint', '', -1 );
					setCookie( 'chart.daily.showHeat', '', -1 );
					setCookie( 'chart.daily.showCool', '', -1 );
					setCookie( 'chart.daily.showFan', '', -1 );
					setCookie( 'chart.daily.fromDate', '', -1 );
					setCookie( 'chart.daily.toDate', '', -1 );

					document.getElementById('chart.daily.setpoint').className = '';
					document.getElementById('chart.daily.showHeat').className = '';
					document.getElementById('chart.daily.showCool').className = '';
					document.getElementById('chart.daily.showFan').className = '';
					document.getElementById('chart.daily.fromDate').className = '';
					document.getElementById('chart.daily.toDate').className = '';
				}

				if( chart == 1 )
				{	// Clear cookies that remember history settings
					setCookie( 'chart.history.toDate', '', -1 );

					document.getElementById('chart.history.toDate').className = '';
				}
			}

			/**
			  * Either set up a countdown timer that both updates this clock AND triggers the chart update when hitting 0
			  * or set up a second timer that is a countdown clock and hope it stays in sync with the update routine.
			  */
			function showRefreshTime()
			{
				var today = new Date();
				var h = '' + today.getHours();
				var m = '' + today.getMinutes();
				//var s = today.getSeconds();
				if( h < 10 ) h = '0' + h;
				if( m < 10 ) m = '0' + m;

				document.getElementById( 'daily_update' ).innerHTML = 'Countdown to refresh: ' + h + ':' + m;
			}

			function doLogout()
			{
				alert( 'Not implemented' );
			}

			function processStatus()
			{
				if( xmlDoc.readyState != 4 ) return ;

				document.getElementById( 'status' ).innerHTML = xmlDoc.responseText;

				// For testing make it look like 3 thermostats
				document.getElementById( 'status' ).innerHTML = xmlDoc.responseText +'<br>'+
				                                                'The data is manually tripilcated to simulate multiple stats<br>' +
				                                                xmlDoc.responseText +'<br>'+
				                                                xmlDoc.responseText;

    	}

			function updateStatus()
			{
				// Need to add the Wheels icon to the sprite map and set relative position in the thermo.css file
				document.getElementById( 'status' ).innerHTML = "<p class='status'><img src='images/Wheels.png'>Looking up present conditions. (This may take some time)</p>";
				if( typeof window.ActiveXObject != 'undefined' )
				{
					xmlDoc = new ActiveXObject( 'Microsoft.XMLHTTP' );
					xmlDoc.onreadystatechange = process ;
				}
				else
				{
					xmlDoc = new XMLHttpRequest();
					xmlDoc.onload = processStatus;
				}
				xmlDoc.open( 'GET', 'get_instant_status.php', true );
				xmlDoc.send( null );

			}

			function switch_style( css_title )
			{
				// You may use this script on your site free of charge provided
				// you do not remove this notice or the URL below. Script from
				// http://www.thesitewizard.com/javascripts/change-style-sheets.shtml
				var i, link_tag;
				for( i = 0, link_tag = document.getElementsByTagName('link'); i < link_tag.length ; i++ )
				{
					if( (link_tag[i].rel.indexOf( 'stylesheet' ) != -1 ) && link_tag[i].title )
					{
						link_tag[i].disabled = true ;
						if( link_tag[i].title == css_title )
						{
							link_tag[i].disabled = false;
						}
					}
				}
			}
		</script>

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
		<!-- Internal variable declarations START -->
		<input type='hidden' name='id' value='<?php echo urlencode($id) ?>'>
		<!-- Internal variable declarations END -->

		<div class='all_tabs'>
			<div class='tab' id='dashboard'> <a href='#dashboard'> Dashboard </a>
				<div class='container'>
					<div class='tab-toolbar'>
						Is this font is too small?
					</div>
					<div class='content'>
						<br><br>
						<input type='button' onClick='javascript: updateStatus();' value='Refresh'>
						<div id='status' class='status'>Javascript must be enabled to see this content</div>
						<!-- Kick off dashboard refresh timers -->
						<script type='text/javascript'>
							updateStatus();
						</script>
						<br><br><br><br><br><br><br><br>The HTML5 components are tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser.	They do not work (manually type in the date) in Firefox.	I've not tested the functionality in IE.	The HTML validator suggests that the HTML 5 components may also work in Opera.
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
						<select>
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
							if( getCookie('chart.daily.showCool') )
							{
								document.getElementById('chart.daily.showCool').checked = true;									// Set flag
								// Checkbox styles are not obeyed by browsers, they are styled by the OS.
								document.getElementById('chart.daily.showCool').className = 'remembered_input';	// Set visual cue that cookie retains this value
							}
							if( getCookie('chart.daily.setpoint') )
							{
								document.getElementById('chart.daily.setpoint').checked = true;
								document.getElementById('chart.daily.setpoint').className = 'remembered_input';
							}

							if( getCookie('chart.daily.showHeat') )
							{
								document.getElementById('chart.daily.showHeat').checked = true;
								document.getElementById('chart.daily.showHeat').className = 'remembered_input';
							}
							if( getCookie('chart.daily.showFan') )
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
						temperatures for <input type='text' id='interval_length' value='21' size='3'>
						<select id='interval_measure' style='width: 65px'>
							<option value='0' selected>days</option>
							<option value='1'>weeks</option>
							<option value='2'>months</option>
						<select>
						ending on <input type='date' id='chart.history.toDate' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' step='1'/>
						&nbsp;&nbsp;Optionally show HVAC runtimes<input type='checkbox' id='show_hvac_runtime' name='show_hvac_runtime'/>
						<span style='float: right;'>UN-save settings<input type='button' onClick='javascript: deleteCookies(1);' value='Clear'></span>
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



			<div class='tab' id='config'> <a href='#config'> <img class='tab-icon' src='<?php echo $lockIcon;?>' alt='<?php echo $lockAlt;?>'/>Configure </a>
				<div class='container'>
					<div class='tab-toolbar'>
<?php
		// Prompt for pwd to login -or- present logout button
		if( ! $isLoggedIn )
		{
			echo '<form method="post">';
			echo '<input name="password" type="password" size="25" maxlength="25"><input value="Login" type="submit">Please enter your password for access.';
			if( isset($_POST['password']) && ($_POST['password'] != $password ) )
			{
				echo "<font color='red'> Incorrect Username or Password - I think you typed &quot; {$_POST['password']} ?>&quot; </font>";
			}
			echo '</form>';
		}
		if( $isLoggedIn )
		{
			echo '<input value="Logout" type="button" onClick="doLogout();">Logout doesn&rsquo;t work yet....';
		}
?>
					</div>
					<div class='content'>
						<br>
<?php
		// If password is valid let the user get access
		if( $isLoggedIn )
		{
?>
						<table border='1'>
							<tr>
								<th>Name</th>
								<th>Description</th>
								<th>IP</th>
								<th>Model</th>
								<th>Firmware</th>
								<th>WLAN Firmware</th>
								<th>Action</th>
							</tr>
<?php
			foreach( $thermostats as $thermostatRec ):
?>
							<tr>
								<td align='right'><a href='index.php?id=<?php echo $thermostatRec['id']?>'><?php echo $thermostatRec['name'] ?></a></td>
								<td align='left'><?php echo $thermostatRec['description'] ?></td>
								<td align='center'><?php echo $thermostatRec['ip'] ?></td>
								<td align='center'><?php echo $thermostatRec['model'] ?></td>
								<td align='left'><?php echo $thermostatRec['fw_version'] ?></td>
								<td align='left'><?php echo $thermostatRec['wlan_fw_version'] ?></td>
								<td align='center'><input type='button' value='Edit' onClick='javascript: alert("Not implemented");'></td>
						 </tr>
<?php
			endforeach;
?>
						</table>
						<p>Choose appearance: <select id='colorPicker' onChange='javascript: switch_style( document.getElementById( "colorPicker" ).value )'>
							<option value='white'>Ice</option>
							<option value='green' selected>Leafy</option>
						</select></p>
						<!-- Put save settings button here later -->
<?php
		}
		else
		{
			echo 'You must enter password to access config information.';
		}
?>
<form method='post'>
<input name='save_settings' type='hidden' value=''>
<!-- Would be better to handle this as an ajax call, but for now this is it -->
<input value='Save Changes' type='submit' onClick='javascript: save_settings.value="Save"; return( true );'>
<br>HERE[<?php echo $verifyText ?>]
</form>

					</div>
				</div>
			</div>
			<div class='tab_gap'></div>



			<div class='tab' id='about'> <a href='#about'><img class='tab-icon' src='images/Info.png' alt='icon: about'/> About </a>
				<div class='container'>
					<div class='content'>
						<br>
						<p>
						<p>Source code for this project can be found on <a target='_blank' href='https://github.com/ThermoMan/3M-50-Thermostat-Tracking'>github</a>
						<p>
						<br>The project originated on Windows Home Server v1 running <a target='_blank' href='http://www.apachefriends.org/en/xampp.html'>xampp</a>. Migrated to a 'real host' to solve issues with Windows Scheduler.
						<br>I used <a target='_blank' href='http://www.winscp.net'>WinSCP</a> to connect and edited the code using <a target='_blank' href='http://www.textpad.com'>TextPad</a>.
						<p>
						<p>This project also uses code from the following external projects
						<ul>
							<li><a target='_blank' href='http://www.pchart.net/'>pChart</a>.</li>
							<li><a target='_blank' href='https://github.com/ThermoMan/Tabbed-Interface-CSS-Only'>Tabbed-Interface-CSS-Only</a> by ThermoMan.</li>
							<li><a target='_blank' href='http://www.customicondesign.com//'>Free for non-commercial use icons from Custom Icon Designs</a>.	These icons are in the package <a target='_blank' href='http://www.veryicon.com/icons/system/mini-1/'>Mini 1 Icons</a>.</li>
							<li><a target='_blank' href='http://www.stevedawson.com/article0014.php'>Password access loosely based on code by Steve Dawson</a>.</li>
							<li>The external temperatures come from <a target='_blank' href='http://www.wunderground.com/weather/api/'><img style='position:relative; top:10px; height:31px; border:0;' src='http://icons.wxug.com/logos/PNG/wundergroundLogo_4c_horz.png' alt='Weather Underground Logo'></a></li>
						</ul>
						<p>This project is based on the <a target='_blank' href='http://www.radiothermostat.com/filtrete/products/3M-50/'>Filtrete 3M Radio Thermostat</a>.
						<br><br>
						<div style='text-align: center;'>
							<a target='_blank' href='http://validator.w3.org/check?uri=referer'><img style='border:0;width:88px;height:31px;' src='images/valid-html5.png' alt='Valid HTML 5'/><div class='caveat'><!-- ANY whitespace between the start of the anchor and the start of the div adds an underscore to the page -->
								<ul>
									<li>The first warning '<b><img class='caveat' src='images/w3c_info.png' alt='Info'>Using experimental feature: HTML5 Conformance Checker.</b>' is provisional until the HTML5 specification is complete.</li>
									<li>The 4 reported errors '<b><img class='caveat' src='images/w3c_error.png' alt='Error'>Attribute size not allowed on element input at this point.</b>' reported on use of the attribute "size" where input type="date" are incorrect because the HTML 5 validator is provisional until the specification is complete.</li>
									<li>The 4 reported warnings '<b><img class='caveat' src='images/w3c_warning.png' alt='Warning'>The date input type is so far supported properly only by Opera. Please be sure to test your page in Opera.</b>' may also be read to include Chrome.</li>
<!--									<li>The final warning '<b><img class='caveat' src='images/w3c_warning.png' alt='Warning'>The scoped attribute on the style element is not supported by browsers yet. It would probably be better to wait for implementations.'</b> complains if the style is not scoped and differently when it is. The style that it is complaining about is local only to this very message and therefore should <i>not</i> be global.</li> -->
								</ul>
							</div></a> <!-- ANY whitespace between the end of the div and the end of the anchor adds an underscore to the page -->
							<a target='_blank' href='http://jigsaw.w3.org/css-validator/check/referer'><img style='border:0;width:88px;height:31px;' src='http://jigsaw.w3.org/css-validator/images/vcss' alt='Valid CSS!'/></a>
 						</div>

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

	</body>
</html>
