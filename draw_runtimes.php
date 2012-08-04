<?php
REQUIRE "common.php";

connect_to_db();

// Find the limits of the run time data and match that to the outdoor temp data
$sql = "SELECT min(date) AS min_date, max(date) AS max_date FROM " . $table_prefix . "run_times;";
$result = mysql_query( $sql );
$row = mysql_fetch_array( $result );
$min_date = $row[ "min_date" ];
$max_date = $row[ "max_date" ];

// Bug?  When it is between 00:00 and 00:30 the bars seem to be offset one position to the right?
// If so it's probably a discrepency between the two SQLs here.

$sql = "SELECT date, heat_runtime, cool_runtime, "
       . "(SELECT MAX(outdoor_temp) "
       . " FROM " . $table_prefix . "temperatures "
       . " WHERE " . $table_prefix . "run_times.date = DATE(" . $table_prefix . "temperatures.date) ) AS outdoor_max, "
       . "(SELECT MIN(outdoor_temp) "
       . " FROM " . $table_prefix . "temperatures "
       . " WHERE " . $table_prefix . "run_times.date = DATE(" . $table_prefix . "temperatures.date) ) AS outdoor_min "
       . "FROM " . $table_prefix . "run_times "
       . "ORDER by date ASC;";
$result = mysql_query( $sql );

// Create and populate the pData object
$MyData = new pData();

// Set default boundaries for chart
$chart_temp_min = $normal_low;
$chart_temp_max = $normal_high;
$chart_runtime_min = 0;
$chart_runtime_max = 360;  // Set six hours as the default runtime scale max

$days = mysql_num_rows( $result );  // Determine the number of days in the resultset.
$first = true;
while( $row = mysql_fetch_array( $result ) )
{
  // There can be too many dates to show them ALL on the x-axis - it turns into one black line.
  if( $first )
  { // Do show the WHOLE date for the first item.
    $MyData->addPoints( $row["date"], "Labels" );
    $first = false;
  }
  else
  { // Thereafter show only MM-DD when you show anything at all
    if( $days > 14 )
    { // When showing more than two weeks of data only show Sundays
      if( date_format( date_create($row['date']), "N" ) == 7 )
      { // Show the date only for the first day of each week
        $MyData->addPoints( substr( $row['date'], 5, 5 ), "Labels" );
      }
      else
      { // Placekeeper for non-shown dates
        $MyData->addPoints( VOID, "Labels" );
      }
    }
    else
    { // Show every day when you are looking at two weeks or less
      $MyData->addPoints( substr( $row['date'], 5, 5 ), "Labels" );
    }
  }

  if( $row["heat_runtime"] != "VOID" )
  { // Assume that if one data point is bad, they all are.
    $MyData->addPoints( $row["heat_runtime"], "Heat" );
    if( $row["heat_runtime"] > $chart_runtime_max ) $chart_runtime_max = $row["heat_runtime"];

    $MyData->addPoints( $row["cool_runtime"], "Cool" );
    if( $row["cool_runtime"] > $chart_runtime_max ) $chart_runtime_max = $row["cool_runtime"];

    $MyData->addPoints( $row["outdoor_min"], "Low Temperature" );
    $MyData->addPoints( $row["outdoor_max"], "High Temperature" );
    if( $row["outdoor_min"] < $chart_temp_min ) $chart_temp_min = $row["outdoor_min"];
    if( $row["outdoor_max"] > $chart_temp_max ) $chart_temp_max = $row["outdoor_max"];
  }
  else
  {
    $MyData->addPoints( VOID, "Heat" );
    $MyData->addPoints( VOID, "Cool" );
    $MyData->addPoints( VOID, "High Temperature" );
    $MyData->addPoints( VOID, "Low Temperature" );
  }
}

disconnect_from_db();

// Attach the data series to the axis (by ordinal.  0 is X-axis)
$MyData->setSerieOnAxis( "Heat", 0 );
$MyData->setSerieOnAxis( "Cool", 0 );
$MyData->setSerieOnAxis( "High Temperature", 1 );
$MyData->setSerieOnAxis( "Low Temperature", 1 );
$MyData->setAxisPosition( 1, AXIS_POSITION_RIGHT );

// Set line style, color, and alpha blending level
$MyData->setSerieTicks( "Heat", 0 );  // 0 is a solid line
$serieSettings = array( "R" => 150, "G" => 50, "B" => 80, "Alpha" => 90 );
$MyData->setPalette( "Heat", $serieSettings );

$serieSettings = array( "R" => 50, "G" => 150, "B" => 180, "Alpha" => 90 );
$MyData->setPalette( "Cool", $serieSettings );

$serieSettings = array( "R" => 175, "G" => 75, "B" => 105, "Alpha" => 100 );
$MyData->setPalette( "High Temperature", $serieSettings );
$serieSettings = array( "R" => 75, "G" => 105, "B" => 175, "Alpha" => 100 );
$MyData->setPalette( "Low Temperature", $serieSettings );

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

// Set font for all descriptive text
$myPicture->setFontProperties( array( "FontName" => "lib/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 10 ) );

// Write the picture title
$myPicture->drawText( 10, 14, "HVAC run times", array( "R" => 255, "G" => 255, "B" => 255) );

// Define shadows under series lines
$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 50, "G" => 50, "B" => 50, "Alpha" => 20 ) );

// Define the chart area
$myPicture->setGraphArea( 60, 60, 850, 390 );

// Explicity set a scale for the drawing.
$AxisBoundaries = array( 0 => array ( "Min" => $chart_runtime_min, "Max" => $chart_runtime_max ), 1 => array ( "Min" => $chart_temp_min, "Max" => $chart_temp_max ) );
// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$Settings = array("Pos"=>SCALE_POS_LEFTRIGHT, "Mode"=>SCALE_MODE_MANUAL, "ManualScale"=>$AxisBoundaries, "LabelingMethod"=>LABELING_ALL, "GridR"=>255, "GridG"=>255, "GridB"=>255, "GridAlpha"=>50, "TickR"=>0, "TickG"=>0, "TickB"=>0, "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, "DrawYLines"=>ALL);
$myPicture->drawScale($Settings);

// Define shadows under series lines
$myPicture->setShadow( FALSE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 40 ) );

// Draw the outdoor max temperature as a filled line chart
$MyData->setSerieDrawable( "Heat", FALSE );
$MyData->setSerieDrawable( "Cool", FALSE );
$MyData->setSerieDrawable( "High Temperature", TRUE );
$MyData->setSerieDrawable( "Low Temperature", TRUE );
$myPicture->drawAreaChart();

// Draw the run times as bar charts
$MyData->setSerieDrawable( "Heat", TRUE );
$MyData->setSerieDrawable( "Cool", TRUE );
$MyData->setSerieDrawable( "High Temperature", FALSE );
$MyData->setSerieDrawable( "Low Temperature", FALSE );
$myPicture->drawBarChart( array( "Gradient" => 1, "AroundZero" => TRUE, "Interleave" => 5 ) );

// Write the chart legend
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
$myPicture->drawLegend( 745, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL ) );

$myPicture->autoOutput();
?>