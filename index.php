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
        var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
        var show_date_string = 'show_date=' + document.getElementById( 'show_date' ).value;
        var show_heat_cycle_string = 'show_heat_cycles=' + document.getElementById( 'show_heat_cycles' ).checked;
        var show_cool_cycle_string = 'show_cool_cycles=' + document.getElementById( 'show_cool_cycles' ).checked;
        var show_fan_cycle_string  = 'show_fan_cycles='  + document.getElementById( 'show_fan_cycles' ).checked;
        var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.  In this case it breaks the web page function.
        var url_string = 'draw_daily.php?' + show_thermostat_id + '&' + show_date_string + '&' + show_heat_cycle_string  + '&' + show_cool_cycle_string  + '&' + show_fan_cycle_string + '&' + no_cache_string;
        document.getElementById( 'daily_temperature_chart' ).src = url_string;
      }

      function display_hvac_runtime_chart()
      {
        var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
        var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.  That cleverness breaks this web page's function.
        var url_string = 'draw_runtimes.php?' + show_thermostat_id + '&' + no_cache_string;
        document.getElementById( 'hvac_runtime_chart' ).src = url_string;
      }

      function update_multi_chart()
      {
        var from_date_string = 'from_date=' + document.getElementById( 'from_date' ).value;
        var to_date_string = 'to_date=' + document.getElementById( 'to_date' ).value;
        var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.  In this case it breaks the web page function.
        var url_string = 'draw_range.php?id=<?php echo $id ?>&' + from_date_string + '&' + to_date_string  + '&' + no_cache_string;
        document.getElementById( 'multi_chart_image' ).src = url_string;
      }


      function display_historic_chart()
      {
        var show_thermostat_id = 'id=' + document.getElementById( 'thermostat_id' ).value;
        var show_indoor = 'Indoor=' + document.getElementById( 'history_selection' ).value;
        var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.  That cleverness breaks this web page's function.
        var url_string = 'draw_weekly.php?' + show_thermostat_id + '&' + show_indoor + '&' + no_cache_string;
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
            <br><br>The HTML5 components are tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser.  They do not work (manually type in the date) in Firefox.  I've not tested the functionality in IE.  The HTML validator suggests that the HTML 5 components may also work in Opera/

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
            <button type='button' onClick='javascript: show_date.stepDown(); display_daily_temperature();'>&lt;--</button>
            <!-- Need to change the max value to a date computed by Javascript so it stays current -->
            <input type='date' id='show_date' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' onInput='javascript: display_daily_temperature();' step='1'/>
            <button type='button' name='show_date' onClick='javascript: document.getElementById("show_date").stepUp(); display_daily_temperature();'>--&gt;</button>
            <input type='checkbox' id='show_heat_cycles' name='show_heat_cycles' onChange='javascript: display_daily_temperature();'/>Show Heat Cycles
            <input type='checkbox' id='show_cool_cycles' name='show_cool_cycles' onChange='javascript: display_daily_temperature();'/>Show Cool Cycles
            <input type='checkbox' id='show_fan_cycles'  name='show_fan_cycles'  onChange='javascript: display_daily_temperature();'/>Show Fan Cycles
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
Just read the cookie here and load the default value and refresh teh chart accordingly
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



      <div class='tab' id='hvac'> <a href='#hvac'> HVAC Runtime </a>
        <div class='container'>
          <div class='tab-toolbar'>
            <input type='button' onClick='javascript: display_hvac_runtime_chart();' value='Refresh'>
          </div>
          <div class='content'>
            <br>
            <div class='thermo_chart'>
              <img id='hvac_runtime_chart' src='images/hvac_runtime_placeholder.png' alt='HVAC Runtimes'>
            </div>
            <script type='text/javascript'>
              display_hvac_runtime_chart(); // Draw the chart
            </script>
          </div>
        </div>
      </div>
      <div class='tab_gap'></div>



      <div class='tab' id='history'> <a href='#history'> History Hi/Low </a>
        <div class='container'>
          <div class='tab-toolbar'>
            <select id='history_selection' onClick='javascript: display_historic_chart();'>
              <option selected='selected' value='0'>Outoor</option>
              <option value='1'>Indoor</option>
              <option value='2'>Both</option>
            </select>
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



      <div class='tab' id='multiple'> <a href='#multiple'> Multiple Days </a>
        <div class='container'>
          <div class='tab-toolbar'>
            <!-- Show initial range as from 3 days ago through today -->
            From date <input type='date' id='from_date' value='<?php echo date( 'Y-m-d', time() - 259000 ); ?>' max='<?php echo $show_date; ?>' onInput='javascript: update_multi_chart();' step='1'/>
            to date <input type='date' id='to_date' value='<?php echo $show_date; ?>' max='<?php echo $show_date; ?>' onInput='javascript: update_multi_chart();' step='1'/>
          </div>
          <div class='content'>
            <br>
            <div class='thermo_chart'>
              <img id='multi_chart_image' src='draw_range.php?id=<?php echo urlencode($id) ?>&from_date=<?php echo date( 'Y-m-d', time() - 259000 ); ?>&amp;to_date=<?php echo $show_date; ?>' alt='Several Days Temperature History'>
            </div>
            Show an hour glass cursor while the chart is updating?  Sometimes it is not obvious that an update is happening and it can be slow.
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
<?php
    if( isset($_POST['password']) && ($_POST['password'] != $password ) )
    {
      echo "<font color='red'> Incorrect Username or Password - I think you typed &quot; {$_POST['password']} ?>&quot; </font>";
    }
?>
              </form>
<?php
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
      if( ! $isLoggedIn )
      { // Default presentation before password is entered
        echo 'You must enter password to access config information.';
      }
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
              <li>The external temperatures come from the <a target='_blank' href='http://www.wunderground.com/weather/api/'>Weather Underground API</a></li>
            </ul>
            <p>This project is based on the <a target='_blank' href='http://www.radiothermostat.com/filtrete/products/3M-50/'>Filtrete 3M Radio Thermostat</a>.
            <br><br><br>
            <div style='text-align: center;'>
              <a target='_blank' href='http://validator.w3.org/check?uri=referer'><img style='border:0;width:88px;height:31px;' src='images/valid-html5.png' alt='Valid HTML 5'/></a>
              <a target='_blank' href='http://jigsaw.w3.org/css-validator/check/referer'><img style='border:0;width:88px;height:31px;' src='http://jigsaw.w3.org/css-validator/images/vcss' alt='Valid CSS!'/></a>
            </div>
          </div>
        </div>
      </div>

    </div>

  </body>
</html>