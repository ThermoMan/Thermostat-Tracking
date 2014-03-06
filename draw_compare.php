<?php
$start_time = microtime(true);
require_once( 'common_chart.php' );

$hddBaseF = 65;
$cddBaseF = 65;

$hddBaseC = ( ( $hddBaseF - 32 ) * 5 ) / 9;
$cddBaseC = ( ( $cddBaseF - 32 ) * 5 ) / 9;

if( $config['units'] = 'F' )
{
	$hddBase = $hddBaseF;
	$cddBase = $cddBaseF;
}
else
{
	$hddBase = $hddBaseC;
	$cddBase = $cddBaseC;
}


//ROUND(SUM(heat_runtime)/60,0) AS heatHours,
//ROUND(SUM(cool_runtime)/60,0) AS coolHours
//AND   DATE_FORMAT(date, '%Y') = '2013'
// DATE_FORMAT(date, '%Y-%m')

$sql = "SELECT DATE_FORMAT(date, '%m') AS theMonthNumber,
DATE_FORMAT(date, '%M') AS theMonth,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', heat_runtime, 0))/60,0) AS heatHours12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', heat_runtime, 0))/60,0) AS heatHours13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', heat_runtime, 0))/60,0) AS heatHours14,

ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', cool_runtime, 0))/60,0) AS coolHours12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', cool_runtime, 0))/60,0) AS coolHours13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', cool_runtime, 0))/60,0) AS coolHours14

FROM {$dbConfig['table_prefix']}run_times
WHERE tstat_uuid = ?
GROUP BY 1
ORDER BY 1";
//$queryRunTimes = $pdo->prepare( $sql );
//$queryRunTimes->execute( array( $uuid ) );

// Perhaps count the rows and if fewer than 24 then give message like "not enough data, be patient grasshopper"?

$sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS date,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', avgTempHDD, 0)),1) AS hdd12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', avgTempHDD, 0)),1) AS hdd13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', avgTempHDD, 0)),1) AS hdd14,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2012', avgTempCDD, 0)),1) AS cdd12,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2013', avgTempCDD, 0)),1) AS cdd13,
ROUND(SUM(IF( DATE_FORMAT(date, '%Y') = '2014', avgTempCDD, 0)),1) AS cdd14
FROM (
SELECT date_format(date, '%Y-%m-%d') AS date,
IF( AVG(outdoor_temp) <= $hddBase, $hddBase - AVG(outdoor_temp), 0 ) AS avgTempHDD,
IF( AVG(outdoor_temp) >= $cddBase, AVG(outdoor_temp) - $cddBase, 0 ) AS avgTempCDD
FROM {$dbConfig['table_prefix']}run_times
WHERE tstat_uuid = ?
GROUP BY DATE_FORMAT(date, '%Y-%m-%d') ) derivedTemperatures
GROUP BY 1
";
//$queryDegreeDays = $pdo->prepare( $sql );
//$queryDegreeDays->execute( array( $uuid ) );

/**
	* To compute degree days....
	*
	* For heating degree days
	*  1. Determine average temperature for the whole day
	*  2. For each average in excess of 65 degrees F, count only those degrees above 65
	*  3. Sum those degrees for the month
	* For cooling degree days
	*  1. Determine average temperature for the whole day
	*  2. For each average below 65 degrees F, count only those degrees below 65
	*  3. Sum those degrees for the month
	*
	* To use these computed numbers.
	*  Compare the the heating degree days for a given month on two successive years to see which is
	*  the hotter month.
	*  Examine the HVAC runtime in hours for that same month on those two years.
	*  If all things are equal, the run time for a given number of degrees will remain the same.
	*  If your run time begins to increase then you may need to tune/refill/maintain your unit.
	*
	*  This is also very handy if one year your electricity bill is much higher, you can see if the
	*  Summer was very much hotter.
	*
	*  65 is the magic number in the US.  Your number may vary.  Your number WILL vary.
	*  http://www.degreedays.net/introduction
	*  See particularly the section about determining your own numbers
	*
	*/

$giantSQL = "SELECT
	t1.theMonthNumber,
	t1.theMonth AS theMonth,
	heatHours12, heatHours13, heatHours14,
	coolHours12, coolHours13, coolHours14
	hdd12, hdd13, hdd14,
	cdd12, cdd13, cdd14
FROM
	(
		SELECT
			DATE_FORMAT(rt.date, '%m') AS theMonthNumber,
			DATE_FORMAT(rt.date, '%M') AS theMonth,
			ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2012', rt.heat_runtime, 0))/60,0) AS heatHours12,
			ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2013', rt.heat_runtime, 0))/60,0) AS heatHours13,
			ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2014', rt.heat_runtime, 0))/60,0) AS heatHours14,
			ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2012', rt.cool_runtime, 0))/60,0) AS coolHours12,
			ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2013', rt.cool_runtime, 0))/60,0) AS coolHours13,
			ROUND(SUM(IF( DATE_FORMAT(rt.date, '%Y') = '2014', rt.cool_runtime, 0))/60,0) AS coolHours14
		FROM {$dbConfig['table_prefix']}run_times rt
		WHERE tstat_uuid = ?
		GROUP BY 1, 2
	) t1,
	(
		SELECT
			DATE_FORMAT(dt.date, '%m') AS theMonthNumber,
			DATE_FORMAT(dt.date, '%M') AS theMonth,
			ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2012', dt.avgTempHDD, 0)),1) AS hdd12,
			ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2013', dt.avgTempHDD, 0)),1) AS hdd13,
			ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2014', dt.avgTempHDD, 0)),1) AS hdd14,
			ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2012', dt.avgTempCDD, 0)),1) AS cdd12,
			ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2013', dt.avgTempCDD, 0)),1) AS cdd13,
			ROUND(SUM(IF( DATE_FORMAT(dt.date, '%Y') = '2014', dt.avgTempCDD, 0)),1) AS cdd14
		FROM
		(
			SELECT
				date_format(t.date, '%Y-%m-%d') AS date,
				IF( AVG(t.outdoor_temp) <= {$hddBase}, {$hddBase} - AVG(t.outdoor_temp), 0 ) AS avgTempHDD,
				IF( AVG(t.outdoor_temp) >= {$cddBase}, AVG(t.outdoor_temp) - {$cddBase}, 0 ) AS avgTempCDD
			FROM {$dbConfig['table_prefix']}temperatures t
			WHERE tstat_uuid = ?
			GROUP BY DATE_FORMAT(date, '%Y-%m-%d')
		) dt
	GROUP BY 1, 2
	) t2
WHERE
    t1.theMonthNumber = t2.theMonthNumber
AND t1.theMonth = t2.theMonth
";

$queryGiant = $pdo->prepare( $giantSQL );
$queryGiant->execute( array( $uuid, $uuid ) );


// Create and populate the pData object
$MyData = new pData();

while( $row = $queryGiant->fetch( PDO::FETCH_ASSOC ) )
{

	$MyData->addPoints( $row[ 'theMonth' ], 'Labels' );

//	$MyData->addPoints( $row[ 'heatHours12' ], 'Heating12' );
//	$MyData->addPoints( $row[ 'hdd12' ], 'Heating Degrees 2012' );
	$MyData->addPoints( $row[ 'coolHours12' ], 'Cooling 2012' );
	$MyData->addPoints( $row[ 'cdd12' ], 'Cooling Degrees 2012' );

//	$MyData->addPoints( $row[ 'heatHours13' ], 'Heating13' );
//	$MyData->addPoints( $row[ 'hdd13' ], 'Heating Degrees 2013' );
	$MyData->addPoints( $row[ 'coolHours13' ], 'Cooling 2013' );
	$MyData->addPoints( $row[ 'cdd13' ], 'Cooling Degrees 2013' );

//	$MyData->addPoints( $row[ 'heatHours14' ], 'Heating14' );
//	$MyData->addPoints( $row[ 'hdd14' ], 'Heating Degrees 2014' );
//	$MyData->addPoints( $row[ 'coolHours14' ], 'Cooling 2014' );
//	$MyData->addPoints( $row[ 'cdd14' ], 'Cooling Degrees 2014' );
}

// Attach the data series to the axis (by ordinal)
//$MyData->setSerieOnAxis( 'Heating 2012', 0 );
//$MyData->setSerieOnAxis( 'Heating 2013', 0 );
//$MyData->setSerieOnAxis( 'Heating 2014', 0 );
//$MyData->setSerieOnAxis( 'Heating Degrees 2012', 1 );
//$MyData->setSerieOnAxis( 'Heating Degrees 2013', 1 );
//$MyData->setSerieOnAxis( 'Heating Degrees 2014', 1 );
$MyData->setSerieOnAxis( 'Cooling 2012', 0 );
$MyData->setSerieOnAxis( 'Cooling 2013', 0 );
//$MyData->setSerieOnAxis( 'Cooling 2014', 0 );
$MyData->setSerieOnAxis( 'Cooling Degrees 2012', 1 );
$MyData->setSerieOnAxis( 'Cooling Degrees 2013', 1 );
//$MyData->setSerieOnAxis( 'Cooling Degrees 2014', 1 );


// Set names for Y-axis labels
$MyData->setAxisName( 0, 'Hours' );
$MyData->setAxisName( 1, 'Degrees' );
$MyData->setAxisPosition( 1, AXIS_POSITION_RIGHT );

// Set names for X-axis labels
$MyData->setSerieDescription( 'Labels', 'Months' );
$MyData->setAbscissa( 'Labels' );

/**
	* Set variables for going into common block
	*/
$picTitle = 'Show the comparison run times';
$chartTitle = 'HVAC run times for each month in the record';

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
//$scaleSettings = array( 'Mode' => SCALE_MODE_MANUAL, 'ManualScale' => $AxisBoundaries, 'GridR' => 200, 'GridG' => 200, 'GridB' => 200, 'LabelingMethod' => LABELING_DIFFERENT, 'DrawSubTicks' => TRUE, 'CycleBackground' => TRUE, 'YMargin' => 0,'Floating' => TRUE );
$scaleSettings = array( 'GridR' => 200, 'GridG' => 200, 'GridB' => 200, 'LabelingMethod' => LABELING_DIFFERENT, 'DrawSubTicks' => TRUE, 'CycleBackground' => TRUE, 'YMargin' => 0,'Floating' => TRUE );
$myPicture->drawScale( $scaleSettings );

// Write the chart legend - convert all legends to left aligned because there is no auto right alignment
$myPicture->setFontProperties( array( 'FontName' => 'pf_arma_five.ttf', 'FontSize' => 6 ) );
$myPicture->setShadow( TRUE, array( 'X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 10 ) );
$myPicture->drawLegend( 60, 412, array( 'Style' => LEGEND_NOBORDER, 'Mode' => LEGEND_HORIZONTAL ) );
// END of common block


// Draw the chart
//$myPicture->drawLineChart( array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO ) );
//$myPicture->drawBarChart( array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO ) );

$Settings = array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO, 'Gradient' => 1, 'AroundZero' => TRUE, 'Interleave' => 2  );
$MyData->setSerieDrawable( 'Cooling 2012', TRUE );
$MyData->setSerieDrawable( 'Cooling 2013', TRUE );
$MyData->setSerieDrawable( 'Cooling Degrees 2012', FALSE );
$MyData->setSerieDrawable( 'Cooling Degrees 2013', FALSE );
$myPicture->drawBarChart( 'Cooling 2012', 'Cooling 2013', $Settings );

$Settings = array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO );
$MyData->setSerieDrawable( 'Cooling 2012', FALSE );
$MyData->setSerieDrawable( 'Cooling 2013', FALSE );
$MyData->setSerieDrawable( 'Cooling Degrees 2012', TRUE );
$MyData->setSerieDrawable( 'Cooling Degrees 2013', TRUE );
$myPicture->drawLineChart( 'Cooling Degrees 2012', 'Cooling Degrees 2013', $Settings );

// Render the picture
$myPicture->autoOutput( 'images/compare_chart.png' );
$log->logInfo( 'draw_compare.php: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>