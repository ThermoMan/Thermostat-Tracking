<?php
require_once 'common.php';
require_once 'common_chart.php';

$table_flag = false;
if( isset( $_GET['table_flag'] ) && $_GET['table_flag'] == 'true' )
{
  $table_flag = true;
}

$from_date = date( 'Y-m-d' );
if( isset( $_GET['chart_daily_fromDate'] ) )
{ // Use provided date
  $from_date = $_GET['chart_daily_fromDate'];
}
if( ! validate_date( $from_date ) ) return;

$to_date = date( 'Y-m-d' );
if( isset( $_GET['chart_daily_toDate'] ) )
{ // Use provided date
  $to_date = $_GET['chart_daily_toDate'];
}
if( ! validate_date( $to_date ) ) return;
// Verify that date is not future?

// Verify that date is at least three days BEFORE the to_date?


// Set default cycle display to none
// Somehow the "." is converted to an "_" which I guess I have to accept, but I don't like.
$show_heat_cycles = (isset($_GET['chart_daily_showHeat']) && ($_GET['chart_daily_showHeat'] == 'false')) ? true : false;
$show_cool_cycles = (isset($_GET['chart_daily_showCool']) && ($_GET['chart_daily_showCool'] == 'false')) ? false : true;
$show_fan_cycles  = (isset($_GET['chart_daily_showFan'])  && ($_GET['chart_daily_showFan']  == 'true')) ? true : false;

// OK, now that we have a bounding range of dates, build an array of all the dates in the range
$check_date = $from_date;
$days = array( $check_date );
$dayCount = 1;
while( $check_date != $to_date )
{
  $check_date = date( 'Y-m-d', strtotime( '+1 day', strtotime( $check_date ) ) );
  array_push( $days, $check_date );
	$dayCount++;
  if( $dayCount > 31 )
  { // Special logic for large data sets
		/**
		  * A large data set takes a long time to graph.  90 days at one temp per half-hour takes more than
			* 30 seconds and that is osme kind of hard-limit coded into the chart package.  And the chart
			* package just aborts...
			*
			* One fix it to find that limit and change it.  Another is to deal with it (and the actual user
			* experienced delay) by changing the detail that is graphed.
			*
			* So, go ahead and SELECT all the data points, but on display on show every second one (on the hour)
			* so this will allow the chart to display up to about 180 days.  This will also greatly help on the
			* X-Axis label problem.
			*
			* Version one should just put up the 'on the hour' data.  Version 2 should use an averge of on hour
			* and on half-hour.  Version 3 should examine the trend and use the max when trend is up and the min
			* when the trend is down.
		  */

		/**
		  * But for now, stop at 31 days.
			*
			* Alter the $to_date variable so that it all just works AND the user sees the change in the chart text
			*/
/*
		$to_date = $check_date;
    break;
*/
  }
}

/*
 *   The DB design for the project is still not as pretty as it could be.  The conversion to a 3 section system is starting though.
 * Section 1 has to do with the collection of data.  That is _mostly_ what is going on in there now.
 *           Check in \scripts for the processes that ADD data to the database.
 *
 * Section 2 will have to do with the presentation of the data in charts.  For instance that hvac_cycles table
 *           exists for two reasons.  Firstly it keeps the 'per minute' table lightweight and secondly it makes charting easier.
 *           If the application adds notifications (for instance power out or over temperature situations) that is reporting
 *           and will go here
 *           The new table time_index has been added to replace a really long nasty SQL section of hard-coded time stamps.  The
 *           table name ought to reflect the function. Perhaps should be renamed to chart_time_index?  And don't forget the
 *           global table name prefix either! (The name might look like thermo2__chart_time_index)
 *
 * Section 3 will be for the management of the website that presents the data.  If there will be a user registration system, the
 *           data for that will be stored in this set of tables.
 *
 *   The goal of this split of design is for two purposes.
 * Purpose 1 is for good MVC separation.  While ideological adherence to any design pattern is usually detrimental to real-world
 *           coding, patterns exist to make things easier to maintain in the long run.  Patterns are tools, use the ones that
 *           make life easy, discard the ones that are a PITA.
 *
 * Purpose 2 is for integration with other projects.  For instance the TED-5000 project also collects data and presents it.  The
 *           two projects can be used together and as such the data collection tables are unique to each project, but the website
 *           management are functionally identical and therefore when used together these tables should NOT be dupicated. In
 *           addition each project has it's own charting needs, but the combined charts will have constraints because of the shared
 *           presentation needs.
 */

$sql =
"SELECT CONCAT( ?, ' ', b.time ) AS date,
				IFNULL(a.indoor_temp, 'VOID') as indoor_temp,
				IFNULL(a.outdoor_temp, 'VOID') as outdoor_temp
 FROM {$dbConfig['table_prefix']}time_index b
 LEFT JOIN {$dbConfig['table_prefix']}temperatures a
 ON a.date = CONCAT( ?, ' ', b.time ) AND a.tstat_uuid = ? ";
$minutes = '30';

if( $dayCount >= 70 )
{	// Reduce data set if there are more than 70 days.
	$sql .= "WHERE SUBSTR( b.time, 2, 3 ) != ':30' ";
	$minutes = '60';	// Repeated setting is redundant, but it's better to keep this text change with the SQL change.
}
$query = $pdo->prepare( $sql );


// Set default boundaries for chart
$chart_y_min = $normalLows[ date( 'n', strtotime($from_date) )-1 ];
$chart_y_max = $normalHighs[ date( 'n', strtotime($from_date) )-1 ];

if( ! $table_flag )
{ // Create, then populate the pData object (it expects to be presented as an img src=)
	$MyData = new pData();
}
else
{	// Start the tabular display
	echo '<link href="resources/thermo.css" rel="stylesheet" type="text/css" />';	// It expects to be presented in an iframe which does NOT inherit the parent css
	echo "<br>Normal low for this month is $chart_y_min.";
	echo "<br>Normal high for this month is $chart_y_max.";
	echo "<br>Showing data every $minutes minutes for $dayCount days from $from_date to $to_date.";
	//echo "<br>The SQL<br>$sql";
	echo '<table class="thermo_chart"><th class="thermo_chart">Date</th><th class="thermo_chart">Indoor Temp</th><th class="thermo_chart">Outdoor Temp</th>';
}

$dates = '';
$very_first = true;

foreach( $days as $show_date )
{
  $dates .= $show_date . '   ';

  $query->execute( array( $show_date, $show_date, $uuid ) );

  $counter = 0;
	$first_row = true;
  while( $row = $query->fetch( PDO::FETCH_ASSOC ) )
  {
/* Chart of things that work for X-axis labels
days  divisor
 1		 $dayCount
 6		 $dayCount
 7		 6
 8		 6
 9		 8
10		 8
11		12 (date and noon)
16		12
17		24 (date only)
31		24
32		each week start date
70 Change to every hour SELECT instead of every half hour SELECT
The charting softwre borks if the internal rendering time limit of 30 seconds is hit.  Happens around
~75 days of every half-hour
~80 days of hours
This is dependant upon server load...
*/
		if( $dayCount > 13 ) $labelDivisor = 24;
		else if( $dayCount > 10 ) $labelDivisor = 12;
		else if( $dayCount >  8 ) $labelDivisor =  8;
		else if( $dayCount >  6 ) $labelDivisor =  6;
		else $labelDivisor = $dayCount;

		if( ! $table_flag )
		{	// Only set X-Axis labels if we're displaying a chart
			if( $very_first )
			{	// Always show the first one - regardless of settings
				if( $dayCount < 6 )
				{	// Show time if we have only a few days.
					$MyData->addPoints( substr( $row['date'], 11, 5 ), 'Labels' );
				}
				else
				{	// Show date if we have a lot.
					$MyData->addPoints( substr( $row[ 'date' ], 5, 5 ), 'Labels' );
				}
			}
			else
			{
				if( $dayCount <= 28 )
				{
					if( ( substr( $row['date'], 13, 3 ) == ':00' ) && ( substr( $row['date'], 11, 2 ) % $labelDivisor == 0 ) )
					{	// Only show axis every -interval- hours
						if( substr( $row['date'], 11, 2 ) == '00' )
						{	// At midnight show the new date (How to add emphasis to distinguish from time stamps?)
							$MyData->addPoints( substr( $row['date'], 5, 5 ), 'Labels' );
						}
						else
						{
							$MyData->addPoints( substr( $row['date'], 11, 5 ), 'Labels' );
						}
					}
					else
					{
						$MyData->addPoints( VOID, 'Labels' );
					}
				}
				else
				{	// All other intervals...
					if( date_format( date_create( $row[ 'date' ]), 'N' ) == 7 )
					{ // Show the date only for the first day of each week in mm-dd format
						$MyData->addPoints( substr( $row[ 'date' ], 5, 5 ), 'Labels' );
					}
					else
					{ // Placekeeper for non-shown dates
						$MyData->addPoints( VOID, 'Labels' );
					}
				}
			}
			$MyData->addPoints( ($row['indoor_temp'] == 'VOID' ? VOID : $row['indoor_temp']), 'Indoor' );
			$MyData->addPoints( ($row['outdoor_temp'] == 'VOID' ? VOID : $row['outdoor_temp']), 'Outdoor' );
		}
		else
		{
			echo '<tr><td class="thermo_chart">'.$row['date'].'</td><td class="thermo_chart">'.($row['indoor_temp'] == 'VOID' ? '&nbsp;' : $row['indoor_temp']).'</td><td class="thermo_chart">'.($row['outdoor_temp'] == 'VOID' ? '&nbsp;' : $row['outdoor_temp']).'</td></tr>';
		}
		$very_first = false;

		/**
		  * Expand chart boundaries to contain data that exceeds the default boundaries
		  * 'VOID' values test poorly in inequality against numeric values so us 50 when the data is bad.
		  * Increement or decrement by ten to keep the chart boundaries pretty
			*/
		while( ($row['indoor_temp'] == 'VOID' ? 50 : $row['indoor_temp']) < $chart_y_min ) $chart_y_min -= 10;
		while( ($row['indoor_temp'] == 'VOID' ? 50 : $row['indoor_temp']) > $chart_y_max ) $chart_y_max += 10;
		while( ($row['outdoor_temp'] == 'VOID' ? 50 : $row['outdoor_temp']) < $chart_y_min ) $chart_y_min -= 10;
		while( ($row['outdoor_temp'] == 'VOID' ? 50 : $row['outdoor_temp']) > $chart_y_max ) $chart_y_max += 10;
  }
}

if( $table_flag )
{	// If we're showing the data in a chart, we're done now.  Wrap up the table tag and press the eject button.
	echo '</table>';
	echo "<br>Adjusted low for this month is $chart_y_min.";
	echo "<br>Adjusted high for this month is $chart_y_max.";
	return;
}

// Attach the data series to the axis (by ordinal)
$MyData->setSerieOnAxis( 'Indoor', 0 );
$MyData->setSerieOnAxis( 'Outdoor', 0 );

// Set line style, color, and alpha blending level
$MyData->setSerieTicks( 'Indoor', 0 );  // 0 is a solid line
$serieSettings = array( 'R' => 50, 'G' => 150, 'B' => 80, 'Alpha' => 100 );
$MyData->setPalette( 'Indoor', $serieSettings );

$MyData->setSerieTicks( 'Outdoor', 2 ); // n is length in pixels of dashes in line
$serieSettings = array( 'R' => 150, 'G' => 50, 'B' => 80, 'Alpha' => 100 );
$MyData->setPalette( 'Outdoor', $serieSettings );

// Set names for Y-axis labels
$MyData->setAxisName( 0, 'Temperatures' );

// Set names for X-axis labels
$MyData->setSerieDescription( 'Labels', 'The march of the hours' );
$MyData->setAbscissa( 'Labels' );


/**
	* Set variables for going into common block
	*/
if( $dayCount == 1 ) $picTitle = "Show temperatures for $from_date";
else $picTitle = "Show temperatures for $from_date - $to_date ($dayCount days)";
$chartTitle = "Temperature every $minutes minutes across the span of dates";
// Explicity set a scale for the drawing.
$AxisBoundaries = array( 0 => array ( 'Min' => $chart_y_min, 'Max' => $chart_y_max ) );


/**
	* START of common block - this code should be identical for all charts so that they have a common look and feel
	*/
$myPicture = new pImage( 900, 430, $MyData );	// Create the pChart object
$myPicture->Antialias = TRUE;									// Turn on Antialiasing

// Draw the background
$Settings = array( 'R' => 170, 'G' => 183, 'B' => 87, 'Dash' => 1, 'DashR' => 190, 'DashG' => 203, 'DashB' => 107, 'Alpha' => 60 );
$myPicture->drawFilledRectangle( 0, 0, 900, 430, $Settings );

// Overlay with a gradient
$Settings = array( 'StartR' => 219, 'StartG' => 231, 'StartB' => 139, 'EndR' => 1, 'EndG' => 138, 'EndB' => 68, 'Alpha' => 50 );
$myPicture->drawGradientArea( 0, 0, 900, 430, DIRECTION_VERTICAL, $Settings );
$Settings = array( 'StartR' => 0, 'StartG' => 0, 'StartB' => 0, 'EndR' => 50, 'EndG' => 50, 'EndB' => 50, 'Alpha' => 80 );
$myPicture->drawGradientArea( 0, 0, 900,	20, DIRECTION_VERTICAL, $Settings );

// Add a border to the picture
$myPicture->drawRectangle( 0, 0, 899, 429, array( 'R' => 0, 'G' => 0, 'B' => 0 ) );

// Set font for all descriptive text
$myPicture->setFontProperties( array( 'FontName' => 'lib/fonts/Copperplate_Gothic_Light.ttf', 'FontSize' => 10 ) );

// Write picture and chart titles
$myPicture->drawText( 10, 14, $picTitle, array( 'R' => 255, 'G' => 255, 'B' => 255) );
$myPicture->drawText( 60, 55, $chartTitle, array( 'FontSize' => 12, 'Align' => TEXT_ALIGN_BOTTOMLEFT ) );

// Write the picture timestamp
$myPicture->drawText( 680, 14, 'Last update ' . date( 'Y-m-d H:i' ), array( 'R' => 255, 'G' => 255, 'B' => 255) );

$myPicture->setGraphArea( 60, 60, 850, 390 );	 // Define the chart area

// Draw the scale
$myPicture->setFontProperties( array( 'FontName' => 'lib/pChart2.1.3/fonts/pf_arma_five.ttf', 'FontSize' => 6 ) );
$scaleSettings = array( 'Mode' => SCALE_MODE_MANUAL, 'ManualScale' => $AxisBoundaries, 'GridR' => 200, 'GridG' => 200, 'GridB' => 200, 'LabelingMethod' => LABELING_DIFFERENT, 'DrawSubTicks' => TRUE, 'CycleBackground' => TRUE );
$myPicture->drawScale( $scaleSettings );

// Write the chart legend
$myPicture->setFontProperties( array( 'FontName' => 'lib/pChart2.1.3/fonts/pf_arma_five.ttf', 'FontSize' => 6 ) );
$myPicture->setShadow( TRUE, array( 'X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10 ) );
$myPicture->drawLegend( 60, 412, array( 'Style' => LEGEND_NOBORDER, 'Mode' => LEGEND_HORIZONTAL ) );
// END of common block


// Draw the chart(s)
//$myPicture->setShadow( TRUE, array( 'X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 40 ) );	// Define shadows under series lines
$myPicture->drawLineChart( array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO ) );
//$myPicture->setShadow( FALSE );		// No more shadows (so they only apply to the lines)


/**
	* After the chart is created, overlay the HVAC cycles.  I draw these manually because I can't
	*  find a horizontal 'stacked' bar chart that allows missing pieces in it in pChart.
	*
	* To make the rendering portion faster it would be better to do the SQL operations before the initiation
	*  of the charting and copy the data into an array to pass in to the drawing code.
	*
	* This representation of cycle runtimes has some serious omissions.
	*
	* Omission 1:
	*  is that presently running cycles are not shown since the data is soruced from the completed cycle table.
	*  to fix that a small query on the per minute table with a start time of the last stop from the first SQL
	*  should be added.  The display should indicate this is open ended (lighter color perhaps or use static images?)
	*
	* Others were fixed....
	*/
if( ($show_heat_cycles + $show_cool_cycles + $show_fan_cycles) >0 )
{ // For a $show_date of '2012-07-10' get the start and end bounding datetimes
  $start_date = strftime( '%Y-%m-%d 00:00:00', strtotime($from_date));	// "2012-07-10 00:00:00";
  $end_date = strftime( '%Y-%m-%d 23:59:59', strtotime($to_date));			// "2012-07-10 23:59:59";

  /**
		* This SQL should include cycles that started on the previous night or ended on the
		*  following morning for any given date.
		*
		* Ought to graphically differentiate those open ended cycles somehow?
		*/
  $sql =
  "SELECT system,
					DATEDIFF( start_time, ? ) AS start_day,
					DATEDIFF( end_time, ? ) AS end_day,
          DATE_FORMAT( GREATEST( start_time, ? ), '%k' ) AS start_hour,
          TRIM(LEADING '0' FROM DATE_FORMAT( GREATEST( start_time, ? ), '%i' ) ) AS start_minute,
          DATE_FORMAT( LEAST( end_time, ? ), '%k' ) AS end_hour,
          TRIM( LEADING '0' FROM DATE_FORMAT( LEAST( end_time, ? ), '%i' ) ) AS end_minute
  FROM {$dbConfig['table_prefix']}hvac_cycles
  WHERE start_time >= ? AND end_time <= ? AND tstat_uuid = ?
  ORDER BY start_time ASC";

/*
echo "<br>sql is $sql";
echo "<br>start_date is $start_date";
echo "<br>end_date is $end_date";
echo "<br>uuid is $uuid";
*/
  $query = $pdo->prepare($sql);
  $result = $query->execute(array( $start_date, $start_date, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $uuid ) );

  // The rounded corners look so much better, but the run times are so short that the rounds seldom appear.
  $HeatGradientSettings = array( 'StartR' => 200, 'StartG' => 100, 'StartB' => 100, 'Alpha' => 65, 'Levels' => 90, 'BorderR' =>  0, 'BorderG' =>  0, 'BorderB' => 0  );
  $CoolGradientSettings = array( 'StartR' =>  50, 'StartG' =>  50, 'StartB' => 200, 'Alpha' => 65, 'Levels' => 90, 'BorderR' =>  0, 'BorderG' =>  0, 'BorderB' => 0  );
  $FanGradientSettings  = array( 'StartR' => 255, 'StartG' => 255, 'StartB' =>   0, 'Alpha' => 65, 'Levels' => 90, 'BorderR' =>  0, 'BorderG' =>  0, 'BorderB' => 0  );
  $RectHeight = 20;
  $HeatRectRow = 150;
  $CoolRectRow = 175;
  $FanRectRow = 200;
  $LeftMargin = 69;
  $PixelsPerMinute = 0.5354 / $dayCount;
  /**
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
		* The $dayCount factor was added to account for the number of days in the display.  Too many days and the dispaly will be really ugly
		*
		* Cycle data is represented by drawing objects, so it has to be AFTER the creation of $myPicture
		*/

//echo "<table border='1'>";
  while( $row = $query->fetch( PDO::FETCH_ASSOC ) )
  {
/*
echo '<tr>';
foreach($row as $cell)echo "<td>$cell</td>";
echo '</tr>';
*/
    // 'YYYY-MM-DD HH:mm:00'  There are NO seconds in these data points.
    $cycle_start = $LeftMargin + (($row['start_day'] * 1440) + ($row['start_hour'] * 60) + $row['start_minute'] ) * $PixelsPerMinute;
    $cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

    if( $row['system'] == 1 && $show_heat_cycles == 1 )
    { // Heat
      $myPicture->drawGradientArea( $cycle_start, $HeatRectRow, $cycle_end, $HeatRectRow + $RectHeight, DIRECTION_HORIZONTAL, $HeatGradientSettings );
    }
    else if( $row['system'] == 2 && $show_cool_cycles == 1 )
    { // A/C
      $myPicture->drawGradientArea( $cycle_start, $CoolRectRow, $cycle_end, $CoolRectRow + $RectHeight, DIRECTION_HORIZONTAL, $CoolGradientSettings );
    }
    else if( $row['system']== 3 && $show_fan_cycles == 1 )
    { // Fan
      $myPicture->drawGradientArea( $cycle_start, $FanRectRow, $cycle_end, $FanRectRow + $RectHeight, DIRECTION_HORIZONTAL, $FanGradientSettings );
    }
  }
//echo "</table>";
  // If $start_date is today then also look in the hvac_status table and see if there is an open ended run going on right now.
  /*
  $sql = "SELECT MIN(date) FROM thermo_hvac_status WHERE heat_status = 1 and date(date) = '$start_date'";
  $result = mysql_query( $sql );
  $row = mysql_fetch_array( $result ); // I expect either zero or one row from the SQL
  */
  // From that date roll forward and see if there is more than once cycle to add
}

$myPicture->autoOutput( 'images/daily_chart.png' );
?>