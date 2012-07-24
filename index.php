<!DOCTYPE html>
<?php
REQUIRE "config.php";
REQUIRE "lib/t_lib.php";

date_default_timezone_set( $timezone );
$show_date = time();  // Start with today's date
?>

<html>
<head>
  <meta http-equiv=Content-Type content="text/html; charset=utf-8">
  <title>Thermostat</title>
  <link href="/Thermo/favicon.ico" rel="shortcut icon" type="image/x-icon" />
  <link href="lib/tabs/tabs.css" rel="stylesheet" type="text/css" />  <!-- Add tab library -->

  <script type="text/javascript">
    function update_daily_chart()
    {
      var show_date_string = "show_date=" + document.getElementById('show_date').value;
      var show_cycle_string = "show_cycles=" + document.getElementById('show_cycles').checked;
      var no_cache_string = "nocache=<?php echo time() ?>";
      var url_string = "draw_daily.php" + "?" + show_date_string + "&" + show_cycle_string + "&" + no_cache_string;
      document.getElementById( "daily_chart_image" ).src = url_string;
    }

    var refreshInterval = 20 * 60 * 1000;  // It's measured in milliseconds and I want a unit of minutes.
    function timedRefresh()
    {
      // Save value (either true or false) for next time (keep cookie up to ten days)
      setCookie( "auto_refresh", document.getElementById( "auto_refresh" ).checked, 10 );

      var loc_string = "" + location; // Turn location into a string so we can check for page number
      if( document.getElementById( "auto_refresh" ).checked == true && loc_string.indexOf( "page-1" ) > 0 )
      {
        update_daily_chart();
        setTimeout( "timedRefresh();", refreshInterval );
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
  </script>
</head>

<body>

<ol id="toc">
  <li><a href="#page-1"><span>Daily Detail</span></a></li>
  <li><a href="#page-2"><span>HVAC Runtime</span></a></li>
  <li><a href="#page-3"><span>Multiple Days (example)</span></a></li>
  <li><a href="#page-4"><span>Indoor Historic</span></a></li>
  <li><a href="#page-5"><span>Outdoor Historic</span></a></li>
  <li><a href="#page-6"><span>Misc Junk</span></a></li>
  <li><a href="#page-7"><span>About</span></a></li>
</ol>

<div class="content" id="page-1">
  <p>
  <div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
    <div style="font-size: 10px; opacity: 1;">
      <div style="height: 430px; width: 900px;">
        <img src="draw_daily.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" id="daily_chart_image" alt="The temperatures">
      </div>
    </div>
  </div>
  <button type="button" onclick="javascript: show_date.stepDown(); update_daily_chart();">&lt;--</button>
  <input id="show_date" type="date" value="<?php echo date( "Y-m-d", $show_date); ?>" max="<?php echo date( "Y-m-d", $show_date); ?>" onInput="javascript: update_daily_chart();" step="1"/>
  <button type="button" name="show_date" onclick="javascript: document.getElementById('show_date').stepUp();update_daily_chart();">--&gt;</button>
  <input type="checkbox" id="show_cycles"  name="show_cycles"  onChange="javascript: update_daily_chart();"/>Show Cycles
  <input type="checkbox" id="auto_refresh" name="auto_refresh" onChange="javascript: timedRefresh();"/>Auto refresh (every 20 minutes)

  <!-- This initialization script must fall AFTER declaration of date input box -->
  <script type="text/javascript">
    // Set initial values for chart
    document.getElementById('show_date').value = "<?php echo date( "Y-m-d", $show_date); ?>";
    document.getElementById('show_cycles').checked = false;

    // Need to add a check here for present day.  Only present day is actually changing so don't bother with refresh on historic data
    update_daily_chart(); // Draw the chart

    if( getCookie( "auto_refresh" ) == "true" )
    { // Simply setting the check box does not start the timer.
      document.getElementById( "auto_refresh" ).checked = true;
      timedRefresh(); // So start the timer too
    }
  </script>

  <br>HTML5: Pick a date or click the increment/decrement buttons and the chart will auto update.  Works in Chrome, for Firefox 14 you have to type dates<br>
  <br>Missing values are where Windows Task Scheduler is demonstrating itself to be an inferior way of executing a task.
</div>

<div class="content" id="page-2">
  <p>
  <div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
    <div style="font-size: 10px; opacity: 1;">
      <div style="height: 430px; width: 900px;">
        <img src="draw_runtimes.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="HVAC Runtimes">
      </div>
    </div>
  </div>
</div>

<div class="content" id="page-3">
  <p>
  <div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
    <div style="font-size: 10px; opacity: 1;">
      <div style="height: 430px; width: 900px;">
        <img src="draw_range.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="Several Days Temperature History">
      </div>
    </div>
  </div>
  Try showing a range of dates
</div>

<div class="content" id="page-4">
  <p>
  <div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
    <div style="font-size: 10px; opacity: 1;">
      <div style="height: 430px; width: 900px;">
        <img src="draw_weekly.php?Indoor=1" alt="All Time Indoor History">
      </div>
    </div>
  </div>
  Hi/Low temps
</div>

<div class="content" id="page-5">
  <p>
  <div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
    <div style="font-size: 10px; opacity: 1;">
      <div style="height: 430px; width: 900px;">
        <img src="draw_weekly.php?Indoor=0" alt="All Time Outdoor History">
      </div>
    </div>
  </div>
</div>

<div class="content" id="page-6">
  <?php

  echo "<br>Alternative sources of outdoor temp - need a voting system to pick the consensus temp with a preference for one particular sensor.<p>";

  $doc = new DOMDocument();
  $base_url = "http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=";
  $url = $base_url . $ZIP;
  if( $doc->load( $url, LIBXML_NOERROR ) )
  {
    $locs = $doc->getElementsByTagName( "current_observation" );
    foreach( $locs as $loc )
    {
      $outdoorTemp =  $loc->getElementsByTagName( "temp_f" )->item(0)->nodeValue; // . "&deg; F";
    }
  }
  else
  {
    $outdoorTemp = "-999";
  }
  echo "<br>Weather Underground outdoor temp is " . $base_url . "ZIP &lt;" . $outdoorTemp . "&gt;";

  // Create DOM from URL
  $base_url = "http://www.wunderground.com/cgi-bin/findweather/getForecast?query=";
  $url = $base_url . $ZIP;
  $html = file_get_html( $url );
  foreach($html->find('div[id=nowTemp]') as $key => $info)
  {
  //  echo "<br>" .($key + 1).'. '.$info->plaintext;
    $str = $info->plaintext;
    $matches = preg_split( "/[\s]*[ &][\s]*/", $str );
    $outdoorTemp2 = $matches[2];

  }
  echo "<br>Weather Underground OTHER outdoor temp is " . $base_url . "ZIP &lt;" . $outdoorTemp2 . "&gt;";


  ?>
  <br><br><br>
  <br>These next fields don't do anything, they are just HTML5 for play.
  <br><input type="number" min="0" max="10" step="2" value="6">
  <br><input type="range" min="0" max="10" step="2" value="6">
</div>

<div class="content" id="page-7">
  <p>
  <p>Source code for this project can be found on <a target="_blank" href="https://github.com/ThermoMan/3M-50-Thermostat-Tracking">github</a>
  <p>
  <br>The foundation for this project is a Windows Home Server v1 running <a target="_blank" href="http://www.apachefriends.org/en/xampp.html">xampp</a>. The web server is <a target="_blank" href="http://www.apache.org">apache</a> and code is written in <a target="_blank" href="http://www.php.net">php</a> and stores data in a <a target="_blank" href="http://www.mysql.com">MySQL</a> database. I used <a target="_blank" href="http://www.winscp.net">WinSCP</a> to connect to <a target="_blank" href="http://filezilla-project.org">FileZilla</a> and edited the code using <a target="_blank" href="http://www.textpad.com">TextPad</a>.
  <p>
  <p>This project also uses code from the following external projects
  <ul>
    <li><a target="_blank" href="http://sourceforge.net/projects/simplehtmldom/">PHP Simple HTML DOM Parser</a></li>
    <li><a target="_blank" href="http://www.pchart.net/">pChart</a></li>
    <li><a target="_blank" href="http://blixt.org/articles/tabbed-navigation-using-css#section=introduction">Blixt tab library</a></li>
  </ul>
  <br><br><br>
  <div style="text-align: center;">
    <a target="_blank" href="http://validator.w3.org/check?uri=referer"><img style="border:0;width:88px;height:31px" src="images/valid-html5.png" alt="Valid HTML 5"/></a>
    <a target="_blank" href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:88px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS!"/></a>
  </div>
</div>

<!-- This following scripts MUST be dead last for the tab library to work properly -->
<script src="lib/tabs/activatables.js" type="text/javascript"></script>
<script type="text/javascript">
  activatables('page', ['page-1', 'page-2', 'page-3', 'page-4', 'page-5', 'page-6', 'page-7']);
</script>
</body>
</html>