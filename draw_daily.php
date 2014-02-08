<?php
$start_time = microtime(true);
require_once 'common.php';
require_once 'common_chart.php';

/**
	* If the user requests more than about 90 days it will take more than 30 seconds to render
	*	If it takes more than 30 seconds to render the chart package pukes.
	* Solve this perhaps by only getting one temperature per hour when span is 90+ days?
	*
	*/

$table_flag = false;
if( isset( $_GET['table_flag'] ) && $_GET['table_flag'] == 'true' )
{
	$table_flag = true;
}

$source = 2;	// Default to showing both
if( isset( $_GET['chart_daily_source'] ) )
{	// The "." character in the URL is somehow converted to an "_" character when PHP goes to look at it.
	$source = $_GET['chart_daily_source'];
}
if( $source < 0 || $source > 2 )
{ // If it is out of bounds, show both.  0: outdoor, 1: indoor, 2: both
	$source = 2;
}

$to_date = date( 'Y-m-d' );
if( isset( $_GET['chart_daily_toDate'] ) )
{ // Use provided date
	$to_date = $_GET['chart_daily_toDate'];
}
if( ! validate_date( $to_date ) ) return;
// Verify that date is not future?

$interval_measure = 0;	// Default to days
if( isset( $_GET['chart_daily_interval_group'] ) )
{
  $interval_measure = $_GET['chart_daily_interval_group'];
}
if( $interval_measure < 0 || $interval_measure > 3 )
{	// 0: days, 1: weeks, 2: months, 3: years
	$interval_measure = 0;
}

if( isset( $_GET['chart_daily_interval_length'] ) )
{
  $interval_length = $_GET['chart_daily_interval_length'];

	// Bounds checking
	if( $interval_length < 0 ) $interval_length = 1;
	if( $interval_length > 50 ) $interval_length = 21;
}

$date_text = array( 0 => 'days', 1 => 'weeks', 2 => 'months', 3 => 'years' );
$interval_string = $to_date . ' -' . $interval_length . ' ' . $date_text[$interval_measure];

// Compute the "from date"
$from_date = date( 'Y-m-d', strtotime( $interval_string ) );

// There is the appearance of one extra day on every chart...
$from_date = date( 'Y-m-d', strtotime( "$from_date + 1 day" ) );

// Set default cycle display to none
$show_heat_cycles = (isset($_GET['chart_daily_showHeat']) && ($_GET['chart_daily_showHeat'] == 'false')) ? 0 : 1;
$show_cool_cycles = (isset($_GET['chart_daily_showCool']) && ($_GET['chart_daily_showCool'] == 'false')) ? 0 : 1;
$show_fan_cycles  = (isset($_GET['chart_daily_showFan'])  && ($_GET['chart_daily_showFan']  == 'false')) ? 0 : 1;
// Set default for displaying set point temp to "off"
$show_setpoint    = (isset($_GET['chart_daily_setpoint']) && ($_GET['chart_daily_setpoint']  == 'false')) ? 0 : 1;


// OK, now that we have a bounding range of dates, build an array of all the dates in the range
$check_date = $from_date;
$days = array( $check_date );
$dayCount = 1;
while( $check_date != $to_date )
{
	$check_date = date( 'Y-m-d', strtotime( '+1 day', strtotime( $check_date ) ) );
	array_push( $days, $check_date );
	$dayCount++;
}

/**
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

$sqlOne =
"SELECT CONCAT( ?, ' ', b.time ) AS date,
				IFNULL(a.indoor_temp, 'VOID') as indoor_temp,
				IFNULL(a.outdoor_temp, 'VOID') as outdoor_temp
 FROM {$dbConfig['table_prefix']}time_index b
 LEFT JOIN {$dbConfig['table_prefix']}temperatures a
 ON a.date = CONCAT( ?, ' ', b.time ) AND a.tstat_uuid = ? ";
$minutes = '30';

/** QQQ
if( $dayCount == 1 )
{
	$sqlOne .=
	"UNION
	SELECT ? AS date,
	IFNULL(a.indoor_temp, 'VOID') as indoor_temp,
	IFNULL(a.outdoor_temp, 'VOID') as outdoor_temp
	FROM thermo2__time_index b
	LEFT JOIN thermo2__temperatures a
	ON a.date = ? AND a.tstat_uuid = ?";
}
*/

if( $dayCount >= 70 )
{	// Reduce data set if there are more than 70 days.
	$sqlOne .= "WHERE SUBSTR( b.time, 3, 3 ) != ':30' ";
	$minutes = '60';	// Repeated setting is redundant, but it's better to keep this text change with the SQL change.
}
$queryOne = $pdo->prepare( $sqlOne );

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
	//echo "<br>Normal low for this month is $chart_y_min.";
	//echo "<br>Normal high for this month is $chart_y_max.";
	//echo "<br>The SQL<br>$sqlOne";
	//echo '<table class="thermo_chart"><th class="thermo_chart">Date</th><th class="thermo_chart">Indoor Temp</th><th class="thermo_chart">Outdoor Temp</th>';
	echo '<table class="thermo_table"><th>Date</th>';
	if( $source == 1 || $source == 2 )
	{	// Indoor or both
		echo '<th>Indoor Temp</th>';
	}
	if( $source == 0 || $source == 2 )
	{	// Outdoor or both
		echo '<th>Outdoor Temp</th>';
	}
}

$dates = '';
$very_first = true;

$saved_string = VOID;	// Used to store the current X-axis' label until we tell pChart about it

$log->logInfo( "draw_daily.php: sqlOne is ($sqlOne)" );	// Soooo wierd, this log line writes AFTER the completion log entry.
foreach( $days as $show_date )
{
	$dates .= $show_date . '   ';

/** QQQ
	if( $dayCount > 1 )
	{
*/
		$queryOne->execute( array( $show_date, $show_date, $uuid ) );
//$log->logInfo( "draw_daily.php: Executing sqlOne with ($show_date, $show_date, $uuid)" );
/** QQQ
	}
	else
	{	// I think I may only need the extra data point for one day charting, but I may always need it?
		$oneMoreRow = strftime( '%Y-%m-%d 00:00:00', strtotime( "$show_date +1 days" ) );
		$queryOne->execute( array( $show_date, $show_date, $uuid, $oneMoreRow, $oneMoreRow, $uuid ) );
//$log->logInfo( "draw_daily.php: Executing sqlOne with ($show_date, $show_date, $uuid, , '2014-02-06 00:00:00', '2014-02-06 00:00:00', $uuid) adding $oneMoreRow" );
	}
*/
	$counter = 0;
	$first_row = true;
	while( $row = $queryOne->fetch( PDO::FETCH_ASSOC ) )
	{	/**
			* Chart of things that work for X-axis labels (work in progress to have optimal spacing)
			* days  divisor
			*  1		 $dayCount
			*  6		 $dayCount
			*  7		 6
			*  8		 6
			*  9		 8
			* 10		 8
			* 11		12 (date and noon)
			* 16		12
			* 17		24 (date only)
			* 31		24
			* 32		each week start date
			* 70 Change to every hour SELECT instead of every half hour SELECT (cuts data points in half!)
			* The charting software borks if the internal rendering time limit of 30 seconds is hit.
			* On my server, these limit happen around
			* ~75 days of every half-hour
			* ~80 days of hours
			* This crash is VERY is dependant upon server load...
			*/
		if( $dayCount > 13 ) $labelDivisor = 24;
		else if( $dayCount > 10 ) $labelDivisor = 12;
		else if( $dayCount >  8 ) $labelDivisor =  8;
		else if( $dayCount >  6 ) $labelDivisor =  6;
		else $labelDivisor = $dayCount;

//$log->logInfo( "draw_daily.php: Here is the data point to prove we got it {$row['date']}");	// So why isn't it showing up in the chart?

		if( ! $table_flag )
		{	// Only set X-Axis labels if we're displaying a chart
			if( $very_first )
			{	// Always show the first one - regardless of settings
				if( $dayCount < 6 )
				{	// Show time if we have only a few days.
					$saved_string = substr( $row['date'], 11, 5 );
				}
				else
				{	// Show date if we have a lot.
					$saved_string = substr( $row[ 'date' ], 5, 5 );
				}
					$MyData->addPoints( $saved_string, 'Labels' );
//$log->logInfo( "draw_daily.php: adding to AAA Label " . substr( $row['date'], 11, 5 ) );
			}
			else
			{	/**
					* This seems pretty ugly, but pChart highlights a hash mark on the X axis whenever it finds the next point
					* in the abscissa array as being different than the previous one.  Using VOID for the value for those hash marks
					* you don't want to highlight (or get a grid line for) doesn't work since VOID is a valid value.  So you'd
					* get one more highlighted hash mark and a grid line just after the one you really wanted (the date or time) -
					* although it wouldn't actually SHOW anything because the value was "VOID".
					*
					* So, instead, for every hash mark that you don't want to highlight (or get a grid line for) just set it
					* to be the same as the previous hash mark's value.
					* -- Lerrissirrel
					*/

				if( $dayCount <= 28 )
				{	// 13, 3 = minutes with colon (:MM), 11, 2 = two digit hour (HH)
					if( ( substr( $row['date'], 13, 3 ) == ':00' ) && ( (substr( $row['date'], 11, 2 ) % $labelDivisor) == 0 ) )
					{	// Only show axis every -interval- hours
						if( substr( $row['date'], 11, 2 ) == '00' ) // This is '00:mm' (and we already know that 'mm' is '00')
						{	// At midnight show the new date in MM-DD format
							// (How to add emphasis to distinguish from time stamps?)
							$saved_string = substr($row['date'], 5, 5);
						}
						else
						{	// Otherwise show the hour in HH:MM format
							$saved_string = substr($row['date'], 11, 5);
						}
// Something in here is adding too many extra (some extra are OK like the :30 ones in a one day chart) hash marks to the x-axis line.
					}
				}
				else
				{	// All other intervals...
					if( date_format( date_create( $row[ 'date' ]), 'N' ) == 7 )
					{ // Show the date only for the first day of each week in mm-dd format
						$saved_string = substr($row['date'], 5, 5);
					}
				}

				/** We may, or may not, have changed $saved_string, but if we didn't change it is is because we didn't
					* want to show a value for a particular point on the X axis - pChart detects that same value
					* and doesn't display anything (as opposed to VOID which is different than the previous value).
					* -- Lerrissirrel
					*/
				$MyData->addPoints( $saved_string, 'Labels' );
//$log->logInfo( "draw_daily.php: adding to CCC Label " . $saved_string );
			}

			if( $source == 1 || $source == 2 )
			{	// Indoor or both
				$MyData->addPoints( ($row['indoor_temp'] == 'VOID' ? VOID : $row['indoor_temp']), 'Indoor' );
//$log->logInfo( "draw_daily.php: Here is the data point to prove we got it {$row['date']} and {$row['indoor_temp']}");
			}
			if( $source == 0 || $source == 2 )
			{	// Outdoor or both
				$MyData->addPoints( ($row['outdoor_temp'] == 'VOID' ? VOID : $row['outdoor_temp']), 'Outdoor' );
			}
			if( $show_setpoint == 1 )
			{	// Add a VOID point so we can get a legend for the Setpoint overlay
				$MyData->addPoints( VOID, 'Setpoint');
			}
		}
		else
		{
			//echo '<tr><td>'.$row['date'].'</td><td>'.($row['indoor_temp'] == 'VOID' ? '&nbsp;' : $row['indoor_temp']).'</td><td>'.($row['outdoor_temp'] == 'VOID' ? '&nbsp;' : $row['outdoor_temp']).'</td></tr>';
			echo '<tr><td>'.$row['date'].'</td>';
			if( $source == 1 || $source == 2 )
			{	// Indoor or both
				echo '<td>'.($row['indoor_temp'] == 'VOID' ? '&nbsp;' : $row['indoor_temp']).'</td>';
			}
			if( $source == 0 || $source == 2 )
			{	// Outdoor or both
				echo '<td>'.($row['outdoor_temp'] == 'VOID' ? '&nbsp;' : $row['outdoor_temp']).'</td>';
			}
			echo '</tr>';
		}
		$very_first = false;

		/**
		  * Expand chart boundaries to contain data that exceeds the default boundaries
		  * 'VOID' values test poorly in inequality against numeric values so us 50 when the data is bad.
		  * Increment or decrement by ten to keep the chart boundaries pretty
			*/
		if( $source == 1 || $source == 2 )
		{	// Indoor or both
			while( ($row['indoor_temp'] == 'VOID' ? 50 : $row['indoor_temp']) < $chart_y_min + $chartPaddingLimit ) $chart_y_min -= $chartPaddingSpace;
			while( ($row['indoor_temp'] == 'VOID' ? 50 : $row['indoor_temp']) > $chart_y_max - $chartPaddingLimit ) $chart_y_max += $chartPaddingSpace;
		}
		if( $source == 0 || $source == 2 )
		{	// Outdoor or both
			while( ($row['outdoor_temp'] == 'VOID' ? 50 : $row['outdoor_temp']) < $chart_y_min + $chartPaddingLimit ) $chart_y_min -= $chartPaddingSpace;
			while( ($row['outdoor_temp'] == 'VOID' ? 50 : $row['outdoor_temp']) > $chart_y_max - $chartPaddingLimit ) $chart_y_max += $chartPaddingSpace;
		}
  }
}

if( ! $table_flag )
{	// Only set X-Axis labels if we're displaying a chart
	// Cram one more label on the very end to restore that last 30 minutes on the chart x-axis.
	$MyData->addPoints( $saved_string, 'Labels' );
}

// For a $show_date of '2012-07-10' get the start and end bounding datetimes
$start_date = strftime( '%Y-%m-%d 00:00:00', strtotime($from_date));	// "2012-07-10 00:00:00";
$end_date = strftime( '%Y-%m-%d 23:59:59', strtotime($to_date));			// "2012-07-10 23:59:59";

if( ($show_heat_cycles + $show_cool_cycles + $show_fan_cycles) > 0 )
{
  /**
		* This SQL should include cycles that started on the previous night or ended on the
		*  following morning for any given date.
		*
		* Ought to graphically differentiate those open ended cycles somehow?
		*/
  $sqlTwo =
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
echo "<br>sql is $sqlTwo";
echo "<br>start_date is $start_date";
echo "<br>end_date is $end_date";
echo "<br>uuid is $uuid";
*/
  $queryTwo = $pdo->prepare($sqlTwo);
  $result = $queryTwo->execute(array( $start_date, $start_date, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $uuid ) );

//$log->logInfo( "draw_daily.php: Executing sqlTwo ($sqlTwo) for values $start_date, $start_date, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $uuid" );

	$sqlThree = "SELECT heat_status
					,DATEDIFF( start_date_heat, ? ) AS start_day_heat
					,DATE_FORMAT( start_date_heat, '%k' ) AS start_hour_heat
					,TRIM(LEADING '0' FROM DATE_FORMAT( start_date_heat, '%i' ) ) AS start_minute_heat

					,cool_status
					,DATEDIFF( start_date_cool, ? ) AS start_day_cool
					,DATE_FORMAT( start_date_cool, '%k' ) AS start_hour_cool
					,TRIM(LEADING '0' FROM DATE_FORMAT( start_date_cool, '%i' ) ) AS start_minute_cool

					,fan_status
					,DATEDIFF( start_date_fan, ? ) AS start_day_fan
					,DATE_FORMAT( start_date_fan, '%k' ) AS start_hour_fan
					,TRIM(LEADING '0' FROM DATE_FORMAT( start_date_fan, '%i' ) ) AS start_minute_fan

					,DATEDIFF( date, ? ) AS end_day
					,DATE_FORMAT( date, '%k' ) AS end_hour
					,TRIM( LEADING '0' FROM DATE_FORMAT( date, '%i' ) ) AS end_minute

					FROM {$dbConfig['table_prefix']}hvac_status
					WHERE tstat_uuid = ?";

  $queryThree = $pdo->prepare($sqlThree);
  $result = $queryThree->execute(array( $from_date, $from_date, $from_date, $from_date, $uuid ) );

//$log->logInfo( "draw_daily.php: Executing sqlThree ($sqlThree) for values $from_date, $from_date, $from_date, $from_date, $uuid" );

}

if( $show_setpoint == 1 )
{
	$sqlFour =
	"SELECT set_point, switch_time
	 FROM {$dbConfig['table_prefix']}setpoints
	 WHERE id = ?
		AND switch_time BETWEEN ? AND ?
	 UNION ALL
	 SELECT set_point, switch_time
	 FROM (
		SELECT *
		FROM {$dbConfig['table_prefix']}setpoints
		WHERE switch_time < ?
		ORDER BY switch_time DESC
		LIMIT 1
		) AS one_before_start
	 ORDER BY switch_time ASC";

  $queryFour = $pdo->prepare($sqlFour);
  $result = $queryFour->execute(array( $id, $start_date, $end_date, $start_date ) );
//$log->logInfo( "draw_daily.php: Executing sqlFour ($sqlFour) for values $id, $start_date, $end_date, $start_date" );
	while( $row = $queryFour->fetch( PDO::FETCH_ASSOC ) )
	{
		$queryFourData[] = $row;
		while( $row['set_point'] < $chart_y_min + $chartPaddingLimit ) $chart_y_min -= $chartPaddingSpace;
		while( $row['set_point'] > $chart_y_max - $chartPaddingLimit ) $chart_y_max += $chartPaddingSpace;
	}
}

if( $table_flag )
{	// If we're showing the data in a chart, we're done now.  Wrap up the table tag and press the eject button.
	echo '</table>';
	//echo "<br>Adjusted low for this month is $chart_y_min.";
	//echo "<br>Adjusted high for this month is $chart_y_max.";
	echo "Showing data every $minutes minutes for $dayCount days from $from_date to $to_date.";
	return;
}

// Attach the data series to the axis (by ordinal)
$MyData->setSerieOnAxis( 'Indoor', 0 );
$MyData->setSerieOnAxis( 'Outdoor', 0 );
$MyData->setSerieOnAxis( 'Setpoint', 0 );

// Set line style, color, and alpha blending level
$MyData->setSerieTicks( 'Indoor', 0 );  // 0 is a solid line
$serieSettings = array( 'R' => 50, 'G' => 150, 'B' => 80, 'Alpha' => 100 );
$MyData->setPalette( 'Indoor', $serieSettings );

$MyData->setSerieTicks( 'Outdoor', 2 ); // n is length in pixels of dashes in line
$serieSettings = array( 'R' => 150, 'G' => 50, 'B' => 80, 'Alpha' => 100 );
$MyData->setPalette( 'Outdoor', $serieSettings );

$MyData->setSerieTicks( 'Setpoint', 0 ); // n is length in pixels of dashes in line
$serieSettings = array( 'R' => 100, 'G' => 100, 'B' => 255, 'Alpha' => 60 );
$MyData->setPalette( 'Setpoint', $serieSettings );

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
$myPicture->Antialias = FALSE;								// Turn OFF Antialiasing (it draws faster)

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
$myPicture->setFontProperties( array( 'FontName' => 'Copperplate_Gothic_Light.ttf', 'FontSize' => 10 ) );

// Write picture and chart titles
$myPicture->drawText( 10, 14, $picTitle, array( 'R' => 255, 'G' => 255, 'B' => 255) );
$myPicture->drawText( 60, 55, $chartTitle, array( 'FontSize' => 12, 'Align' => TEXT_ALIGN_BOTTOMLEFT ) );

// Write the picture timestamp
$myPicture->drawText( 680, 14, 'Last update ' . date( 'Y-m-d H:i' ), array( 'R' => 255, 'G' => 255, 'B' => 255) );

// Define the chart area
$graphAreaStartX = 60;
$graphAreaEndX = 850;
$graphAreaStartY = 60;
$graphAreaEndY = 390;
$myPicture->setGraphArea( $graphAreaStartX, $graphAreaStartY, $graphAreaEndX, $graphAreaEndY );

// Draw the scale
$myPicture->setFontProperties( array( 'FontName' => 'pf_arma_five.ttf', 'FontSize' => 6 ) );
$scaleSettings = array( 'Mode' => SCALE_MODE_MANUAL, 'ManualScale' => $AxisBoundaries, 'GridR' => 200, 'GridG' => 200, 'GridB' => 200, 'LabelingMethod' => LABELING_DIFFERENT, 'DrawSubTicks' => TRUE, 'CycleBackground' => TRUE, 'XMargin' => 0,'YMargin' => 0,'Floating' => TRUE );
$myPicture->drawScale( $scaleSettings );

// Write the chart legend
$myPicture->setFontProperties( array( 'FontName' => 'pf_arma_five.ttf', 'FontSize' => 6 ) );
$myPicture->setShadow( TRUE, array( 'X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10 ) );
$myPicture->drawLegend( 60, 412, array( 'Style' => LEGEND_NOBORDER, 'Mode' => LEGEND_HORIZONTAL ) );
// END of common block


// Draw the chart(s)
//$myPicture->setShadow( TRUE, array( 'X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 40 ) );	// Define shadows under series lines
$myPicture->drawLineChart( array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO ) );
//$myPicture->setShadow( FALSE );		// No more shadows (so they only apply to the lines)


/**
	* After the chart is created, prepare the overlays.  I draw these manually because I can't
	*  find a horizontal 'stacked' bar chart that allows missing pieces in it in pChart.
	*/

$PixelsPerMinute = (($graphAreaEndX - $graphAreaStartX) / 1440) / $dayCount;  // = 0.54861 (for dayCount = 1)
/**
	* Assumptions:
	*  1. The chart X-axis represents 24 hours
	*  2. The graph horizontal area (i.e. graph area) is 790 pixels wide (so each pixel represents 1.82 minutes)
	*
	* Why 0.54861?  (when dayCount is 1)
	* The chart area boundary is defined as 790px wide (850px - 60px start position).
	* 1440 is the number of minutes in a day.
	* $dayCount is the number of days that will be charted
	* ((850 - 60) / 1440) / 1
	* 790px / 1440 pixels/day = .54861 pixels per minute
	*
	* The $dayCount factor was added to account for the number of days in the display.  Too many days and the display will be really ugly
	*
	* Cycle data is represented by drawing objects, so it has to be AFTER the creation of $myPicture
	*/

// Positions are relative to the charting area rather than graphing area
$LeftMargin = $graphAreaStartX;

if( ($show_heat_cycles + $show_cool_cycles + $show_fan_cycles) > 0 )
{	// The SQL has already been executed.  Now just draw it.

  // The rounded corners look so much better, but the run times are so relatively short that the rounds seldom appear.
  $HeatGradientSettings = array( 'StartR' => 150, 'StartG' =>  50, 'StartB' =>  80, 'Alpha' => 65, 'Levels' => 90, 'BorderR' =>  140, 'BorderG' =>  40, 'BorderB' =>  70 );
  $CoolGradientSettings = array( 'StartR' =>  50, 'StartG' => 150, 'StartB' => 180, 'Alpha' => 65, 'Levels' => 90, 'BorderR' =>   40, 'BorderG' => 140, 'BorderB' => 170 );
  $FanGradientSettings  = array( 'StartR' => 235, 'StartG' => 235, 'StartB' =>   0, 'Alpha' => 65, 'Levels' => 90, 'BorderR' =>  255, 'BorderG' => 255, 'BorderB' =>   0 );
  $RectHeight = 20;
  $HeatRectRow = 150;
  $CoolRectRow = 175;
  $FanRectRow = 200;

//echo "<table border='1'>";
  while( $row = $queryTwo->fetch( PDO::FETCH_ASSOC ) )
  {
/*
echo '<tr>';
foreach($row as $cell)echo "<td>$cell</td>";
echo '</tr>';
*/
    // 'YYYY-MM-DD HH:mm:00'  There are NO seconds in these data points.
    $cycle_start = $LeftMargin + ((($row['start_day'] * 1440) + ($row['start_hour'] * 60) + $row['start_minute'] ) * $PixelsPerMinute);
    $cycle_end   = $LeftMargin + ((($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute);

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

	// Now draw boxes for a presently running heat/cool/fan sessions.

  while( $row = $queryThree->fetch( PDO::FETCH_ASSOC ) )
  {	// Should be only one row!
  	if( $row['heat_status'] == 1 && $show_heat_cycles == 1 )
  	{	// If the AC is on now AND we want to draw it
			$cycle_start = $LeftMargin + (($row['start_day_heat'] * 1440) + ($row['start_hour_heat'] * 60) + $row['start_minute_heat'] ) * $PixelsPerMinute;
			$cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

      $myPicture->drawGradientArea( $cycle_start, $HeatRectRow, $cycle_end, $HeatRectRow + $RectHeight, DIRECTION_HORIZONTAL, $HeatGradientSettings );
  	}
  	if( $row['cool_status'] == 1 && $show_cool_cycles == 1 )
  	{	// If the AC is on now AND we want to draw it
			$cycle_start = $LeftMargin + (($row['start_day_cool'] * 1440) + ($row['start_hour_cool'] * 60) + $row['start_minute_cool'] ) * $PixelsPerMinute;
			$cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

			$myPicture->drawGradientArea( $cycle_start, $CoolRectRow, $cycle_end, $CoolRectRow + $RectHeight, DIRECTION_HORIZONTAL, $CoolGradientSettings );
  	}
  	if( $row['fan_status'] == 1 && $show_fan_cycles == 1 )
  	{	// If the AC is on now AND we want to draw it
			$cycle_start = $LeftMargin + (($row['start_day_fan'] * 1440) + ($row['start_hour_fan'] * 60) + $row['start_minute_fan'] ) * $PixelsPerMinute;
			$cycle_end   = $LeftMargin + (($row['end_day']   * 1440) + ($row['end_hour']   * 60) + $row['end_minute'] )   * $PixelsPerMinute;

      $myPicture->drawGradientArea( $cycle_start, $FanRectRow, $cycle_end, $FanRectRow + $RectHeight, DIRECTION_HORIZONTAL, $FanGradientSettings );
  	}
	}
}

if( $show_setpoint == 1 )
{
	// The graph area is 330 vertical pixels.  Set the y scale range against the graph area
	$setpoint_scale = ($chart_y_max - $chart_y_min) / ($graphAreaEndY - $graphAreaStartY);

	$first_row = 1;
//	while( $row = $queryFour->fetch( PDO::FETCH_ASSOC ) )
	foreach( $queryFourData as $row )
	{
		/** The query returns one row prior to the current date range so that
			* we can determine the setpoint leading into the first drawn day
			*** This falls apart currently if there is not a setpoint for the prior day
			*** but there should always be one unless the database table is just starting
			*** to become populated with data.
			*/
		if( $first_row == 1 )
		{
			$first_row = 0;
			$prev_setpoint = $row['set_point'];
			$prev_switch_time = date_create( $from_date );
			$start_px = $LeftMargin;
			continue;
		}

		// Compute the switch time delta
		$setpoint = $row['set_point'];
		$switch_time = date_create($row['switch_time']);
		$interval = $prev_switch_time->diff($switch_time);

		// Compute the next end pixel based on the switch time difference
		$end_px = $start_px + ( $interval->format('%h') * 60 + $interval->format('%i') ) * $PixelsPerMinute;

    // Draw the horizontal setpoint line
    $myPicture->drawLine( $start_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, $end_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, array( 'R' => 100, 'G' => 100, 'B' => 255, 'Ticks' => 2, 'Alpha' => 60 ) );
		// Draw the vertical setpoint change line
		$myPicture->drawLine( $end_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, $end_px, $graphAreaEndY-($setpoint-$chart_y_min)/$setpoint_scale, array( 'R' => 100, 'G' => 100, 'B' => 255, 'Ticks' => 2, 'Alpha' => 60 ) );

		// Reset parameters for next iteration
		$prev_switch_time = $switch_time;
		$prev_setpoint = $setpoint;
		$start_px = $end_px;
  }

	/** Draw the last setpoint horizontal line but first determine how far it needs to be drawn
		* If the last switch_time and the current time are the same day then only draw up to the
		* current time.  Otherwise, draw to the 23:59:59 marker.
		*/
	$now = date_create();
	$interval = $prev_switch_time->diff($now);
	if( $prev_switch_time->format('Y-m-d') == $now->format('Y-m-d') )
	{
		$end_px = $start_px + ( $interval->format('%h') * 60 + $interval->format('%i') ) * $PixelsPerMinute;
		$myPicture->drawLine( $start_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, $end_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, array( "R" => 100, "G" => 100, "B" => 255, "Ticks" =>2, "Alpha" => 60 ) );
	}
	else
	{
		$end_px = $graphAreaEndX;
		$myPicture->drawLine( $start_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, $end_px, $graphAreaEndY-($prev_setpoint-$chart_y_min)/$setpoint_scale, array( "R" => 100, "G" => 100, "B" => 255, "Ticks" => 2, "Alpha" => 60 ) );
	}
}

$myPicture->autoOutput( 'images/daily_chart.png' );
$log->logInfo( 'draw_daily.php: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>