<?php
REQUIRE "common.php";

connect_to_db();

$show_date = date( "Y-m-d" );
if( isset( $_GET["show_date"] ) )
{ // Use provided date
  $show_date = $_GET["show_date"];
}
if( ! validate_date( $show_date ) ) return;

// Set default cycle display to none
$show_heat_cycles = 0;
if( isset( $_GET["show_heat_cycles"] ) )
{
  if( $_GET["show_heat_cycles"] == "true" )
  {
    $show_heat_cycles = 1;
  }
}
$show_cool_cycles = 0;
if( isset( $_GET["show_cool_cycles"] ) )
{
  if( $_GET["show_cool_cycles"] == "true" )
  {
    $show_cool_cycles = 1;
  }
}
$show_fan_cycles = 0;
if( isset( $_GET["show_fan_cycles"] ) )
{
  if( $_GET["show_fan_cycles"] == "true" )
  {
    $show_fan_cycles = 1;
  }
}

/*
 *   The SQL is still not as pretty as it could be.  The conversion to a 3 section system is starting though.
 *
 * Section 1 has to do with the collection of data.  That is _mostly_ what is going on in there now.
 *
 * Section 2 will have to do with the presentation of the data in charts.  For instance that hvac_cycles table
 *           exists for two reasons.  Firstly it keeps the 'per minute' table lightweight and secondly it makes charting easier.
 *           If the application adds notificaitons (for instance power out or over temperature situations) that is reporting
 *           and will go here
 *           The new table time_index has been added to replace a really long nasty SQL section of hard-coded time stamps.  The
 *           table name ought to reflect the function. Perhaps should be renamed to chart_time_index?  And don't forget the
 *           global table name prefix either!
 *
 * Section 3 will be for the management of the website that presents the data.  If there will be user registration, it will go here
 *
 */
$sql = "SELECT CONCAT( '$show_date', ' ', b.time ) AS date, IFNULL(a.indoor_temp, 'VOID') as indoor_temp, IFNULL(a.outdoor_temp, 'VOID') as outdoor_temp "
. "FROM " . $table_prefix . "time_index b "
. "LEFT JOIN " . $table_prefix . "temperatures a "
. "ON a.date = TIMESTAMP( '$show_date', b.time );";

$result = mysql_query( $sql );

// Create and populate the pData object
$MyData = new pData();

// Set default boundaries for chart
$chart_y_min = $normal_low;
$chart_y_max = $normal_high;

while( $row = mysql_fetch_array( $result ) )
{
  if( substr( $row["date"], 13, 3 ) == ":00" )
  {
    $MyData->addPoints( substr( $row["date"], 11, 5 ), "Labels" );
  }
  else
  {
    $MyData->addPoints( VOID, "Labels" );
  }

  if( $row["indoor_temp"] != "VOID" )
  {
    $MyData->addPoints( $row["indoor_temp"], "Indoor" );
    $MyData->addPoints( $row["outdoor_temp"], "Outdoor" );

  // Expand chart boundaries to contain data that exceeds the default boundaries
  if( $row["indoor_temp"] < $chart_y_min ) $chart_y_min = $row["indoor_temp"];
  if( $row["indoor_temp"] > $chart_y_max ) $chart_y_max = $row["indoor_temp"];
  if( $row["outdoor_temp"] < $chart_y_min ) $chart_y_min = $row["outdoor_temp"];
  if( $row["outdoor_temp"] > $chart_y_max ) $chart_y_max = $row["outdoor_temp"];
  }
  else
  {
    $MyData->addPoints( VOID, "Indoor" );
    $MyData->addPoints( VOID, "Outdoor" );
  }
}

// Attach the data series to the axis (by ordinal)
$MyData->setSerieOnAxis( "Indoor", 0 );
$MyData->setSerieOnAxis( "Outdoor", 0 );

// Set line style, color, and alpha blending level
$MyData->setSerieTicks( "Indoor", 0 );  // 0 is a solid line
$serieSettings = array( "R" => 50, "G" => 150, "B" => 80, "Alpha" => 100 );
$MyData->setPalette( "Indoor", $serieSettings );

$MyData->setSerieTicks( "Outdoor", 2 ); // n is length in pixels of dashes in line
$serieSettings = array( "R" => 150, "G" => 50, "B" => 80, "Alpha" => 100 );
$MyData->setPalette( "Outdoor", $serieSettings );

// Set names for Y-axis labels
$MyData->setAxisName( 0, "Temperatures" );

// Set names for X-axis labels
$MyData->setSerieDescription( "Labels", "The march of the hours" );
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
$myPicture->drawText( 10, 14, "Show temperatures for " . $show_date, array( "R" => 255, "G" => 255, "B" => 255) );

// Write the picture timestamp
$myPicture->drawText( 680, 14, "Last update " . date( "Y-m-d H:i" ), array( "R" => 255, "G" => 255, "B" => 255) );

// Write the chart title
$myPicture->drawText( 60, 55, "Temperature every 30 minutes since midnight", array( "FontSize" => 12, "Align" => TEXT_ALIGN_BOTTOMLEFT ) );

// Define the chart area
$myPicture->setGraphArea( 60, 60, 850, 390 );

// Explicity set a scale for the drawing.
$AxisBoundaries = array( 0 => array ( "Min" => $chart_y_min, "Max" => $chart_y_max ) );
// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$scaleSettings = array( "Mode" => SCALE_MODE_MANUAL, "ManualScale" => $AxisBoundaries, "XMargin" => 10, "YMargin" => 10, "Floating" => FALSE, "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => TRUE, "CycleBackground" => TRUE );
$myPicture->drawScale( $scaleSettings );

// Define shadows under series lines
$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 40 ) );
// Draw the lines
$myPicture->drawLineChart( array( "DisplayValues" => FALSE, "DisplayColor" => DISPLAY_AUTO ) );
// No more shadows (so they only apply to the lines)
$myPicture->setShadow( FALSE );

// Write the chart legend (same font as scale, but slightly larger size)
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
$myPicture->drawLegend( 710, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL ) );

/*
 * This representation of cycle runtimes has one serious omission.
 *
 * Omission 1 is that presently running cycles are not shown since the data is soruced from the completed cycle table.
 *            to fix that a small query on the per minute table with a start time of the last stop from the first SQL
 *            should be added.  The display should indicate this is open ended (lighter color perhaps or use static images?)
 */
if( ($show_heat_cycles + $show_cool_cycles + $show_fan_cycles) >0 )
{ // For a $show_date of "2012-07-10" get the start and end bounding datetimes
  $start_date = strftime( "%Y-%m-%d 00:00:00", strtotime($show_date));  // "2012-07-10 00:00:00";
  // $end_date = strftime( "%Y-%m-%d 00:00:00", strtotime("+1 day", strtotime($show_date)));   // "2012-07-11 00:00:00";
  $end_date = strftime( "%Y-%m-%d 23:59:59", strtotime($show_date));    // "2012-07-10 23:59:59";

  /*
   * This SQL now includes cycles that start on the previous night or end on the following morning.  The
   * actual returned values are bounded by 00:00 and 23:59
   *
   * Ought to differentiate the open ended cycles somehow?
   */
  $sql = "SELECT system, "
  . "DATE_FORMAT( GREATEST( start_time, '$start_date' ), '%k' ) AS start_hour, "
  . "TRIM(LEADING '0' FROM DATE_FORMAT( GREATEST( start_time, '$start_date' ), '%i' ) ) AS start_minute, "
  . "DATE_FORMAT( LEAST( end_time, '$end_date' ), '%k' ) AS end_hour, "
  . "TRIM( LEADING '0' FROM DATE_FORMAT( LEAST( end_time, '$end_date' ), '%i' ) ) AS end_minute "
  . "FROM " . $table_prefix . "hvac_cycles "
  . "WHERE end_time > '$start_date' AND start_time < '$end_date' ORDER BY start_time ASC";

  $result = mysql_query( $sql );

  // The rounded corners look so much better, but the run times are so short that the rounds seldom appear.
  //$HeatRectSettings = array( "R" => 200, "G" => 100, "B" => 100, "BorderR" =>  0, "BorderG" =>  0, "BorderB" => 0, "Alpha" => 75 );
  //$CoolRectSettings = array( "R" =>  50, "G" =>  50, "B" => 200, "BorderR" =>  0, "BorderG" =>  0, "BorderB" => 0, "Alpha" => 75 );
  //$FanRectSettings  = array( "R" => 255, "G" => 255, "B" =>   0, "BorderR" =>  1, "BorderG" =>  1, "BorderB" => 1, "Alpha" => 75 );
  $HeatGradientSettings = array( "StartR" => 200, "StartG" => 100, "StartB" => 100, "Alpha" => 65, "Levels" => 90, "BorderR" =>  0, "BorderG" =>  0, "BorderB" => 0  );
  $CoolGradientSettings = array( "StartR" =>  50, "StartG" =>  50, "StartB" => 200, "Alpha" => 65, "Levels" => 90, "BorderR" =>  0, "BorderG" =>  0, "BorderB" => 0  );
  $FanGradientSettings  = array( "StartR" => 255, "StartG" => 255, "StartB" =>   0, "Alpha" => 65, "Levels" => 90, "BorderR" =>  0, "BorderG" =>  0, "BorderB" => 0  );
  $RectHeight = 20;
  //$RectCornerRadius = 3;
  $HeatRectRow = 150;
  $CoolRectRow = 175;
  $FanRectRow = 200;
  $LeftMargin = 69;
  $PixelsPerMinute = 0.5354;
  /*
   * Assumptions:
   *  1. The chart X-axis represents 24 hours
   *  2. The chart horizontal area is 782 pixels wide (so each pixel represents 1.84 minutes)
   *
   * Why 0.5354?
   *
   * The chart area boundary is defined as 900px wide.
   * There are 70 pixels left of the 00:00.  There are 59 pixels to the right of 24:00
   * There are 1440 minutes in a day
   * (900 - (70 + 59)) / 1440 = .5354
   *
   * With post applied fudge factor ( -0.0005) to make it look better on screen.
   */

  // Cycle data is represented by drawing objects, so it has to be AFTER the creation of $myPicture
  while( $row = mysql_fetch_array( $result ) )
  {

    // "YYYY-MM-DD HH:mm:00"  There are NO seconds in these data points.
    $cycle_start = $LeftMargin + (($row["start_hour"] * 60) + $row["start_minute"] ) * $PixelsPerMinute;
    $cycle_end   = $LeftMargin + (($row["end_hour"]   * 60) + $row["end_minute"] )   * $PixelsPerMinute;

    //$myPicture->setShadow( TRUE, array( "X" => -1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 75 ) );
    if( $row["system"] == 1 && $show_heat_cycles == 1 )
    { // Heat
      //$myPicture->drawRoundedFilledRectangle( $cycle_start, $HeatRectRow, $cycle_end, $HeatRectRow + $RectHeight, $RectCornerRadius, $HeatRectSettings );
      $myPicture->drawGradientArea( $cycle_start, $HeatRectRow, $cycle_end, $HeatRectRow + $RectHeight, DIRECTION_HORIZONTAL, $HeatGradientSettings );
    }
    else if( $row["system"] == 2 && $show_cool_cycles == 1 )
    { // A/C
      //$myPicture->drawRoundedFilledRectangle( $cycle_start, $CoolRectRow, $cycle_end, $CoolRectRow + $RectHeight, $RectCornerRadius, $CoolRectSettings );
      $myPicture->drawGradientArea( $cycle_start, $CoolRectRow, $cycle_end, $CoolRectRow + $RectHeight, DIRECTION_HORIZONTAL, $CoolGradientSettings );
    }
    else if( $row["system"]== 3 && $show_fan_cycles == 1 )
    { // Fan
      //$myPicture->drawRoundedFilledRectangle( $cycle_start, $FanRectRow, $cycle_end, $FanRectRow + $RectHeight, $RectCornerRadius, $FanRectSettings );
      $myPicture->drawGradientArea( $cycle_start, $FanRectRow, $cycle_end, $FanRectRow + $RectHeight, DIRECTION_HORIZONTAL, $FanGradientSettings );
    }
  }

  // If $start_date is today then also look in the hvac_status table and see if there is an open ended run going on right now.
  /*
  $sql = "SELECT MIN(date) FROM thermo_hvac_status WHERE heat_status = 1 and date(date) = '$start_date'";
  $result = mysql_query( $sql );
  $row = mysql_fetch_array( $result ); // I expect either zero or one row from the SQL
  */
  // From that date roll forward and see if there is more than once cycle to add

}
disconnect_from_db();

$myPicture->autoOutput( "images/daily_chart.png" );
?>