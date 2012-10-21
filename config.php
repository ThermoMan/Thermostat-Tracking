<?php

/**
  * Lockfile - set to a path that exists on your system
  *  the thermostat id from the database will be appended to this filename
  * This keeps thermo_update_temps and thermo_update_status from running at the same time.  If they both hit the
  * thermostat at the same time, the thermostat could be overloaded and become unresponsive for 20-30 minutes
  * until the wifi module resets.
  */
$lockFile = '/tmp/thermo.lock';

/**
  * Really need to have timezone for each location so that all data is stored in the 'local' zone.
  * At present this is used to force the servers (php procesor, web server, DB server) to think they
  *  are in the same timezone as the location of all the thermostats.
	*
  * If you are using a system that does not understand timezones (for example Synology NAS) or you are
  *  using it in a 100% local environment
  * $timezone = 'SYSTEM';
	*/
$timezone = 'America/Los_Angeles';

// Your ZIP code  (still assuming that all thermostats are in one location.  Multi-location support comes later.
$ZIP = '90210';

/** Weather - External Temperature
  * weatherunderground requires an api key - register for a free one
  * weatherbug requires an api key - register for a free one. !!temps in F only!!
  * noaa requires an api_loc - see http://w1.weather.gov/xml/current_obs/ for the correct xml url for your location
  */
$weatherConfig = array(
    'type'    => 'weatherunderground',     
    // weatherunderground, noaa, weatherbug
    'units'   => 'F',                      
    // F, C
    'api_key' => '0000000000000000',
    // Registered API key
    'api_loc' => '' 
    // blank (uses ZIP)
    // URL - User Specific
    //   See  http://www.wunderground.com/weather/api/d/docs
    //        http://weather.weatherbug.com/desktop-weather/api-documents.html
    //        http://w1.weather.gov/xml/current_obs/

);


// Database connection parameters
$dbConfig = array(
 'dsn'          => 'mysql:host=localhost;port=3306;dbname=thermo',
 'username'     => 'user',
 'password'     => 'password',
 'table_prefix' => 'thermo2__'             // Prefix to attach to all table/procedure names to make unique in unknown environment.
 // DO make this prefix DIFFERENT than you used for version 1 (if you had the old code installed)
);

// Config edit PW
$password = 'admin';



// Display variables
/**
  * Set normal temperature range so the charts always scale the same way
	*
  * Hi/Low temps for each month (January to December).  Based on normal hi/low temps.  These temps are in
  * degrees F and are  manually updated for now.
	* Add +/- 10 when displaying to try to keep the lines in the chart from banging into the edges of the area.
	*
	* Ideas for future:
	*  + Connect normal high/low to location.
	*  + Store in the DB along with the locations.
	*  + Keep track of F/C in the database and convert to preference when displaying
	*  + Always store in degrees F and convert to degrees C for display since each degree F is a smaller
	*     increment than each degree C
	*/
$normalHighs = array( 60, 70, 70, 80, 90, 100, 100, 100, 90, 80, 70, 60 );
$normalLows  = array( 30, 40, 40, 50, 60,  70,  70,  70, 60, 50, 40, 30 );


// advent_light Bedizen calibri Forgotte MankSans GeosansLight
// pf_arma_five verdana Silkscreen Copperplate_Gothic_Light
/*
$displayConfig = array(
  'legendFont' => 'verdana',
  'legendFontSize'  => '8',
  'descriptiveFont' => 'MankSans',
  'descriptiveFontSize' => '11',
  'scaleFont' => 'verdana',
  'scaleFontSize' => '8',
  'titleFont' => 'verdana',
  'titleFontSize' => '14',
  'titleFontColor' => array('R' => 80, 'G' => 80, 'B' => 80),
  'headerFontColor' => array('R' => 255, 'G' => 255, 'B' => 255),
  'backgroundColor' => array('R' => 200, 'G' => 213, 'B' => '190', 'Dash' => 0, 'DashR' => 1, 'DashG' => 0, 'DashB' => 0, 'Alpha' => 100),
  'gradientHead' => array('StartR' => 0, 'StartG' => 0, 'StartB' => 0, 'EndR' => 50, 'EndG' => 50, 'EndB' => 50, 'Alpha' => 80),
  'gradient' => array('StartR' => 219, 'StartG' => 231, 'StartB' => 139, 'EndR' => 1, 'EndG' => 138, 'EndB' => 68, 'Alpha' => 50),
  'borderColor' => array('R' => 0, 'G' => 0, 'B' => 0),
  'indoorColor' => array('R' => 150, 'G' => 50, 'B' => 150, 'Alpha' => 100),
  'outdoorColor' => array('R' => 190, 'G' => 120, 'B' => 80, 'Alpha' => 100),
  'setpointColor' => array('R' => 50, 'G' => 50, 'B' => 190, 'Alpha' => 100),
  'shadow' => array('X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 40)
);
*/



/**
  * The following ought to be stored in the DB with a config page
  *
  * But before it can be remotely configurable there has to be an ID/PW system for some tabs
  * I guess a tab would have to contain an iframe and the iframe has a page that checks permissions.
  */
$send_end_of_day_email = 'Y';     // 'Y' or 'N'
$send_eod_email_time = '0800';    // format is HHMM (24-hour) as text string
$send_eod_email_address = 'your_address@wherever.com';
$send_eod_email_smtp = '';
$send_eod_email_pw = '';
/**
  * Add a check at the end of the one per minute task to see if time now == $send_eod_email_time
  * The better way would be to use Windows Scheduler to create a task to run at the named time
  *  In order to implement that, need to store Windows ID and Password to be able to write the
  *  command line necesary to change the existing schedule.  Those two items should be in this
  *  config file on the theory that the file system is slightly more secure than a DB that is
  *  already available online.  Make sure to use a non-privilaged account!
  */
?>