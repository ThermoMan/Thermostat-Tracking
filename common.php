<?php
/** Location for code that is common to all pages in the project.
  *
  * Need to separate three concepts.
  *   Code common to ALL functions (DB connection, logging, user class defintion etc...)
  *   Code common to UI pages (session validation and setting up globals)
  *   Code common to back-end processes (prevent direct call, verify user sessions, etc...)
  *
  */

// This next thing ought to be in some utility package.

/** For libraries that are not uniquely part of the application code base, there is a common
  * location on the webserver so that all projects can use one instance of the library.

+-- www
    +-- common
    |   +-- css
    |   +-- html
    |   +-- js
    |   +-- php
    |       +-- pChart -> pChart2.1.4
    |       +-- pChart2.1.4
    |       +-- mailer
    |
    +-- MY_WEB_APP_HERE
    |
    +-- thermo2
        +-- backup
        +-- images
        +-- install
        +-- lib
        |   +-- fonts
        |   +-- tabs
        +-- locks
        +-- logs
        +-- resources
        +-- scripts
        +-- sessions

  * In order to be able to reference those files without hard coded path names, the PHP include path needs to know about the relative location of
  *  those libraries.
  */
function add_include_path( $path ){
  foreach( func_get_args() AS $path ){
    if( !file_exists( $path ) OR ( file_exists( $path ) && filetype( $path ) !== 'dir' ) ){
      trigger_error( "Include path '{$path}' not exists", E_USER_WARNING );
      continue;
    }

    $paths = explode( PATH_SEPARATOR, get_include_path() );

    if( array_search( $path, $paths ) === false ){
      array_push( $paths, $path );
    }

    set_include_path( implode( PATH_SEPARATOR, $paths ) );
  }
}
// rootDir variable no longer exists!   add_include_path( $rootDir . '../../common/php/' );
add_include_path( '../common/php/' );

require_once( 'config.php' );

// Do I need a separate common file for the command line utils or just lump it all in this one? (for now this one)
require_once( 'mailer/class.phpmailer.php' );   // Used for sending emails

require_once( 'lib/t_lib.php' );                // Used for reaading the 3M-50 Thermostats
require_once( 'lib/e_lib.php' );                // Used for reading the TED 5000
// Need lib for LIFX smart bulbs
// Need lib for SolarCity panel reading
require_once( 'lib/ExternalWeather.php' );      // Used for reading outside temperature data from one of several sources.
require_once( 'KLogger.php' );                  // Used for writing log file (original location https://github.com/katzgrau/KLogger )


require_once( 'simple_html_dom.php' );          // Used for ???? ( original location https://sourceforge.net/projects/simplehtmldom/files/ )

$rootDir = dirname(__FILE__) . '/';
$logDir =  $rootDir . 'logs/';

// Create a utility class with these "global variables".  Make it a singleton
define( 'LOG_LEVEL', array( 'DEBUG' => 0, 'INFO' => 1, 'WARN' => 2, 'ERROR' => 3 ) );

// Create a utility class with these "global variables".  Make it a singleton
class UTIX{
  private static $instance;

  // Consider using const type
  public static $lockFile;
  public static $timezone;
  public static $rootDir;
  private static $logDir;
  private static $sessionDir;
  public static $adminUsername;
  private static $log;
  private static $logLevel;

  // protected/private to prevent use
  protected function __construct(){}
  private function __clone(){}
  private function __wakeup(){}

  public static function getInstance(){
    if( null === static::$instance ){
      static::$instance = new static();
    }
    self::$rootDir = dirname(__FILE__) . '/';

// All these directories, the app should create them if they do not exist
    self::$logDir = self::$rootDir . 'logs/';
    self::$sessionDir = self::$rootDir . 'sessions/';   // This one requires the app to have a sessions sub directory.
//    self::$lockFile = '/tmp/thermo.lock';
// Maybe only need lockDir and not lockFile since what file name is in use may depend on what asset is being queried.
// Will need some sort of cleaup routine to periodically clean out old locks that are obviously invalid (probably anything over ten minutes old is bad)
    self::$lockFile = self::$rootDir . 'locks/thermo.lock';   // Ought/Needs to include username in file name
//    self::$lockDir = self::$rootDir . 'locks/';  // This one requires the app to have a locks sub directory.

//    self::$timezone = 'America/Chicago';
//    self::$adminUsername = 'test7';
    self::$timezone = TIME_ZONE;
    self::$adminUsername = SITE_ADMIN;          // This establishes a site administrator ID

    self::$logLevel = 0;  // Default to allow log of everything

    return static::$instance;
  }

/**  These levels are based on what KLogger has
  * Need to set levels to control verbosity.  Here are the typical levels of verbosity I've seen. Not sure where to
  * draw the line on what is needed.
  *
  * FATAL
  * ERROR
  * WARN
  * INFO
  * DEBUG
  * TRACE
  *
**/
  private static function logIt( $message ){
    $logFile = self::$logDir . 'log_' . date( 'Y-m-d' ) . '.txt';
    $fh = fopen( $logFile, 'a' );
//    fwrite( $fh, date( 'Y-m-d G:i:s.u' ) . $message . "\n" );
    fwrite( $fh, (new DateTime( 'now' ))->format( 'Y-m-d G:i:s.u' ) . $message . "\n" );
    fclose( $fh );
  }
  public static function logDebug( $message ){
    if( self::$logLevel > 0 ) return;
    self::logIt( ' - DEBUG --> ' . $message );
  }
  public static function logInfo( $message ){
    if( self::$logLevel > 1 ) return;
    self::logIt( ' -- INFO --> ' . $message );
  }
  public static function logWarn( $message ){
    if( self::$logLevel > 2 ) return;
    self::logIt( ' -- WARN --> ' . $message );
  }
  public static function logError( $message ){
    // Always log errors.
    self::logIt( ' - ERROR --> ' . $message );
  }
  public static function setLogLevel( $level ){
self::logInfo( "setLogLevel with level = $level" );

//    if( in_array( $level, array( 'DEBUG', 'INFO', 'WARN', 'ERROR' ) ) ){
    if( in_array( $level, LOG_LEVEL) ){
//      self::$logLevel = LOG_LEVEL[ $level ];
      self::$logLevel = 5;
     }
  }

  public static function secondsToDate( $seconds ){
    $dtT = new DateTime( "@$seconds" );
    return $dtT->format( 'Y-m-d H:i:s' );
  }

  public static function secondsToTime( $seconds ){
    $dtF = new DateTime( '@0' );
    $dtT = new DateTime( "@$seconds" );
    return $dtF->diff( $dtT )->format( '%a days %h hours %i minutes %s seconds' );
  }

  /**  Verify that a given date is in a valid format
    *  Allow user to specify format or use a default that is a full datetime
    *   Y is 4 digit year
    *   m is two digit month with leading 0
    *   d is two digit day with leading 0
    *   H is 24 hour time with leading 0
    *   i is two digit minute with leading 0
    *   s is two digit second with leading 0
    *
    * Reference http://php.net/manual/en/datetime.createfromformat.php
    */
  public static function isValidDate( $date, $format = 'Y-m-d H:i:s' ){
    $f = DateTime::createFromFormat( $format, $date );
    $valid = DateTime::getLastErrors();
    return( $valid['warning_count'] == 0 and $valid['error_count'] == 0 );
  }

  /**
    * Ensures an ip address is valid.
    * IP address code based on http://blackbe.lt/advanced-method-to-obtain-the-client-ip-in-php
    */
  private static function validate_ip( $ip ){
    /**
      * Possible flag values according to http://www.w3schools.com/php/filter_validate_ip.asp
      * FILTER_VALIDATE_IP        - filter validates an IP address.
      * FILTER_FLAG_IPV4          - The value must be a valid IPv4 address
      * FILTER_FLAG_IPV6          - The value must be a valid IPv6 address
      * FILTER_FLAG_NO_PRIV_RANGE - The value must not be within a private range
      * FILTER_FLAG_NO_RES_RANGE  - The value must not be within a reserved range
      *
      * Here I am disallowing IPv6 and reserved IP ranges.  I am allowing local addresses in case you're running this at home!
      */
    if( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE ) === false ){
      return false;
    }
    return true;
  }

  /**
    * Get user IP address
    * IP address code based on http://blackbe.lt/advanced-method-to-obtain-the-client-ip-in-php
    *
    * Need to add some kind of handler for local (non-web base) calls.
    */
  public static function get_ip_address(){
    $ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
    foreach( $ip_keys as $key ){
      if( array_key_exists( $key, $_SERVER ) === true ){
        foreach( explode( ',', $_SERVER[ $key ] ) as $ip ){
          $ip = trim( $ip );  // trim for safety measures
          if( self::validate_ip( $ip ) ){
            // return the first enountered valid IP address found
            return $ip;
          }
        }
      }
    }
    return isset( $_SERVER[ 'REMOTE_ADDR' ] ) ? $_SERVER[ 'REMOTE_ADDR' ] : false;
  }

  // Need to update mailer lib.  I have the 2009 version!
  // I got the old one from here http://www.codingcage.com/2015/09/login-registration-email-verification-forgot-password-php.html
  public static function send_mail( $email, $message, $subject ){
//    require_once( 'lib/mailer/class.phpmailer.php' );   // Is it better to include globally or just in here when it's needed?
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPDebug  = 0;
//    $mail->Debugoutput = 'html';
    $mail->SMTPSecure = 'tls';
    $mail->SMTPAuth   = true;

    $mail->Host       = EMAIL_HOST;
    $mail->Port       = EMAIL_PORT;
    $mail->Username   = EMAIL_USER;
    $mail->Password   = EMAIL_PASS;
    $mail->SetFrom( EMAIL_USER, EMAIL_NAME );
//    $mail->AddReplyTo( 'me@medomain.com', 'My Name' );

    $mail->AddAddress( $email );
    $mail->Subject = $subject;
    $mail->MsgHTML( $message );

    $mail->Send();
/*
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} else {
    echo "Message sent!";
}
*/
  }

  /**
    * Starts a session with a specific timeout and a specific GC probability.
    * @param int $timeout The number of seconds until it should time out.
    *        default to seven days
    * @param int $probability The probablity, in int percentage, that the garbage
    *        collection routine will be triggered right now.
    *        default to 100%
    * @param strint $cookie_domain The domain path for the cookie.
    *        default to root
    */
  public static function session_start( $timeout = null, $probability = 100, $cookie_domain = '' ){
    if( is_null( $timeout ) ){
      $timeout = (60 * 60 * 24 * SESSION_MAX_LENGTH);
    }
    ini_set( 'session.gc_maxlifetime', $timeout );      // Set the max lifetime
    ini_set( 'session.cookie_lifetime', $timeout );     // Set the session cookie to timout
    ini_set( 'session.gc_probability', $probability );  // Set the chance to trigger the garbage collection.
    ini_set( 'session.gc_divisor', 100 );               // Should always be 100
    session_name( SESSION_NAME );


// QQQ This comment may not be true!
    /**
      * Change the save path. Sessions stored in the same path all share the same lifetime; the lowest lifetime will be
      * used for all. Therefore, for this to work, the session must be stored in a directory where only sessions sharing
      * it's lifetime are. Best to just dynamically create on.
      */
    ini_set( 'session.save_path', realpath( self::$sessionDir ) );

    session_start();

    /**
      * Renew the time left until this session times out.  If you skip this, the session will time out based
      * on the time when it was created, rather than when it was last used.
      */
    if( isset( $_COOKIE[ session_name() ] ) ){
      setcookie( session_name(), $_COOKIE[ session_name() ], time() + $timeout, $cookie_domain );
      setcookie( 'session_ending', time() + $timeout, time() + $timeout, $cookie_domain );
    }
  }

  // The functions below are specific to my app.  The ones above are generic.

  public static function checkThermostat( $p_user ){
    if( ! isset( $p_user ) ){
      return false;
    }
    if( count( $p_user->thermostats ) > 0 ){
      return true;
    }
    else{
      echo '<p>You have no thermostats defined.  If you did, you\'d see the present status of your system(s).</p>';
      echo '<p>Visit the <a href="profile.php">Profile</a> page to set up a thermostat!</p>';
      require_once( 'standard_page_foot.php' );
      return false;
    }
  }

}
$util = UTIX::getInstance();
$util::logInfo( 'common.php: UTIX class instantiated as util for ' . $util::$timezone );


/**
  * Lockfile - set to a path that exists on your system
  *  the thermostat id from the database will be appended to this filename
  * This keeps thermo_update_temps and thermo_update_status from running at the same time.  If they both hit the
  * thermostat at the same time, the thermostat could be overloaded and become unresponsive for 20-30 minutes
  * until the wifi module resets.
  */
//$lockFile = '/tmp/thermo.lock'; // Need username in file name
$lockFile = $util->lockFile;

/**
  * Really need to have timezone for each location so that all data is stored in the 'local' zone.
  * At present this is used to force the servers (php procesor, web server, DB server) to think they
  *  are in the same timezone as the location of all the thermostats.
  *
  * If you are using a system that does not understand timezones (for example Synology NAS) or you are
  *  using it in a 100% local environment then uses SYSTEM.
  * $timezone = 'SYSTEM';
  */
//$timezone = 'America/Chicago';
// Set timezone for all PHP functions
date_default_timezone_set( $util::$timezone );


// THIS OUGHT TO BE IN THAT EXTERNAL WEATHER LIB!!!!!
/** Weather - External Temperature
  * weatherunderground requires an api key - register for a free one
  * weatherbug requires an api key - register for a free one. !!temps in F only!!
  * noaa requires an api_loc - see http://w1.weather.gov/xml/current_obs/ for the correct xml url for your location
  */
$weatherConfig = array(                       // CSV list of possible values for variable
   'useWeather'  => TRUE                      // TRUE, FALSE (Flag to use external temperature for all stats)
  ,'useForecast' => TRUE                      // TRUE, FALSE (Flag to show forecast on dashboard))
  // type is on of: weatherunderground, noaa, weatherbug
  ,'type'        => WEATHER_TYPE
  ,'units'       => 'F'                       // F, C
  ,'api_key'     => WEATHER_API_KEY
  // api_loc needs work - it is relied upon by noaa code, but the others pretty much ignore it.
  ,'api_loc'     => WEATHER_API_LOC
  // URL - User Specific
  // See  http://www.wunderground.com/weather/api/d/docs
  //      http://weather.weatherbug.com/desktop-weather/api-documents.html
  //      http://w1.weather.gov/xml/current_obs/
);
// THIS OUGHT TO BE IN THAT EXTERNAL WEATHER LIB!!!!!

// Database connection parameters
class Database{
  private $host = DB_HOST;
  private $port = DB_PORT;
  private $db_name = DB_NAME;
  private $username = DB_USER;
  private $password = DB_PASS;
  public  $table_prefix = DB_PREFIX;

  public $conn;

  public function dbConnection(){
//    global $timezone; // Pass the timezone in instead of using this global!
    global $util;
$util::logInfo( "common.php: dbConnection - 0" );
    $this->conn = null;
    try{
$util::logInfo( "dbConnection - 1 -->{$this->host}<-- -->{$this->port}<-- -->{$this->db_name}<--" );
//      $this->conn = new PDO( "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}", $this->username, $this->password );
      $this->conn = new PDO( "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=UTF8;", $this->username, $this->password );

$util::logInfo( "dbConnection - 2" );

      $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$util::logInfo( "dbConnection - 3 for " . $util::$timezone );
      $this->conn->exec( "SET time_zone = '" . $util::$timezone . "'" );  // Set timezone for all MySQL functions
$util::logInfo( "dbConnection - OK" );
    }
    catch( Exception $e ){
      $util::logError( 'Database->dbConnection Connection error ' . $e->getMessage() );
      return null;
    }
    return $this->conn;
  }
}

/*
http://www.wikihow.com/Create-a-Secure-Session-Management-System-in-PHP-and-MySQL
CREATE TABLE IF NOT EXISTS thermo2__users(
   user_id         int(10)    NOT NULL AUTO_INCREMENT
  ,is_validated    enum('false','true') NOT NULL DEFAULT 'false'
  ,user_name       varchar(60)   NOT NULL
  ,email           varchar(60)   NOT NULL
  ,pass            varchar(255)  NOT NULL
  ,sessionid       char(128)     NOT NULL DEFAULT ''
  ,session_expiry  datetime      NULL DEFAULT '0'
  ,last_login      datetime      NULL
  ,ip_address      varbinary(16) NOT NULL
  ,validation_key  varchar(60)   DEFAULT NULL
  ,created_on      datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP
  ,last_updated_on datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP
  ,PRIMARY KEY( user_id )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
// Should the session mgmt be in a separate table so the user can have logins on different browsers/platforms?

ALTER TABLE `thermo2__thermostats` ADD `user_id`  TINYINT(3)  NOT NULL AFTER `description`;
ALTER TABLE `thermo2__thermostats` ADD `zip_code` VARCHAR(9)  NOT NULL AFTER `user_id`;
ALTER TABLE `thermo2__thermostats` ADD `timezone` VARCHAR(60) NOT NULL AFTER `zip_code`;
*/

// Display variables

/**
  * The following ought to be stored in the DB with a config page
  *
  * But before it can be remotely configurable there has to be an ID/PW system for some tabs
  * I guess a tab would have to contain an iframe and the iframe has a page that checks permissions.
  */
$send_end_of_day_email = 'Y';     // 'Y' or 'N'
$send_eod_email_time = '0800';    // format is HHMM (24-hour) as text string
$send_eod_email_address = 'thestalwart1-tstat@yahoo.com';
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



// Establish connection to log file
$log = KLogger::instance( $logDir );
?>