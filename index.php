<!DOCTYPE html>
<?php
require "config.php";
require "lib/t_lib.php";

date_default_timezone_set( $timezone );
$show_date = date( "Y-m-d", time() );  // Start with today's date
if( strftime( "%H%M" ) <= "0059" )
{ // If there is not enough (3 data points is "enough") data to make a meaningful chart, default to yesterday
  $show_date =  date( "Y-m-d", strtotime( "-1 day", time() ) );  // Start with yesterday's date
}
?>

<html>
<head>
  <meta http-equiv=Content-Type content="text/html; charset=utf-8">
  <title>3M-50 Thermostat Tracking</title>
  <link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
  <link href="resources/thermo.css" rel="stylesheet" type="text/css" />
  <link href="lib/tabs/tabs.css" rel="stylesheet" type="text/css" />  <!-- Add tab library -->

  <script type="text/javascript">
    function update_daily_chart()
    {
      var show_date_string = "show_date=" + document.getElementById( "show_date" ).value;
      var show_heat_cycle_string = "show_heat_cycles=" + document.getElementById( "show_heat_cycles" ).checked;
      var show_cool_cycle_string = "show_cool_cycles=" + document.getElementById( "show_cool_cycles" ).checked;
      var show_fan_cycle_string  = "show_fan_cycles="  + document.getElementById( "show_fan_cycles" ).checked;
      var no_cache_string = "nocache=" + Math.random(); // Browsers are very clever with image caching.  In this case it breaks the web page function.
      var url_string = "draw_daily.php" + "?" + show_date_string + "&" + show_heat_cycle_string  + "&" + show_cool_cycle_string  + "&" + show_fan_cycle_string + "&" + no_cache_string;
      document.getElementById( "daily_chart_image" ).src = url_string;
      showRefreshTime();
    }


    function update_multi_chart()
    {
      var from_date_string = "from_date=" + document.getElementById( "from_date" ).value;
      var to_date_string = "to_date=" + document.getElementById( "to_date" ).value;
      var no_cache_string = "nocache=" + Math.random(); // Browsers are very clever with image caching.  In this case it breaks the web page function.
      var url_string = "draw_range.php" + "?" + from_date_string + "&" + to_date_string  + "&" + no_cache_string;
      document.getElementById( "multi_chart_image" ).src = url_string;
    }

    var refreshInterval = 20 * 60 * 1000;  // It's measured in milliseconds and I want a unit of minutes.
    function timedRefresh()
    {
      // Save value (either true or false) for next time (keep cookie up to ten days)
      setCookie( "auto_refresh", document.getElementById( "auto_refresh" ).checked, 10 );

      if( document.getElementById( "auto_refresh" ).checked == true )
      {
        document.getElementById( "daily_update" ).style.visibility = "visible";
        /*
         * Need to add a check here for present day.  Only present day actually has changing data
         * so don't bother with refresh on historic data.  But do leave the refresh flag set for later.
         */
        update_daily_chart();
        setTimeout( "timedRefresh();", refreshInterval );
      }
      else
      {
        document.getElementById( "daily_update" ).style.visibility = "hidden";
      }
    }

    function setCookie( c_name, value, exdays )
    {
      var exdate = new Date();
      exdate.setDate( exdate.getDate() + exdays );
      var c_value = escape(value) + ( ( exdays == null ) ? "" : "; expires = " + exdate.toUTCString() );
      document.cookie = c_name + "=" + c_value;
    }

    function getCookie( c_name )
    {
      var i, x, value, ARRcookies = document.cookie.split( ";" );
      for( i = 0; i < ARRcookies.length; i++ )
      {
        x = ARRcookies[i].substr( 0, ARRcookies[i].indexOf( "=" ) );
        value = ARRcookies[i].substr( ARRcookies[i].indexOf( "=" ) + 1);
        x = x.replace( /^\s+|\s+$/g, "" );
        if( x == c_name)
        {
          return unescape( value );
        }
      }
      return NULL;
    }

    /*
     * Either set up a countdown timer that both updates this clock AND triggers the chart update when hitting 0
     * or set up a second timer that is a countdown clock and hope it stays in sync with the update routine.
     */
    function showRefreshTime()
    {
      var today = new Date();
      var h = "" + today.getHours();
      var m = "" + today.getMinutes();
      //var s = today.getSeconds();
      if( h < 10 ) h = "0" + h;
      if( m < 10 ) m = "0" + m;

      document.getElementById( "daily_update" ).innerHTML = "Countdown to refresh: " + h + ":" + m;
    }

  </script>
</head>

<body>
<ol id="toc">
  <li><a href="#dashboard"><span>Dashboard</span></a></li>
  <li><a href="#daily"><span>Daily Detail</span></a></li>
  <li><a href="#hvac"><span>HVAC Runtime</span></a></li>
  <li><a href="#indoor"><span>Indoor Historic</span></a></li>
  <li><a href="#outdoor"><span>Outdoor Historic</span></a></li>
  <li><a href="#multiple"><span>Multiple Days (example)</span></a></li>
  <li><a href="#about"><span><img src="images/Info.png" alt="icon: about"/> About</span></a></li>
</ol>

<div class="content" id="daily">
  <div class="toolbar">
  <button type="button" onclick="javascript: show_date.stepDown(); update_daily_chart();">&lt;--</button>
  <!-- Need to change the max value to a date computed by Javascript so it stays current -->
  <input type="date" id="show_date" value="<?php echo $show_date; ?>" max="<?php echo $show_date; ?>" onInput="javascript: update_daily_chart();" step="1"/>
  <button type="button" name="show_date" onclick="javascript: document.getElementById('show_date').stepUp();update_daily_chart();">--&gt;</button>
  <input type="checkbox" id="show_heat_cycles" name="show_heat_cycles" onChange="javascript: update_daily_chart();"/>Show Heat Cycles
  <input type="checkbox" id="show_cool_cycles" name="show_cool_cycles" onChange="javascript: update_daily_chart();"/>Show Cool Cycles
  <input type="checkbox" id="show_fan_cycles"  name="show_fan_cycles"  onChange="javascript: update_daily_chart();"/>Show Fan Cycles
  <input type="checkbox" id="auto_refresh"     name="auto_refresh"     onChange="javascript: timedRefresh();"/>Auto refresh (every 20 minutes)
    <span id="daily_update" style="float: right; vertical-align: middle; visibility: hidden;">Countdown to refresh: 00:00</span>
  </div>
  <br>
  <div class="thermo_chart">
    <img id="daily_chart_image" src="draw_daily.php?show_date=<?php echo $show_date; ?>" alt="The temperatures">
  </div>

  <!-- This initialization script must fall AFTER declaration of date input box -->
  <script type="text/javascript">
    // Set initial values for chart
    document.getElementById("show_date").value = "<?php echo $show_date; ?>";
    document.getElementById("show_heat_cycles").checked = false;
    document.getElementById("show_cool_cycles").checked = false;
    document.getElementById("show_fan_cycles").checked = false;

    update_daily_chart(); // Draw the chart

    if( getCookie( "auto_refresh" ) == "true" )
    { // Simply setting the check box does not start the timer.
      document.getElementById( "auto_refresh" ).checked = true;
      timedRefresh(); // So start the timer too
    }
  </script>
  HTML5 components tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser.
  <br>Really does need a "Refresh Now" button.
</div>

<div class="content" id="hvac">
  <div class="toolbar">
    &nbsp;
  </div>
  <br>
  <div class="thermo_chart">
    <img src="draw_runtimes.php" alt="HVAC Runtimes">
  </div>
</div>

<div class="content" id="multiple">
  <!-- Show initial range as from 3 days ago through today -->
  <div class="toolbar">
    From date <input type="date" id="from_date" value="<?php echo date( "Y-m-d", time() - 259000 ); ?>" max="<?php echo $show_date; ?>" onInput="javascript: update_multi_chart();" step="1"/>
    to date <input type="date" id="to_date" value="<?php echo $show_date; ?>" max="<?php echo $show_date; ?>" onInput="javascript: update_multi_chart();" step="1"/>
  </div>
  <br>
  <div class="thermo_chart">
    <img id="multi_chart_image" src="draw_range.php?from_date=<?php echo date( "Y-m-d", time() - 259000 ); ?>&amp;to_date=<?php echo $show_date; ?>" alt="Several Days Temperature History">
  </div>
  Show an hour glass cursor while the chart is updating?  Sometimes it is not obvious that an update is happening and it can be slow.
</div>

<div class="content" id="indoor">
  <div class="toolbar">
    &nbsp;
  </div>
  <br>
  <div class="thermo_chart">
    <img src="draw_weekly.php?Indoor=1" alt="All Time Indoor History">
  </div>
</div>

<div class="content" id="outdoor">
  <div class="toolbar">
    &nbsp;
  </div>
  <br>
  <div class="thermo_chart">
    <img src="draw_weekly.php?Indoor=0" alt="All Time Outdoor History">
  </div>
</div>

<div class="content" id="about">
  <div class="toolbar">
    &nbsp;
  </div>
  <br>
  <p>
  <p>Source code for this project can be found on <a target="_blank" href="https://github.com/ThermoMan/3M-50-Thermostat-Tracking">github</a>
  <p>
  <br>The project originated on Windows Home Server v1 running <a target="_blank" href="http://www.apachefriends.org/en/xampp.html">xampp</a>. Migrated to a "real host" to solve issues with Windows Scheduler.
  <br>I used <a target="_blank" href="http://www.winscp.net">WinSCP</a> to connect and edited the code using <a target="_blank" href="http://www.textpad.com">TextPad</a>.
  <p>
  <p>This project also uses code from the following external projects
  <ul>
    <li><a target="_blank" href="http://www.pchart.net/">pChart</a>.</li>
    <li><a target="_blank" href="http://blixt.org/articles/tabbed-navigation-using-css#section=introduction">Blixt tab library</a>.</li>
    <li><a target="_blank" href="http://www.veryicon.com/icons/folder/leopard-folder-replacements/">Free icons from Very Icon</a>.  These icons were made by <a target="_blank" href="http://jasonh1234.deviantart.com">jasonh1234</a>.</li>
    <li>The external temperatures come from the <a target="_blank" href="http://www.wunderground.com/weather/api/">Weather Underground API</a></li>
  </ul>
  <p>This project is based on the <a target="_blank" href="http://www.radiothermostat.com/filtrete/products/3M-50/">Filtrete 3M Radio Thermostat</a>.
  <br><br><br>
  <div style="text-align: center;">
    <a target="_blank" href="http://validator.w3.org/check?uri=referer"><img style="border:0;width:88px;height:31px;" src="images/valid-html5.png" alt="Valid HTML 5"/></a>
    <a target="_blank" href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:88px;height:31px;" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!"/></a>
  </div>
</div>

<div class="content" id="dashboard">
  <div class="toolbar">
    &nbsp;
  </div>
  For now this is a place keeper.  Please look at the other tabs for the data.
  <br><br><br><br>
  <!-- <br>The heater is <span id="heat_state">unknown</span> -->
  <!-- <br>The compressor is <span id="cool_state">unknown</span> -->
  <!-- <br>The fan is <span id="fan_state">unknown</span> -->
</div>

<!-- Kick off dashboard refresh timers -->
<script type="text/javascript">
// setTimeout( "updateStatus();", updateStatusInterval );
</script>

<!-- This following scripts MUST be dead last for the tab library to work properly -->
<script src="lib/tabs/activatables.js" type="text/javascript"></script>
<script type="text/javascript">
  activatables( "tab", ["dashboard", "daily", "hvac", "multiple", "indoor", "outdoor", "about"] );
</script>
</body>
</html>