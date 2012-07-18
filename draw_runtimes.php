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

$show_date = $_GET["show_date"];
$date_pattern = "/[2]{1}[0]{1}[0-9]{2}-[0-9]{2}-[0-9]{2}/";
if( !preg_match( $date_pattern, $show_date ) )
{
  bobby_tables();
  return;
}

$sql = "SELECT date, heat_runtime, cool_runtime FROM " . $table_prefix . "run_times ORDER by date ASC;";

$result = mysql_query( $sql );

// Create and populate the pData object
$MyData = new pData();

while( $row = mysql_fetch_array( $result ) )
{
    $MyData->addPoints( $row['date'], "Labels" );

  if( $row['heat_runtime'] != 'VOID' )
  { // Assume that if one data point is bad, they both are.
    $MyData->addPoints( $row['heat_runtime'], "Heat" );
    $MyData->addPoints( $row['cool_runtime'], "Cool" );
  }
  else
  {
    $MyData->addPoints( VOID, "Heat" );
    $MyData->addPoints( VOID, "Cool" );
  }
}
mysql_close( $link );

// Attach the data series to the axis (by ordinal.  0 is X-axis)
$MyData->setSerieOnAxis( "Heat", 0 );
$MyData->setSerieOnAxis( "Cool", 0 );

// Set line style, color, and alpha blending level
$MyData->setSerieTicks( "Heat", 0 );  // 0 is a solid line
$serieSettings = array( "R" => 150, "G" => 50, "B" => 80, "Alpha" => 90 );
$MyData->setPalette( "Heat", $serieSettings );

//$MyData->setSerieTicks( "Cool", 0); // n is length in pixels of dashes in line
$serieSettings = array( "R" => 50, "G" => 150, "B" => 180, "Alpha" => 90 );
$MyData->setPalette( "Cool", $serieSettings );

// Set names for Y-axis labels
$MyData->setAxisName( 0, "Minutes" );

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


// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$Settings = array("Pos"=>SCALE_POS_LEFTRIGHT, "Mode"=>SCALE_MODE_FLOATING, "LabelingMethod"=>LABELING_ALL, "GridR"=>255, "GridG"=>255, "GridB"=>255, "GridAlpha"=>50, "TickR"=>0, "TickG"=>0, "TickB"=>0, "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, "DrawYLines"=>ALL);
$myPicture->drawScale($Settings);

// Define shadows under series lines
$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 40 ) );
$myPicture->drawBarChart( array("Gradient"=>1, "AroundZero"=>1) );

// Write the chart legend
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
$myPicture->drawLegend( 710, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL ) );


$myPicture->stroke();
?>