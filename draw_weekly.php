<?php
REQUIRE "common.php";

// If they don't ask for indoor, they get outdoor.
$indoor = 0;
if( isset( $_GET["Indoor"] ) )
{
  if( $_GET["Indoor"] == 1 )
  {
    $indoor = 1;
  }
}

connect_to_db();

// The same SQL works for both
$sql = "SELECT DATE(date) AS date, MIN(indoor_temp) AS indoor_min, MAX(indoor_temp) AS indoor_max, MIN(outdoor_temp) AS outdoor_min, MAX(outdoor_temp) AS outdoor_max FROM " . $table_prefix . "temperatures GROUP BY DATE(date)";
$result = mysql_query( $sql );

// Create and populate the pData object
$MyData = new pData();

// Set default boundaries for chart
$chart_y_min = $normal_low;
$chart_y_max = $normal_high;

// Define series names
if( $indoor == 1 )
{
  $series1 = "Indoor Min";
  $series2 = "Indoor Max";

  $column1 = "indoor_min";
  $column2 = "indoor_max";
}
else
{
  $series1 = "Outdoor Min";
  $series2 = "Outdoor Max";

  $column1 = "outdoor_min";
  $column2 = "outdoor_max";
}

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
    $MyData->addPoints( $row["date"], "Labels" );
  }
  else if( substr( $row["date"], 5, 2 ) != $old_month )
  { // Thereafter show only MM-DD when you show anything at all
    // Show month name ala "Dec"
    $MyData->addPoints( date("M", mktime( 0, 0, 0, substr( $row['date'], 5, 2 ), 1) ), "Labels" );
  }
  else
  { // Add a blank instead of text for some x-axis labels.
    $MyData->addPoints( VOID, "Labels" );
  }
  $old_month = substr( $row["date"], 5, 2 );

  $MyData->addPoints( $row[ $column1 ], $series1 );
  $MyData->addPoints( $row[ $column2 ], $series2 );
  if( $row[ $column1 ] < $chart_y_min ) $chart_y_min = $row[ $column1 ];
  if( $row[ $column2 ] > $chart_y_max ) $chart_y_max = $row[ $column2 ];
}
disconnect_from_db();


// Set line style, color, and alpha blending level  (both charts use the same line styles)
$serieSettingsMin = array( "R" => 100, "G" => 100, "B" => 230, "Alpha" => 100 );
$serieSettingsMax = array( "R" => 230, "G" => 100, "B" => 100, "Alpha" => 100 );

$MyData->setSerieOnAxis( $series1, 0 ); // Attach the data series to the axis (by ordinal)
$MyData->setSerieOnAxis( $series2, 0 );
$MyData->setPalette( $series1, $serieSettingsMin );
$MyData->setPalette( $series2, $serieSettingsMax );

// Set names for Y-axis labels
$MyData->setAxisName( 0, "Temperatures" );

// Set names for X-axis labels
$MyData->setSerieDescription( "Labels", "Days" );
$MyData->setAbscissa( "Labels" );

$myPicture = new pImage( 900, 430, $MyData ); // Create the pChart object
$myPicture->Antialias = TRUE;                 // Turn on Antialiasing

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

// Set font for all descriptive text
$myPicture->setFontProperties( array( "FontName" => "lib/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 10 ) );

// Write the picture title
$myPicture->drawText( 10, 14, "Show the historic temperatures", array( "R" => 255, "G" => 255, "B" => 255) );

// Write the chart title
$myPicture->drawText( 60, 55, "Min/Max for each day in the record", array( "FontSize" => 12, "Align" => TEXT_ALIGN_BOTTOMLEFT ) );

$myPicture->setGraphArea( 60, 60, 850, 390 );   // Define the chart area

// Explicity set a scale for the drawing.
$AxisBoundaries = array( 0 => array ( "Min" => $chart_y_min, "Max" => $chart_y_max ) );
// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$scaleSettings = array( "Mode" => SCALE_MODE_MANUAL, "ManualScale" => $AxisBoundaries, "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => TRUE, "CycleBackground" => TRUE );
$myPicture->drawScale( $scaleSettings );

// Write the chart legend
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
/*
 * Frustrating that there is no actual auto-position function for this legend.
 * Since the "indoor" and "outdoor" texts are different lengths they have to be manually positioned.
 * The right hand end of the legend is aligned between these two charts and all the others too.
 */
if( $indoor == 1 )
{ // Each letter in the font I've picked is 10 pixels wide.
  $myPicture->drawLegend( 665, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL, "Align" => TEXT_ALIGN_BOTTOMRIGHT ) );
}
else
{
  $myPicture->drawLegend( 645, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL, "Align" => TEXT_ALIGN_BOTTOMRIGHT ) );
}

$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10 ) );
$myPicture->drawLineChart();  // Used to be a bar chart, but once you get a few months in there it gets far too busy looking.

$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" =>0 , "B" =>0 , "Alpha" => 10 ) );
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Forgotte.ttf", "FontSize" => 11 ) );


// Render the picture
$myPicture->autoOutput();
?>
