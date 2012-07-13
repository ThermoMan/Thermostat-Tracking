<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

// pChart library inclusions
include("lib/pChart2.1.3/class/pData.class.php");
include("lib/pChart2.1.3/class/pDraw.class.php");
include("lib/pChart2.1.3/class/pImage.class.php");
include("lib/pChart2.1.3/class/pStock.class.php");

//session_start();

$link = mysql_connect( $host, $user, $pass );
if( !$link )
{
  die( "Could not connect: <no error message provided to hackers>"  );
}
mysql_select_db( $db, $link ) or die( "cannot select DB" );            // Really should log this?

$sql = "SELECT DATE(date) AS date, MIN(indoor_temp) AS indoor_min, MAX(indoor_temp) AS indoor_max, MIN(outdoor_temp) AS outdoor_min, MAX(outdoor_temp) AS outdoor_max FROM " . $table_prefix . "temperatures GROUP BY DATE(date)";
$result = mysql_query( $sql );

// Create and populate the pData object
$MyData = new pData();

$old_month = -1;
while( $row = mysql_fetch_array( $result ) )
{
  /*
   *   There are too many dates to show them ALL on the x-axis - it turns into one black line.
   *
   *   The original logic was to search for the first of the month like this:
   *    else if( substr( $row['date'], 8, 2 ) == "01" )
   *
   *   However it turns out that sometimes there is NO data at all for a given date.
   * So the new logic is to look for when the month changes and show that date.
   */
  if( $old_month == -1 )
  { // Do show the WHOLE date for the first item.
    $MyData->addPoints( $row['date'], "Labels" );
  }
  else if( substr( $row['date'], 5, 2 ) != $old_month )
  { // Thereafter show only MM-DD when you show anything at all
    // Show month name ala "Dec"
    $MyData->addPoints( date("M", mktime( 0, 0, 0, substr( $row['date'], 5, 2 ), 1) ), "Labels" );
  }
  else
  { // Add a blank instead of text for some x-axis labels.
    $MyData->addPoints( VOID, "Labels" );
  }
  $old_month = substr( $row['date'], 5, 2 );


  if( $_GET["Indoor"] == 1 )
  {
    $MyData->addPoints( $row['indoor_min'], "Indoor Min" );
    $MyData->addPoints( $row['indoor_max'], "Indoor Max" );
  }
  else
  {
    $MyData->addPoints( $row['outdoor_min'], "Outdoor Min" );
    $MyData->addPoints( $row['outdoor_max'], "Outdoor Max" );
  }
}
mysql_close( $link );

// Attach the data series to the axis (by ordinal)
if( $_GET["Indoor"] == 1 )
{
  $MyData->setSerieOnAxis( "Indoor Min", 0 );
  $MyData->setSerieOnAxis( "Indoor Max", 0 );

  // Set line style, color, and alpha blending level
  $serieSettingsMin = array( "R" => 50, "G" => 50, "B" => 180, "Alpha" => 100 );
  $serieSettingsMax = array( "R" => 180, "G" => 50, "B" => 50, "Alpha" => 100 );

  $MyData->setPalette( "Indoor Min", $serieSettingsMin );
  $MyData->setPalette( "Indoor Max", $serieSettingsMax );
}
else
{
  $MyData->setSerieOnAxis( "Outdoor Min", 0 );
  $MyData->setSerieOnAxis( "Outdoor Max", 0 );

  // Set line style, color, and alpha blending level
  $serieSettingsMin = array( "R" => 100, "G" => 100, "B" => 230, "Alpha" => 50 );
  $serieSettingsMax = array( "R" => 230, "G" => 100, "B" => 100, "Alpha" => 50 );

  $MyData->setPalette( "Outdoor Min", $serieSettingsMin );
  $MyData->setPalette( "Outdoor Max", $serieSettingsMax );
}

// Set names for Y-axis labels
$MyData->setAxisName( 0, "Temperatures" );

// Set names for X-axis labels
$MyData->setSerieDescription( "Labels", "Days" );
$MyData->setAbscissa( "Labels" );


$myPicture = new pImage( 900, 330, $MyData ); // Create the pChart object
$myPicture->Antialias = TRUE;                 // Turn off Antialiasing

// Draw the background
$Settings = array( "R" => 170, "G" => 183, "B" => 87, "Dash" => 1, "DashR" => 190, "DashG" => 203, "DashB" => 107, "Alpha" => 60 );
$myPicture->drawFilledRectangle( 0, 0, 900, 430, $Settings );

// Overlay with a gradient
$Settings = array( "StartR" => 219, "StartG" => 231, "StartB" => 139, "EndR" => 1, "EndG" => 138, "EndB" => 68, "Alpha" => 50 );
$myPicture->drawGradientArea( 0, 0, 900, 330, DIRECTION_VERTICAL, $Settings );
$Settings = array( "StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 80 );
$myPicture->drawGradientArea( 0, 0, 900,  20, DIRECTION_VERTICAL, $Settings );

// Add a border to the picture
$myPicture->drawRectangle( 0, 0, 899, 329, array( "R" => 0, "G" => 0, "B" => 0 ) );

// Write the picture title
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 8 ) );
$myPicture->drawText( 10, 13, "Show the historic temperatures", array( "R" => 255, "G" => 255, "B" => 255) );

// Write the chart title
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 8 ) );
$myPicture->drawText( 250, 55, "Min/Max for each day in the record", array( "FontSize" => 12, "Align" => TEXT_ALIGN_BOTTOMMIDDLE ) );

$myPicture->setGraphArea( 60, 60, 850, 290 );   // Define the chart area

// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$scaleSettings = array( "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => TRUE, "CycleBackground" => TRUE );
$myPicture->drawScale( $scaleSettings );

// Write the chart legend
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
$myPicture->drawLegend( 510, 312, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL ) );

$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$myPicture->drawScale( array( "DrawSubTicks" => TRUE ) );
$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10 ) );
$myPicture->drawBarChart();
$myPicture->setShadow( FALSE );

$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" =>0 , "B" =>0 , "Alpha" => 10 ) );
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Forgotte.ttf", "FontSize" => 11 ) );


// Render the picture
$myPicture->autoOutput();
?>
