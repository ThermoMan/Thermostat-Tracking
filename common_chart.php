<?php

class Config_Exception extends Exception{
}

require_once( 'common.php' );
require_once( 'user.php' );

// Modify the font path for the GD library - because graphic renders are lame?
// Must use absolute and not relative paths.
//$pChart_fontpath = realpath( '../common/php/pChart/fonts' );  // pCharts default font library
//$my_fontpath = realpath( '../common/fonts' );                 // font path for this application
//putenv( 'GDFONTPATH='.$pChart_fontpath.PATH_SEPARATOR.$my_fontpath );

//require_once( 'pChart/class/pData.class.php' );
//require_once( 'pChart/class/pDraw.class.php' );
//require_once( 'pChart/class/pImage.class.php' );


// QQQ Move these to the view side of the code - do not need it here in the model side.
/**
  * Set normal temperature range so the charts always scale the same way
  *
  * Hi/Low temps for each month (January to December).  Based on normal hi/low temps.  These temps are in
  * degrees F and are manually updated for now.
  * Add +/- 10 when displaying to try to keep the lines in the chart from banging into the edges of the area.
  *
  * Ideas for future:
  *  + Connect normal high/low to location.
  *  + Store in the DB along with the locations.
  *  + Keep track of F/C in the database and convert to preference when displaying
  *  + Always store in degrees F and convert to degrees C for display since each degree F is a smaller
  *     increment than each degree C.
  *
  *                  Jan Feb Mar Apr May  Jun  Jul  Aug Sep Oct Nov Dec
  */
$normalHighs = array( 60, 60, 70, 80, 90, 100, 100, 100, 90, 70, 70, 60 );
$normalLows  = array( 30, 50, 40, 50, 60,  70,  70,  70, 60, 50, 40, 30 );

/*
// Amount of space to add to y scale to keep lines inside the chart
$chartPaddingLimit = 5;   // When to trigger the addition of space (pixels)
$chartPaddingSpace = 10;  // Amount of space to add (pixels)
*/

/*
// Replaces chart with anti-hacking graphic (usually when web user has used a mal-formed date string)
function bobby_tables(){
  $filename = './images/exploits_of_a_mom.png';
  $handle = fopen( $filename, 'r' );
  $contents = fread( $handle, filesize( $filename ) );
  fclose( $handle );
  echo $contents;
}
*/

// QQQ This should be a UTIL function
// QQQ The call to bobby_tables is not relevant in the model code any more.
function validate_date( $some_date ){
  $date_pattern = "/[2]{1}[0]{1}[0-9]{2}-[0-9]{2}-[0-9]{2}/";
  if( !preg_match( $date_pattern, $some_date ) || strlen( $some_date ) != 10 ){
    // I want it to be EXACTLY YYYY-MM-DD
    bobby_tables();
    return false;
  }
  return true;
}

$uname = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : null;         // Set uname to chosen user name (or null if not chosen)
$session = (isset($_REQUEST['session'])) ? $_REQUEST['session'] : null; // Set session to chosen session id (or null if not chosen)
$user = new USER( $uname, $session );

if( $user == null || ! $user->hasSession( $uname, $session ) ){
  $util::logError( 'common_chart: Back end call with bad user details ' );
//  bobby_tables();
  return false;
}

// Common code that should run for EVERY CHART page follows here
$thermostat_id = (isset($_REQUEST['thermostat_id'])) ? $_REQUEST['thermostat_id'] : null;    // Set id to chosen thermostat (or null if not chosen)
if( $thermostat_id == null ){
  // If the thermostat to display was not chosen, choose one
  $thermostat = array_pop( $thermostats );
  if( is_array( $thermostat ) && isset( $thermostat['thermostat_id'] ) ){
    $thermostat_id = $thermostat['thermostat_id'];
//    if( $thermostat_id != $thermostat['thermostat_id'] ){
//      $util::logError( 'common_chart: Requested thermostat_id does not match user thermostat_id - I smell a hacker!' );
//      throw new Config_Exception( 'common_chart: error 1.  check logs.' );
//    }
  }
}

if( $thermostat_id == null ){
  // If there still is not one chosen then abort
  $util::logError( 'common_chart: Thermostat ID was NULL!' );
  // Need to redirect output to some image showing user there was an error and suggesting to read the logs.
  throw new Config_Exception( 'common_chart: error 3.  check logs.' );
}

foreach( $user->thermostats as $thermostatRec ){
  if( $thermostatRec['thermostat_id'] == $thermostat_id ){
    // Having now chosen a thermostat to display, gather information about it.
    // This is true only if the requested thermostat ID is one of the thermostats owned by the present user.
//    $uuid = $thermostatRec['tstat_uuid'];
    $statName = $thermostatRec['name'];
  }
}

// QQQ This only owkrs if every thermostat has a name.  Must require the user to give it a name.
// QQQ Names per user ought to be unique
if( $statName == null ){
  // If the chosen thermostat is not known to the system then abort
  $util::logError( 'common_chart: Requested thermostat ID was not found! (by ID)' );
  // Need to redirect output to some image showing user there was an error and suggesting to read the logs.
  throw new Config_Exception( 'common_chart: error 5.  check logs.' );
}

?>