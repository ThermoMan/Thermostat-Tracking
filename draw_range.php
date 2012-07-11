<?php
REQUIRE "lib/t_lib.php";
REQUIRE "config.php";

// pChart library inclusions
include("lib/pChart2.1.3/class/pData.class.php");
include("lib/pChart2.1.3/class/pDraw.class.php");
include("lib/pChart2.1.3/class/pImage.class.php");

function bobby_tables()
{
    $url_stuff = parse_url( "http://imgs.xkcd.com/comics/exploits_of_a_mom.png" );
    $port = isset($url_stuff['port']) ? $url_stuff['port'] : 80;

    $fp = fsockopen($url_stuff['host'], $port);

    $query  = 'GET ' . $url_stuff['path'] . " HTTP/1.0\n";
    $query .= 'Host: ' . $url_stuff['host'];
    $query .= "\n\n";

    fwrite($fp, $query);

    while ($tmp = fread($fp, 1024))
    {
        $buffer .= $tmp;
    }

    preg_match('/Content-Length: ([0-9]+)/', $buffer, $parts);
    print substr($buffer, - $parts[1]);
}

//session_start();

$link = mysql_connect( $host, $user, $pass );
if( !$link )
{
  die( "Could not connect: <no error message provided to hackers>"  );
}
mysql_select_db( $db, $link ) or die( "cannot select DB" );            // Really should log this?

// Create and populate the pData object
$MyData = new pData();

$dates = "";
foreach( array("2012-07-05", "2012-07-06") as $show_date )
{
  $dates .= $show_date . "   ";
  $sql = "SELECT b.foo as date, IFNULL(a.indoor_temp, 'VOID') as indoor_temp, IFNULL(a.outdoor_temp, 'VOID') as outdoor_temp "
  . "FROM "
  . "( "
  . "SELECT CONCAT( '" . $show_date . " ', '00:00' ) AS foo "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '00:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '01:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '01:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '02:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '02:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '03:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '03:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '04:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '04:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '05:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '05:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '06:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '06:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '07:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '07:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '08:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '08:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '09:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '09:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '10:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '10:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '11:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '11:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '12:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '12:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '13:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '13:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '14:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '14:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '15:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '15:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '16:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '16:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '17:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '17:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '18:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '18:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '19:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '19:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '20:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '20:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '21:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '21:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '22:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '22:30' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '23:00' ) "
  . "UNION "
  . "SELECT CONCAT( '" . $show_date . " ', '23:30' ) "
  . ") b "
  . "LEFT JOIN  "
  . "thermo.temperatures a "
  . "ON b.foo = DATE_FORMAT( a.date, '%Y-%m-%d %H:%i' );";

  $result = mysql_query( $sql );

  $counter = 0;
  while( $row = mysql_fetch_array( $result ) )
  {
    if( substr( $row['date'], 13, 3 ) == ":00" )
    {
      if( is_int( $counter / 4 ) )
      {
        $MyData->addPoints( substr( $row['date'], 11, 5 ), "Labels" );
        $counter = 0;
      }
			else
			{
	      $MyData->addPoints( VOID, "Labels" );
			}
      $counter++;
    }
    else
    {
      $MyData->addPoints( VOID, "Labels" );
    }

    if( $row['indoor_temp'] != 'VOID' )
    {
      $MyData->addPoints( $row['indoor_temp'], "Indoor" );
      $MyData->addPoints( $row['outdoor_temp'], "Outdoor" );
    }
    else
    {
      $MyData->addPoints( VOID, "Indoor" );
      $MyData->addPoints( VOID, "Outdoor" );
    }
  }
}

mysql_close( $link );

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

// Write the picture title
//$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Silkscreen.ttf", "FontSize" => 8 ) );
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 8 ) );
$myPicture->drawText( 10, 13, "Show temperatures for ".$dates, array( "R" => 255, "G" => 255, "B" => 255) );

// Write the chart title
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/Copperplate_Gothic_Light.ttf", "FontSize" => 8 ) );
$myPicture->drawText( 250, 55, "Temperature every 30 minutes across the span of dates", array( "FontSize" => 12, "Align" => TEXT_ALIGN_BOTTOMMIDDLE ) );

// Define the chart area
$myPicture->setGraphArea( 60, 60, 850, 390 );

// Draw the scale
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 6 ) );
$scaleSettings = array( "XMargin" => 10, "YMargin" => 10, "Floating" => FALSE, "GridR" => 200, "GridG" => 200, "GridB" => 200, "DrawSubTicks" => TRUE, "CycleBackground" => TRUE );
$myPicture->drawScale( $scaleSettings );

// Define shadows under series lines
$myPicture->setShadow( TRUE, array( "X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 40 ) );
// Draw the lines
$myPicture->drawLineChart( array( "DisplayValues" => FALSE, "DisplayColor" => DISPLAY_AUTO ) );
// No more shadows (so they only apply to the lines)
$myPicture->setShadow( FALSE );

// Write the chart legend
$myPicture->setFontProperties( array( "FontName" => "lib/pChart2.1.3/fonts/pf_arma_five.ttf", "FontSize" => 8 ) );
$myPicture->drawLegend( 710, 412, array( "Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL ) );


// Render the picture (choose the best way)
$myPicture->autoOutput();

?>
