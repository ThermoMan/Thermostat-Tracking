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
//global $password;
$isLoggedIn = false;	// Default to logged out.
if( isset($_POST['password']) && ($_POST['password'] == $password ) )
{ // Update logged in status to true only when the correct password has been entered.
	$isLoggedIn = true;
}

// Now do things that depend on that newly determined login status
// Set Config tab icon default value
$lockIcon = 'images/Lock.png';			// Default to locked
$lockAlt = 'icon: lock';
if( $isLoggedIn )
{
	// Set Config tab icon logged-in value
	$lockIcon = 'images/Unlock.png';	// Change to UNlocked icon only when user is logged in
	$lockAlt = 'icon: unlock';
}
?>

<html>
	<head>
		<meta http-equiv=Content-Type content='text/html; charset=utf-8'>
		<title>3M-50 Thermostat Tracking</title>
		<link href='favicon.ico' rel='shortcut icon' type='image/x-icon' />
		<link href='resources/thermo.css' rel='stylesheet' type='text/css' />
		<link href='lib/tabs/tabsE.css' rel='stylesheet' type='text/css' />		 <!-- Add tab library -->

		<script type='text/javascript'>
			function display_daily_temperature()
			{
				// Redraw the placekeeper while the chart is rendering
				document.getElementById( 'daily_temperature_chart' ).src = 'images/daily_temperature_placeholder.png';
				// Perhaps replace this with an animated GIF?

				var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
				var daily_from_date_string = 'chart.daily.fromDate=' + document.getElementById( 'chart.daily.fromDate' ).value;
				var daily_to_date_string = 'chart.daily.toDate=' + document.getElementById( 'chart.daily.toDate' ).value;

				/**
				  * If the user requests more than about 90 days it will take more than 30 seconds to render
				  *	If it takes more than 30 seconds to render the chart package pukes.
				  * Solve this perhaps by only getting one temperature per hour when span is 90+ days?
				  *
				  */

				var show_heat_cycle_string = 'chart.daily.showHeat=' + document.getElementById( 'chart.daily.showHeat' ).checked;
				var show_cool_cycle_string = 'chart.daily.showCool=' + document.getElementById( 'chart.daily.showCool' ).checked;
				var show_fan_cycle_string	= 'chart.daily.showFan='	+ document.getElementById( 'chart.daily.showFan' ).checked;
				var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.	In this case it breaks the web page function.
				var url_string = 'draw_daily.php?' + show_thermostat_id + '&' + daily_from_date_string + '&' + daily_to_date_string + '&' + show_heat_cycle_string	+ '&' + show_cool_cycle_string	+ '&' + show_fan_cycle_string + '&' + no_cache_string;
				document.getElementById( 'daily_temperature_chart' ).src = url_string;
			}

			function display_daily_temperature_table()
			{
				var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
				var table_flag = 'table_flag=true';
				var daily_from_date_string = 'chart.daily.fromDate=' + document.getElementById( 'chart.daily.fromDate' ).value;
				var daily_to_date_string = 'chart.daily.toDate=' + document.getElementById( 'chart.daily.toDate' ).value;
				var show_heat_cycle_string = 'chart.daily.showHeat=false';
				var show_cool_cycle_string = 'chart.daily.showCool=false';
				var show_fan_cycle_string	= 'chart.daily.showFan=false';
				var url_string = 'draw_daily.php?' + show_thermostat_id + '&' + table_flag + '&' + daily_from_date_string + '&' + daily_to_date_string + '&' + show_heat_cycle_string	+ '&' + show_cool_cycle_string	+ '&' + show_fan_cycle_string;
				<!-- document.getElementById( 'foo' ).value = url_string; -->
				document.getElementById( 'daily_temperature_table' ).innerHTML = '<iframe src="'+url_string+'" height="430px" width="900px"></iframe>';
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


			function display_historic_chart()
			{
				var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
				var show_indoor = 'Indoor=' + document.getElementById( 'history_selection' ).value;
				var show_hvac_runtime = 'show_hvac_runtime=' + document.getElementById( 'show_hvac_runtime' ).checked;
				var history_from_date_string = 'history_from_date=' + document.getElementById( 'history_from_date' ).value;
				var history_to_date_string = 'history_to_date=' + document.getElementById( 'history_to_date' ).value;
				var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.	That cleverness breaks this web page's function.
				var url_string = 'draw_weekly.php?' + show_thermostat_id + '&' + show_indoor + '&' + show_hvac_runtime + '&' + history_from_date_string + '&' + history_to_date_string	+ '&' + no_cache_string;
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
			// function setCookie( c_name, value, exdays = 3650 )
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
			function deleteCookies()
			{
				setCookie( 'auto_refresh', '', -1 );
				setCookie( 'chart.daily.showHeat', '', -1 );
				setCookie( 'chart.daily.showCool', '', -1 );
				setCookie( 'chart.daily.showFan', '', -1 );
				setCookie( 'chart.daily.fromDate', '', -1 );
				setCookie( 'chart.daily.toDate', '', -1 );
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
		</script>

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
						<br>
						<table border='1' class='thermostatList'>
							<tr>
								<th>Name</th>
								<th>Description</th>
								<th>Last Temperature</th>
<!-- These items are not for public consumption -->
<!--	<th>IP</th> -->
<!--	<th>Model</th> -->
<!--	<th>Firmware</th> -->
<!--	<th>WLAN Firmware</th> -->
								</tr>
<?php
					foreach ($thermostats as $thermostatRec):
?>
								<tr>
								 <td style='text-align: right'><a href='index.php?id=<?php echo $thermostatRec['id']?>'><?php echo $thermostatRec['name'] ?></a></td>
								 <td style='text-align: left'><?php echo $thermostatRec['description'] ?></td>
								 <td style='text-align: center'>XX</td>
							 </tr>
<?php
					endforeach;
?>
						</table>
						<br><br><br><br>
						<br>Trying to work out how to get around cross-site scripting to get this page to function in the fashion I want.<br>
						<p style='margin: 0px; margin-left: 50px'>The present temperature is <span id='temperature_state'>unknown</span> &deg;<?php echo $weatherConfig['units'] ?></p>
						<p style='margin: 0px; margin-left: 50px'>The heater is (ON/OFF) <span id='heat_state'>unknown</span></p>
						<p style='margin: 0px; margin-left: 50px'>The compressor is (ON/OFF) <span id='cool_state'>unknown</span></p>
						<p style='margin: 0px; margin-left: 50px'>The fan is (ON/OFF) <span id='fan_state'>unknown</span></p>
						<br><br>
						<br><br>The HTML5 components are tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser.	They do not work (manually type in the date) in Firefox.	I've not tested the functionality in IE.	The HTML validator suggests that the HTML 5 components may also work in Opera.
						<!-- Kick off dashboard refresh timers -->
						<script type='text/javascript'>
						// setTimeout( 'updateStatus();', updateStatusInterval );
						</script>
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>



			<div class='tab' id='daily'> <a href='#daily'> Daily Detail </a>
				<div class='container'>
					<div class='tab-toolbar'>
						<input type='button' onClick='javascript: display_daily_temperature();' value='Refresh'>
						Choose thermostat
						<select id='thermostat_id' name='thermostat_id' onChange='javascript: display_daily_temperature();'>
							<?php foreach( $thermostats as $thermostatRec ): ?>
								<option <?php if( $id == $thermostatRec['id'] ): echo 'selected '; endif; ?>value='<?php echo $thermostatRec['id'] ?>'><?php echo $thermostatRec['name'] ?></option>
							<?php endforeach; ?>
						</select>
<!-- <button type='button' onClick='javascript: show_date.stepDown(); display_daily_temperature();'>&lt;&#8212;</button> -->
						<!-- Need to change the max value to a date computed by Javascript so it stays current -->
<!-- &nbsp;Choose Date<input type='date' id='show_date' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_daily_temperature();' step='1'/> -->
						&nbsp;From Date<input type='date' id='chart.daily.fromDate' size='10' value='<?php echo date( 'Y-m-d', time() - 259000 ); ?>' max='<?php echo $show_date; ?>' onInput='javascript: update_daily_value( "chart.daily.fromDate" );' step='1'/>
						&nbsp;To Date<input type='date' id='chart.daily.toDate' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' onInput='javascript: update_daily_value( "chart.daily.toDate" );' step='1'/>
<!-- <button type='button' name='show_date' onClick='javascript: document.getElementById("show_date").stepUp(); display_daily_temperature();'>&#8212;&gt;</button> -->
						&nbsp;Heat Cycles<input type='checkbox' id='chart.daily.showHeat' name='chart.daily.showHeat' onChange='javascript: toggle_daily_flag( "chart.daily.showHeat" );'/>
						&nbsp;Cool Cycles<input type='checkbox' id='chart.daily.showCool' name='chart.daily.showCool' onChange='javascript: toggle_daily_flag( "chart.daily.showCool" );'/>
						&nbsp;Fan Cycles<input type='checkbox' id='chart.daily.showFan'	name='chart.daily.showFan'	onChange='javascript: toggle_daily_flag( "chart.daily.showFan" );'/>
<!-- Not yet working so hide it from user until it does...
						<input type='checkbox' id='auto_refresh'		 name='auto_refresh'		 onChange='javascript: timedRefresh();'/>Auto refresh
						<span id='daily_update' style='float: right; vertical-align: middle; visibility: hidden;'>Countdown to refresh: 00:00</span>
-->
						<span style='float: right;'>UN-save settings<input type='button' onClick='javascript: deleteCookies();' value='Clear'></span>
					</div>
					<div class='content'>
						<br>
						<div class='thermo_chart'>
							<img id='daily_temperature_chart' src='images/daily_temperature_placeholder.png' alt='The temperatures'>
						</div>

						<!-- This initialization script must fall AFTER declaration of various inputs -->
						<script type='text/javascript'>
							document.getElementById('chart.daily.showCool').checked = getCookie('chart.daily.showCool');
							document.getElementById('chart.daily.showHeat').checked = getCookie('chart.daily.showHeat');
							document.getElementById('chart.daily.showFan').checked = getCookie('chart.daily.showFan');

							// Test values before use, don't let null (not set) chnage the defaults
							if( getCookie('chart.daily.fromDate') )
								document.getElementById('chart.daily.fromDate').value = getCookie('chart.daily.fromDate');
							if( getCookie('chart.daily.toDate') )
								document.getElementById('chart.daily.toDate').value = getCookie('chart.daily.toDate');
/* Set a timer to implement the auto refresh
"chart.daily.autoRefresh"

		if( getCookie( 'auto_refresh' ) == 'true' )
		{
			document.getElementById( 'auto_refresh' ).checked = true;	 // Simply setting the check box does not start the timer.
			timedRefresh();																						 // So start the timer too
		}
*/
							display_daily_temperature(); // Draw the chart using the applied settings
						</script>
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>



			<div class='tab' id='daily_table'> <a href='#daily_table'> Daily (Table) </a>
				<div class='container'>
					<div class='tab-toolbar'>
						Table uses same date range as the 'Daily Detail' <input type='button' onClick='javascript: display_daily_temperature_table();' value='Chart'>
						<span style='display: inline-block; float: right;'>&nbsp;UN-save settings<input type='button' onClick='javascript: deleteCookies();' value='Clear'></span>
					</div>
					<div class='content'>
						<!-- keep this around, it might be useful -->
						<!-- <textarea id='foo' rows='2' cols='110' disabled>default</textarea> -->
						<div id='daily_temperature_table'>
							<table><tr><th>Placekeeper header</th></tr><tr><td>Placekeeper data</td></tr></table>
						</div>
					</div>
				</div>
			</div>
			<div class='tab_gap'></div>



			<div class='tab' id='history'> <a href='#history'> History </a>
				<div class='container'>
					<div class='tab-toolbar'>
						<select id='history_selection' onClick='javascript: display_historic_chart();'>
							<option selected='selected' value='0'>Outoor</option>
							<option value='1'>Indoor</option>
							<option value='2'>Both</option>
						</select>
						Show HVAC Runtimes<input type='checkbox' id='show_hvac_runtime' name='show_hvac_runtime' onChange='javascript: display_historic_chart();'/>
						<!-- Show initial range as from 3 weeks ago through today -->
						From date <input type='date' id='history_from_date' size='10' value='<?php echo date( 'Y-m-d', strtotime( '3 weeks ago' ) ); ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_historic_chart();' step='1'/>
						to date <input type='date' id='history_to_date' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_historic_chart();' step='1'/>
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
<?php
		}
		else
		{
			echo 'You must enter password to access config information.';
		}
?>
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
							<li>The external temperatures come from <a target='_blank' href='http://www.wunderground.com/weather/api/'><img style='position:relative; top:10px; height:31px; border:0;' src='http://icons.wxug.com/logos/PNG/wundergroundLogo_4c_horz.png'></a></li>
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