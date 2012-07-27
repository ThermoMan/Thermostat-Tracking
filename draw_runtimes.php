<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

// pChart library inclusions
include("lib/pChart2.1.3/class/pData.class.php");
include("lib/pChart2.1.3/class/pDraw.class.php");
include("lib/pChart2.1.3/class/pImage.class.php");

function bobby_tables()
{
  $filename = "./images/exploits_of_a_mom.png";
  $handle = fopen( $filename, "r" );
  $contents = fread( $handle, filesize($filename) );
  fclose( $handle );
  echo $contents;
}

//session_start();

$link = mysql_connect( $host, $user, $pass );
if( !$link )
{
  die( "Could not connect: <no error message provided to hackers>"  );
}
mysql_select_db( $db, $link ) or die( "cannot select DB" );            // Really should log this?

/* This chart no longer takes date as a parameter - but may take a date range in the future
// Set default date to today
$show_date = date( "Y-m-d" );
if( isset( $_GET["show_date"] ) )
{ // Use provided date
  $show_date = $_GET["show_date"];
}
$date_pattern = "/[2]{1}[0]{1}[0-9]{2}-[0-9]{2}-[0-9]{2}/";
if( !preg_match( $date_pattern, $show_date ) )
{
  bobby_tables();
  return;
}
*/

// Find the limits of the run time data and match that to the outdoor temp data
$sql = "SELECT min(date) AS min_date, max(date) AS max_date FROM " . $table_prefix . "run_times;";
$result = mysql_query( $sql );
$row = mysql_fetch_array( $result );
$min_date = $row[ "min_date" ];
$max_date = $row[ "max_date" ];

//$sql = "SELECT date, heat_runtime, cool_runtime, MAX(outdoor_temp) AS outdoor_max FROM " . $table_prefix . "run_times ORDER by date ASC;";
$sql = "SELECT date, heat_runtime, cool_runtime, "
       . "(SELECT MAX(outdoor_temp) "
       . " FROM " . $table_prefix . "temperatures "
       . " WHERE " . $table_prefix . "run_times.date = DATE(" . $table_prefix . "temperatures.date) ) AS outdoor_max "
       . "FROM " . $table_prefix . "run_times "
       . "ORDER by date ASC;";
$result = mysql_query( $sql );

// Create and populate the pData object
$MyData = new pData();

// Set default boundaries for chart
$chart_temp_min = $normal_low;
$chart_temp_max = $normal_high;
$chart_runtime_min = 0;
$chart_runtime_max = 120;  // Set two hours as the default runtime scale max

$first = true;
while( $row = mysql_fetch_array( $result ) )
{
  // There are too many dates to show them ALL on the x-axis - it turns into one black line.
  if( $first )
  { // Do show the WHOLE date for the first item.
    $MyData->addPoints( $row["date"], "Labels" );
    $first = false;
  }
  else
  { // Thereafter show only MM-DD when you show anything at all
    $MyData->addPoints( substr( $row['date'], 5, 5 ), "Labels" );
  }

  if( $row["heat_runtime"] != "VOID" )
  { // Assume that if one data point is bad, they all are.
    $MyData->addPoints( $row["heat_runtime"], "Heat" );
    if( $row["heat_runtime"] > $chart_runtime_max ) $chart_runtime_max = $row["heat_runtime"];

    $MyData->addPoints( $row["cool_runtime"], "Cool" );
    if( $row["cool_runtime"] > $chart_runtime_max ) $chart_runtime_max = $row["cool_runtime"];

    $MyData->addPoints( $row["outdoor_max"], "Temperature" );
    if( $row["outdoor_max"] < $chart_temp_min ) $chart_temp_min = $row["outdoor_max"];
    if( $row["outdoor_max"] > $chart_temp_max ) $chart_temp_max = $row["outdoor_max"];
  }
  else
  {
    $MyData->addPoints( VOID, "Heat" );
    $MyData->addPoints( VOID, "Cool" );
    $MyData->addPoints( VOID, "Temperature" );
  }
}

mysql_close( $link );

// Attach the data series to the axis (by ordinal.  0 is X-axis)
$MyData->setSerieOnAxis( "Heat", 0 );
$MyData->setSerieOnAxis( "Cool", 0 );
$MyData->setSerieOnAxis( "Temperature", 1 );
$MyData->setAxisPosition( 1, AXIS_POSITION_RIGHT );

// Set line style, color, and alpha blending level
$MyData->setSerieTicks( "Heat", 0 );  // 0 is a solid line
$serieSettings = array( "R" => 150, "G" => 50, "B" => 80, "Alpha" => 90 );
$MyData->setPalette( "Heat", $serieSettings );

$serieSettings = array( "R" => 50, "G" => 150, "B" => 180, "Alpha" => 90 );
$MyData->setPalette( "Cool", $serieSettings );

$serieSettings = array( "R" => 175, "G" => 75, "B" => 105, "Alpha" => 100 );
$MyData->setPalette( "Temperature", $serieSettings );

// Set names for Y-axis labels
$MyData->setAxisName( 0, "Minutes" );
$MyData->setAxisName( 1, "Degrees" );

// Set names for X-axis labels
$MyData->setSerieDescription( "The days" );
$MyData->setAbscissa( "Labels" );


// Create the pChart object
$myPicture = new pImage( 900, 430, $MyData );

// Turn off Antialiasing
$myPicture->Antialias = TRUE;

// Draw the background
$Settings = array( "R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107, "Alpha" => 60 );
$myPicture->drawFilledRectangle( 0, 0, 900, 430, $Settings );

// Overlay with a gradient
$Settings = array( "StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50 );
$myPicture->drawGradientArea( 0, 0, 900, 430, DIRECTION_VERTICAL, $Settings );
$Settings = array( "StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80 );
$myPicture->drawGradientArea( 0, 0, 900,  20, DIRECTION_VERTICAL, $Settings );

// Add a border to the picture
$myPicture->drawRectangle( 0, 0, 899, 429, array( "R" => 0, "G" => 0, "B" => 0 ) );

// Write the picture title
$myPicture->setFontProperties( array( "FontName" => "lib/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 8 ) );
$myPicture->drawText( 10, 13, "HVAC run times", array( "R" => 255, "G" => 255, "B" => 255) );

// Define shadows under series lines
$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>20));

// Define the chart area
$myPicture->setGraphArea( 60, 60, 850, 390 );

// Explicity set a scale for the drawing.
$AxisBoundaries = array( 0 => array ( "Min" => $chart_runtime_min, "Max" => $chart_runtime_max ), 1 => array ( "Min" => $chart_temp_min, "Max" => $chart_temp_max ) );
// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
//$Settings = array("Pos"=>SCALE_POS_LEFTRIGHT, "Mode"=>SCALE_MODE_FLOATING, "LabelingMethod"=>LABELING_ALL, "GridR"=>255, "GridG"=>255, "GridB"=>255, "GridAlpha"=>50, "TickR"=>0, "TickG"=>0, "TickB"=>0, "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, "DrawYLines"=>ALL);
$Settings = array("Pos"=>SCALE_POS_LEFTRIGHT, "Mode"=>SCALE_MODE_MANUAL, "ManualScale"=>$AxisBoundaries, "LabelingMethod"=>LABELING_ALL, "GridR"=>255, "GridG"=>255, "GridB"=>255, "GridAlpha"=>50, "TickR"=>0, "TickG"=>0, "TickB"=>0, "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, "DrawYLines"=>ALL);
$myPicture->drawScale($Settings);

// Define shadows under series lines
$myPicture->setShadow( FALSE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 40 ) );

// Draw the outdoor max temperature as a filled line chart
$MyData->setSerieDrawable( "Heat", FALSE );
$MyData->setSerieDrawable( "Cool", FALSE );
$MyData->setSerieDrawable( "Temperature", TRUE );
$myPicture->drawAreaChart();

// Draw the run times as bar charts
$MyData->setSerieDrawable( "Heat", TRUE );
$MyData->setSerieDrawable( "Cool", TRUE );
$MyData->setSerieDrawable( "Temperature", FALSE );
$myPicture->drawBarChart( array("Gradient"=>1, "AroundZero"=>1) );

// Write the chart legend
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
$myPicture->drawLegend( 710, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL ) );


$myPicture->autoOutput();
?>