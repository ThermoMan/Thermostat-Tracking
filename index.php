<html>
<head>
  <title>Thermostat</title>
  <link rel="shortcut icon" href="./favicon.ico">
  <link rel="stylesheet"    href="t_css.css" type="text/css">
</head>
<body>
<?php
REQUIRE "config.php";

date_default_timezone_set( "America/Chicago" );
$show_date = time();  // Start with today's date
?>
Type a date and then click Show Chart.  If you're using Chrome, then the HTML5 pieces work, but not yet in Firefox 13<br>
<button type="button" onclick="javascript: show_date.stepDown();">&lt;--</button>
<input id="show_date"
       type="date"
       value="<?php echo date( "Y-m-d", $show_date); ?>"
       max="<?php echo date( "Y-m-d", $show_date); ?>"
       onInput="javascript: document.getElementById('daily_chart').innerHTML = '<img src=\'draw_daily.php?show_date='+document.getElementById('show_date').value+'\' alt=\'Chart\'>'; "
       step="1"/>
<button type="button" onclick="javascript: document.getElementById('show_date').stepUp();">--&gt;</button>
<button type="button" onclick="javascript: document.getElementById('daily_chart').innerHTML = '<img src=\'draw_daily.php?show_date='+document.getElementById('show_date').value+'\' alt=\'Chart\'>'; ">Show Chart</button>

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <center>
      <div id="daily_chart" style="height: 430px; width: 900px;">
        <img src="draw_daily.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="Today's Temperatures">
      </div>
    </center>
  </div>
</div>
Missing values are where Windows Task Scheduler is demonstrating itself to be an inferior way of executing a task.

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <center>
      <div id="daily_chart" style="height: 430px; width: 900px;">
        <img src="draw_runtimes.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="HVAC Runtimes">
      </div>
    </center>
  </div>
</div>


<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <center>
      <div id="daily_chart" style="height: 430px; width: 900px;">
        <img src="draw_range.php?show_date=<?php echo date( "Y-m-d", $show_date); ?>" alt="Several Days Temperature History">
      </div>
    </center>
  </div>
</div>
Try showing a range of dates

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <center>
      <div id="daily_chart" style="height: 329px; width: 900px;">
        <img src="draw_weekly.php?Indoor=1" alt="All Time Indoor History">
			<div>
    </center>
  </div>
</div>
Hi/Low temps

<p>
<div style="display: table-cell; padding: 10px; border: 2px solid rgb(255, 255, 255); vertical-align: middle; overflow: auto; background-image: url('lib/pChart2.1.3/examples/resources/dash.png');">
  <div style="font-size: 10px; opacity: 1;" id="render">
    <center>
      <div id="daily_chart" style="height: 329px; width: 900px;">
        <img src="draw_weekly.php?Indoor=0" alt="All Time Outdoor History">
			<div>
    </center>
  </div>
</div>

<?php

echo "<br>Alternative sources of outdoor temp - need a voting system to pick the consensus temp with a preference for one particular sensor";

$doc = new DOMDocument();
$url = "http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=" . $ZIP;
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
echo "<br>Weather Underground outdoor temp is " . $url . "<" . $outdoorTemp . ">";

// Create DOM from URL
$url = "http://www.wunderground.com/cgi-bin/findweather/getForecast?query=" . $ZIP";
$html = file_get_html( $url );
foreach($html->find('div[id=nowTemp]') as $key => $info)
{
//  echo "<br>" .($key + 1).'. '.$info->plaintext;
  $str = $info->plaintext;
  $matches = preg_split( "/[\s]*[ &][\s]*/", $str );
  $outdoorTemp2 = $matches[2];

}
echo "<br>Weather Underground OTHER outdoor temp is " . $url . " <" . $outdoorTemp2 . ">";


?>
<br><br><br>
<br>( These next fields don't do anything, they are just HTML5 for play.
<br><input type="number" min="0" max="10" step="2" value="6">
<br><input type="range" min="0" max="10" step="2" value="6"> )

</body>
</html>