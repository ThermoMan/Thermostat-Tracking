<!DOCTYPE html>
<?php
require_once 'common.php';

date_default_timezone_set( $timezone );
$show_date = date( 'Y-m-d', time() );  // Start with today's date
if( strftime( '%H%M' ) <= '0059' )
{ // If there is not enough (3 data points is 'enough') data to make a meaningful chart, default to yesterday
  $show_date =  date( 'Y-m-d', strtotime( '-1 day', time() ) );  // Start with yesterday's date
}

$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '';  // Set default thermostat selection

// Login status
$password = 'admin';  // This password should be in the config file!
$isLoggedIn = false;  // By default set logged in status to false.
if( isset($_POST['password']) && ($_POST['password'] == $password ) )
{ // Update logged in status to true only when the correct password has been entered.
  $isLoggedIn = true;
}

// Now do things that depend on that newly determined login status
// Set Config tab icon default value
$lockIcon = 'images/Lock.png';      // Default to locked
$lockAlt = 'icon: lock';
if( $isLoggedIn )
{
  // Set Config tab icon logged-in value
  $lockIcon = 'images/Unlock.png';  // Change to UNlocked icon only when user is logged in
  $lockAlt = 'icon: unlock';
}
?>

<html>
  <head>
    <meta http-equiv=Content-Type content='text/html; charset=utf-8'>
    <title>3M-50 Thermostat Tracking</title>
    <link href='favicon.ico' rel='shortcut icon' type='image/x-icon' />
    <link href='resources/thermo.css' rel='stylesheet' type='text/css' />
    <link href='lib/tabs/tabsE.css' rel='stylesheet' type='text/css' />     <!-- Add tab library -->

    <script type='text/javascript'>
      function display_daily_temperature()
      {
				// Redraw the placekeeper while the chart is rendering
				document.getElementById( 'daily_temperature_chart' ).src = 'images/daily_temperature_placeholder.png';
				// Perhaps replace this with an animated GIF?

        var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
        var daily_from_date_string = 'daily_from_date=' + document.getElementById( 'daily_from_date' ).value;
        var daily_to_date_string = 'daily_to_date=' + document.getElementById( 'daily_to_date' ).value;

				/**
				  * If the user requests more than about 90 days it will take more than 30 seconds to render
				  *  If it takes more than 30 seconds to render the chart package pukes.
				  * Solve this perhaps by only getting one temperature per hour when span is 90+ days?
					*
					*/

        var show_heat_cycle_string = 'show_heat_cycles=' + document.getElementById( 'show_heat_cycles' ).checked;
        var show_cool_cycle_string = 'show_cool_cycles=' + document.getElementById( 'show_cool_cycles' ).checked;
        var show_fan_cycle_string  = 'show_fan_cycles='  + document.getElementById( 'show_fan_cycles' ).checked;
        var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.  In this case it breaks the web page function.
        var url_string = 'draw_daily.php?' + show_thermostat_id + '&' + daily_from_date_string + '&' + daily_to_date_string + '&' + show_heat_cycle_string  + '&' + show_cool_cycle_string  + '&' + show_fan_cycle_string + '&' + no_cache_string;
        document.getElementById( 'daily_temperature_chart' ).src = url_string;
      }

			function display_daily_temperature_table()
			{
        var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
				var table_flag = 'table_flag=true';
        var daily_from_date_string = 'daily_from_date=' + document.getElementById( 'daily_from_date' ).value;
        var daily_to_date_string = 'daily_to_date=' + document.getElementById( 'daily_to_date' ).value;
        var show_heat_cycle_string = 'show_heat_cycles=false';
        var show_cool_cycle_string = 'show_cool_cycles=false';
        var show_fan_cycle_string  = 'show_fan_cycles=false';
        var url_string = 'draw_daily.php?' + show_thermostat_id + '&' + table_flag + '&' + daily_from_date_string + '&' + daily_to_date_string + '&' + show_heat_cycle_string  + '&' + show_cool_cycle_string  + '&' + show_fan_cycle_string;
				<!-- document.getElementById( 'foo' ).value = url_string; -->
        document.getElementById( 'daily_temperature_table' ).innerHTML = '<iframe src="'+url_string+'" height="430px" width="900px"></iframe>';
			}


      function display_historic_chart()
      {
        var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
        var show_indoor = 'Indoor=' + document.getElementById( 'history_selection' ).value;
        var show_hvac_runtime = 'show_hvac_runtime=' + document.getElementById( 'show_hvac_runtime' ).checked;
        var history_from_date_string = 'history_from_date=' + document.getElementById( 'history_from_date' ).value;
        var history_to_date_string = 'history_to_date=' + document.getElementById( 'history_to_date' ).value;
        var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.  That cleverness breaks this web page's function.
        var url_string = 'draw_weekly.php?' + show_thermostat_id + '&' + show_indoor + '&' + show_hvac_runtime + '&' + history_from_date_string + '&' + history_to_date_string  + '&' + no_cache_string;
        document.getElementById( 'history_chart' ).src = url_string;
      }


      var refreshInterval = 20 * 60 * 1000;  // It's measured in milliseconds and I want a unit of minutes.
      function timedRefresh()
      {
        // Save value (either true or false) for next time (keep cookie up to ten days)
        setCookie( 'auto_refresh', document.getElementById( 'auto_refresh' ).checked, 10 );

        if( document.getElementById( 'auto_refresh' ).checked == true )
        {
          document.getElementById( 'daily_update' ).style.visibility = 'visible';
          /*
           * Need to add a check here for present day.  Only present day actually has changing data
           * so don't bother with refresh on historic data.  But do leave the refresh flag set for later.
           */
          update_daily_chart();
          setTimeout( 'timedRefresh();', refreshInterval );
        }
        else
        {
          document.getElementById( 'daily_update' ).style.visibility = 'hidden';
        }
      }

      function setCookie( c_name, value, exdays )
      {
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

      /*
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

    </script>
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
<!--  <th>IP</th> -->
<!--  <th>Model</th> -->
<!--  <th>Firmware</th> -->
<!--  <th>WLAN Firmware</th> -->
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
            <br><br>The HTML5 components are tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser.  They do not work (manually type in the date) in Firefox.  I've not tested the functionality in IE.  The HTML validator suggests that the HTML 5 components may also work in Opera.
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
            &nbsp;From Date<input type='date' id='daily_from_date' size='10' value='<?php echo date( 'Y-m-d', time() - 259000 ); ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_daily_temperature();' step='1'/>
            &nbsp;To Date<input type='date' id='daily_to_date' size='10' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_daily_temperature();' step='1'/>
<!-- <button type='button' name='show_date' onClick='javascript: document.getElementById("show_date").stepUp(); display_daily_temperature();'>&#8212;&gt;</button> -->
            &nbsp;Heat Cycles<input type='checkbox' id='show_heat_cycles' name='show_heat_cycles' onChange='javascript: display_daily_temperature();'/>
            &nbsp;Cool Cycles<input type='checkbox' id='show_cool_cycles' name='show_cool_cycles' onChange='javascript: display_daily_temperature();'/>
            &nbsp;Fan Cycles<input type='checkbox' id='show_fan_cycles'  name='show_fan_cycles'  onChange='javascript: display_daily_temperature();'/>
<!-- Not yet working so hide it from user until it does...
            <input type='checkbox' id='auto_refresh'     name='auto_refresh'     onChange='javascript: timedRefresh();'/>Auto refresh
            <span id='daily_update' style='float: right; vertical-align: middle; visibility: hidden;'>Countdown to refresh: 00:00</span>
-->
          </div>
          <div class='content'>
            <br>
            <div class='thermo_chart'>
              <img id='daily_temperature_chart' src='images/daily_temperature_placeholder.png' alt='The temperatures'>
            </div>

            <!-- This initialization script must fall AFTER declaration of date input box -->
            <script type='text/javascript'>
/* Use cookies to track all check boxes on this screen.
"chart.daily.showHeat" = true/false or 1/0 whatever is direct transfer of the .value setting
"chart.daily.showCool"
"chart.daily.showFan"
"chart.daily.autoRefresh"
Perhaps in the header transfer the cookie alies to internal variables and then here move into checkboxes ... no, too complicated.
Just read the cookie here and load the default value and refresh the chart accordingly
And on checkbox change, save the new cookie value (so a missing cookie is same as false)
    // Set initial values for chart
    document.getElementById('show_date').value = '<?php echo $show_date; ?>';
    document.getElementById('show_heat_cycles').checked = false;
    document.getElementById('show_cool_cycles').checked = false;
    document.getElementById('show_fan_cycles').checked = false;


    if( getCookie( 'auto_refresh' ) == 'true' )
    {
      document.getElementById( 'auto_refresh' ).checked = true;   // Simply setting the check box does not start the timer.
      timedRefresh();                                             // So start the timer too
    }
*/
              display_daily_temperature(); // Draw the chart
            </script>
          </div>
        </div>
      </div>
      <div class='tab_gap'></div>



      <div class='tab' id='daily_table'> <a href='#daily_table'> Daily (Table) </a>
        <div class='container'>
          <div class='tab-toolbar'>
            Table uses same date range as the 'Daily Detail' <input type='button' onClick='javascript: display_daily_temperature_table();' value='Chart'>
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
?>
						<form method='post'>
							<input name='password' type='password' size='25' maxlength='25'><input value='Login' type='submit'>Please enter your password for access.
						</form>
<?php
			if( isset($_POST['password']) && ($_POST['password'] != $password ) )
			{
				echo "<font color='red'> Incorrect Username or Password - I think you typed &quot; {$_POST['password']} ?>&quot; </font>";
			}
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
                <td align='center'><input type='button' value='Edit'></td>
             </tr>
<?php
      endforeach;
?>
            </table>
<?php
    }
    else
    {
//      if( ! $isLoggedIn )
//      { // Default presentation before password is entered
        echo 'You must enter password to access config information.';
//      }
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
							<li><a target='_blank' href='http://www.customicondesign.com//'>Free for non-commercial use icons from Custom Icon Designs</a>.  These icons are in the package <a target='_blank' href='http://www.veryicon.com/icons/system/mini-1/'>Mini 1 Icons</a>.</li>
							<li><a target='_blank' href='http://www.stevedawson.com/article0014.php'>Password access loosely based on code by Steve Dawson</a>.</li>
							<li>The external temperatures come from the <a target='_blank' href='http://www.wunderground.com/weather/api/'>Weather Underground API</a></li>
            </ul>
						<p>This project is based on the <a target='_blank' href='http://www.radiothermostat.com/filtrete/products/3M-50/'>Filtrete 3M Radio Thermostat</a>.
						<br><br>
						<div style='text-align: center; border: 1px solid; margin: 0px 100px 0px 60px; padding: 10px'>
							<a target='_blank' href='http://validator.w3.org/check?uri=referer'><img style='border:0;width:88px;height:31px;' src='images/valid-html5.png' alt='Valid HTML 5'/></a>
							<a target='_blank' href='http://jigsaw.w3.org/css-validator/check/referer'><img style='border:0;width:88px;height:31px;' src='http://jigsaw.w3.org/css-validator/images/vcss' alt='Valid CSS!'/></a>
							<ul>
							<li style='text-align: left;'>The first warning '<b>Using experimental feature: HTML5 Conformance Checker.</b>' is provisional until the HTML5 specification is complete.</li>
							<li style='text-align: left;'>The 4 reported errors '<b>Attribute size not allowed on element input at this point.</b>' reported on use of the attribute "size" where input type="date" are incorrect because the HTML 5 validator is provisional until the specification is complete.</li>
							<li style='text-align: left;'>The 4 reported warnings '<b>The date input type is so far supported properly only by Opera. Please be sure to test your page in Opera.</b>' may also be read to include Chrome.</li>
							</ul>
						</div>

						<div style='text-align: center;'>
<?php
  $uptime = @exec('uptime');
  if ( strstr($uptime, 'days') )
  {
    if ( strstr($uptime, 'min') )
    {
      preg_match("/up\s+(\d+)\s+days,\s+(\d+)\s+min/", $uptime, $times);
      $days = $times[1];
      $hours = 0;
      $mins = $times[2];
    }
    else
    {
      preg_match("/up\s+(\d+)\s+days,\s+(\d+):(\d+),/", $uptime, $times);
      $days = $times[1];
      $hours = $times[2];
      $mins = $times[3];
    }
  }
  else
  {
    preg_match("/up\s+(\d+):(\d+),/", $uptime, $times);
    $days = 0;
    $hours = $times[1];
    $mins = $times[2];
  }
  preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $uptime, $avgs);
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