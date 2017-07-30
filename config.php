<?php
/** Define app secret variables
  * Cannot not use .htacess file because these values HAVE to be available to the command line parts of the code too.
  */

// Timezone for PHP (and MySQL) to assume as it's own.
define( 'TIME_ZONE', 'America/Los_Angeles' );

define( 'DB_HOST', 'localhost' );
define( 'DB_PORT', '3306' );
define( 'DB_NAME', 'thermo' );
define( 'DB_USER', 'user' );
define( 'DB_PASS', 'password' );
define( 'DB_PREFIX', 'thermo2__' );   // Can also use NULL or empty string
// Prefix to attach to all table/procedure names to make unique in unknown environment.
// Using a double underscore as the end of the prefix enables some UI magic in phpMyAdmin

// Variables to send email
define( 'EMAIL_HOST', 'smtp.gmail.com' );
define( 'EMAIL_PORT', '587' );
define( 'EMAIL_USER', 'gmailuser@gmail.com' );
define( 'EMAIL_PASS', 'gmailpass' );
define( 'EMAIL_NAME', 'Smart Home Info' );

# WEATHER_TYPE is on of: weatherunderground, noaa, weatherbug
# WEATHER_API_LOC may be NULL (but NEVER blank ''), http://w1.weather.gov/xml/current_obs/KUZA.xml
define( 'WEATHER_TYPE', 'weatherunderground' );
define( 'WEATHER_API_KEY', '0000000000000000' );
define( 'WEATHER_API_LOC', NULL );

// Variable to define site super admin
define( 'SITE_ADMIN', 'site_admin' );

// Session config information
define( 'SESSION_MAX_LENGTH', '180' );        // How long can a user stay logged in?  (in days)
define( 'SESSION_NAME', 'SMART_HOME_INFO' );  // Name the session cookie to prevent conflict with other pagfes on the same domain
?>