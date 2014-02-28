<?php
$start_time = microtime(true);
require_once( 'common_chart.php' );

$sql = "SELECT DATE_FORMAT(date, '%mM') AS theMonthNumber,
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
//ROUND(SUM(heat_runtime)/60,0) AS heatHours,
//ROUND(SUM(cool_runtime)/60,0) AS coolHours
//AND   DATE_FORMAT(date, '%Y') = '2013'
// DATE_FORMAT(date, '%Y-%m')

$query = $pdo->prepare( $sql );
$query->execute( array( $uuid ) );


// Create and populate the pData object
$MyData = new pData();

while( $row = $query->fetch( PDO::FETCH_ASSOC ) )
{

	$MyData->addPoints( $row[ 'theMonth' ], 'Labels' );

//	$MyData->addPoints( $row[ 'heatHours12' ], 'Heating12' );
	$MyData->addPoints( $row[ 'coolHours12' ], 'Cooling 2012' );

//	$MyData->addPoints( $row[ 'heatHours13' ], 'Heating13' );
	$MyData->addPoints( $row[ 'coolHours13' ], 'Cooling 2013' );

//	$MyData->addPoints( $row[ 'heatHours14' ], 'Heating14' );
	$MyData->addPoints( $row[ 'coolHours14' ], 'Cooling 2014' );
}

// Attach the data series to the axis (by ordinal)
//$MyData->setSerieOnAxis( 'Heating12', 0 );
//$MyData->setSerieOnAxis( 'Cooling13', 0 );


// Set names for Y-axis labels
$MyData->setAxisName( 0, 'Hours' );

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
$myPicture->drawBarChart( array( 'DisplayValues' => FALSE, 'DisplayColor' => DISPLAY_AUTO ) );


// Render the picture
$myPicture->autoOutput( 'images/compare_chart.png' );
$log->logInfo( 'draw_compare.php: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>