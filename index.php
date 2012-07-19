<?php
REQUIRE "config.php";
REQUIRE "lib/t_lib.php";

date_default_timezone_set( $timezone );
$show_date = time();  // Start with today's date
?>

<html>
<head>
  <title>Thermostat</title>
  <link rel="shortcut icon" href="./favicon.ico">
  <link rel="stylesheet"    href="t_css.css" type="text/css">

  <script type="text/javascript">
    function update_daily_chart()
    {
      show_date_string = "show_date=" + document.getElementById('show_date').value;
      //show_cycle_string = "show_cycles=false";
      show_cycle_string = "show_cycles=" + document.getElementById('show_cycles').checked;
      no_cache_string = "nocache=<?php echo time() ?>";
      url_string = "draw_daily.php" + "?" + show_date_string + "&" + show_cycle_string + "&" + no_cache_string;
//      alert( "Before src is " + document.getElementById( "daily_chart_image" ).src );
      document.getElementById( "daily_chart_image" ).src = url_string;
//      alert( "After src is " + document.getElementById( "daily_chart_image" ).src );
    }
  </script>
</head>
<body>

HTML5: Pick a date or click the increment/decrement buttons and the chart will auto update.  Works in Chrome, for Firefox 13 you have to type dates<br>
<button type="button" onclick="javascript: show_date.stepDown(); update_daily_chart();">&lt;--</button>
<input id="show_date" name="show_date"
       type="date"
       value="<?php echo date( "Y-m-d", $show_date); ?>"
       max="<?php echo date( "Y-m-d", $show_date); ?>"
       onInput="javscript: update_daily_chart();"
       step="1"/>
<button type="button" onclick="javascript: document.getElementById('show_date').stepUp();update_daily();">--&gt;</button>
<input type="checkbox" id="show_cycles" name="show_cycles" onChange="javascript: update_daily_chart();"/>Show Cycles

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <div style="height: 430px; width: 900px;">
      <!-- The img tag does not draw the chart.  It is a place keeper in code and on screen for the chart position -->
      <img src="" id="daily_chart_image" alt="The temperatures">
<script type="text/javascript">
  // Set initial values for chart
  document.getElementById('show_date').value = "<?php echo date( "Y-m-d", $show_date); ?>";
  document.getElementById('show_cycles').checked = false;
  // Draw the chart
  update_daily_chart();
</script>
    </div>
  </div>
</div>
Missing values are where Windows Task Scheduler is demonstrating itself to be an inferior way of executing a task.

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <div id="daily_chart" style="height: 430px; width: 900px;">
      <img src="draw_runtimes.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="HVAC Runtimes">
    </div>
  </div>
</div>


<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <div id="daily_chart" style="height: 430px; width: 900px;">
      <img src="draw_range.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="Several Days Temperature History">
    </div>
  </div>
</div>
Try showing a range of dates

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <div id="daily_chart" style="height: 430px; width: 900px;">
      <img src="draw_weekly.php?Indoor=1" alt="All Time Indoor History">
    </div>
  </div>
</div>
Hi/Low temps

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <div id="daily_chart" style="height: 430px; width: 900px;">
      <img src="draw_weekly.php?Indoor=0" alt="All Time Outdoor History">
    </div>
  </div>
</div>

<?php

echo "<br>Alternative sources of outdoor temp - need a voting system to pick the consensus temp with a preference for one particular sensor";

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
echo "<br>Weather Underground outdoor temp is " . $base_url . "ZIP <" . $outdoorTemp . ">";

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
echo "<br>Weather Underground OTHER outdoor temp is " . $base_url . "ZIP <" . $outdoorTemp2 . ">";


?>
<br><br><br>
<br>( These next fields don't do anything, they are just HTML5 for play.
<br><input type="number" min="0" max="10" step="2" value="6">
<br><input type="range" min="0" max="10" step="2" value="6"> )

</body>
</html>