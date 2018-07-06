<?php
require_once( 'common.php' );

/**
  * User registration/login loosely based on http://www.codingcage.com/2015/04/php-login-and-registration-script-with.html
  */
class USER{
  private $database;
  private $conn;
  public  $thermostats = array();
  public  $MTUs = array();
  private $uname;
  private $session;

  public function __construct( $uname = null, $session = null ){
    global $util;
$util::logDebug( 'user.php: construct 0' );
    $this->database = new Database();
    $db = $this->database->dbConnection();
    $this->conn = $db;
//$util::logDebug( 'user.php: construct 1' );

    if( $uname == null && $session == null && $this->isLoggedIn() ){
      $this->uname = $_SESSION[ 'user_name' ];
      $this->session = $_SESSION[ 'user_session' ];
    }
    else if( $uname != null && $session != null && $this->hasSession( $uname, $session ) ){
      $this->uname = $uname;
      $this->session = $session;
    }

//$util::logDebug( 'user.php: construct 2' );
    if( $this->uname != null && $this->session != null ){
      // Put user Thermostat(s) in the list.
//$util::logDebug( 'user.php: construct 2a' );
//      $sql = "SELECT stat.thermostat_id, stat.tstat_uuid, stat.model, stat.fw_version, stat.wlan_fw_version, stat.ip, stat.name, stat.description, stat.user_id, stat.zip_code, stat.timezone FROM {$this->database->table_prefix}thermostats AS stat, {$this->database->table_prefix}users AS user WHERE user.user_id = stat.user_id AND user.user_name = :uname";
      $sql = "
SELECT stat.thermostat_id
      ,stat.tstat_uuid
      ,stat.model
      ,stat.fw_version
      ,stat.wlan_fw_version
      ,stat.ip
      ,stat.name
      ,stat.description
      ,stat.user_id
      ,loc.location_string
      ,loc.timezone
FROM {$this->database->table_prefix}users AS user
    ,{$this->database->table_prefix}thermostats AS stat
    ,{$this->database->table_prefix}locations AS loc
WHERE user.user_name = :uname
  AND stat.user_id = user.user_id
  AND loc.location_id = stat.location_id";

      $stmt = $this->conn->prepare( $sql );
//$util::logDebug( 'user.php: construct 2b' );
      $stmt->bindParam( ':uname', $this->uname );
//$util::logDebug( 'user.php: construct 2c' );
      $stmt->execute();
//$util::logDebug( 'user.php: construct 2d' );

      $this->thermostats = $stmt->fetchAll( PDO::FETCH_ASSOC );
//$util::logDebug( 'user.php: construct 2e' );

      // Put user TED 5000(s) in the list.
//      $sql2 = "SELECT mtu.user_id, mtu.mtu_id, mtu.ip, mtu.name, mtu.description FROM elec__meters AS mtu, {$this->database->table_prefix}users AS user WHERE user.user_id = mtu.user_id AND user.user_name = :uname";
      $sql2 = "
SELECT mtu.mtu_id
      ,mtu.mtu
      ,mtu.ip
      ,mtu.name
      ,mtu.description
      ,loc.location_string
      ,loc.timezone
FROM {$this->database->table_prefix}users AS user
    ,{$this->database->table_prefix}meters AS mtu
    ,{$this->database->table_prefix}locations AS loc
WHERE user.user_name = :uname
  AND mtu.user_id = user.user_id
  AND loc.location_id = mtu.location_id";
      $stmt = $this->conn->prepare( $sql2 );
      $stmt->bindParam( ':uname', $this->uname );
      $stmt->execute();

      $this->TED5000_Gateways = $stmt->fetchAll( PDO::FETCH_ASSOC );
    }
$util::logDebug( 'user.php: construct 3' );
  }

  public function getName(){
    return $this->uname;
  }

  public function getSession(){
    return $this->session;
  }

  public function isSiteAdmin(){
    global $util;
    if( $this->uname === $util::$adminUsername ){
      return true;
    }
    return false;
  }

  // QQQ Why is this called runSQL?  It doesn't run the SQL....
  // QQQ Probably ought to delete this.
  public function runQuery( $sql ){
    $stmt = $this->conn->prepare( $sql );
    return $stmt;
  }

  public function register( $uname, $umail, $upass ){
    global $util;
    try{
      $new_password = password_hash( $upass, PASSWORD_DEFAULT );

      $stmt = $this->conn->prepare( "INSERT INTO {$this->database->table_prefix}users( user_name, email, pass, validation_key ) VALUES( :uname, :umail, :upass, :key )" );

// QQQ Need to generate a real key before used in production environment.
$key = '12345';
      $stmt->bindParam( ':uname', $uname );
      $stmt->bindParam( ':umail', $umail );
      $stmt->bindParam( ':upass', $new_password );
      $stmt->bindParam( ':key', $key );

      $stmt->execute();

      $message = "Please click this <a href='URL TO SERVER/thermo2/confirm.php?key=$key'>link</a> to confirm your registation.";
      $util::send_mail( $umail, $message, 'User ID registration email' );

//      return $stmt;
      return true;
    }
    catch( Exception $e ){
      echo $e->getMessage();
    }
  }

  // Add a new table: user_login_history
  // Add a trigger to users table to insert a row with username, date, ip_address on each login
  public function doLogin( $uname, $upass ){
    global $util;
    try{
      $stmt = $this->conn->prepare( "SELECT user_id, user_name, email, pass FROM {$this->database->table_prefix}users WHERE user_name = :uname" );
      $stmt->bindParam( ':uname', $uname );
      $stmt->execute();

      $userRow = $stmt->fetch( PDO::FETCH_ASSOC );
      if( $stmt->rowCount() == 1 ){
        if( password_verify( $upass, $userRow[ 'pass' ] ) ){
          // Not trying to be unique for security, just trying to get something sort of random
          $user_session = substr( MD5( microtime() ), 0, 128);
          $_SESSION[ 'user_session' ] = $user_session;
          $_SESSION[ 'user_name' ] = $uname;
          $ip = $util::get_ip_address();

          $stmt = $this->conn->prepare( "UPDATE {$this->database->table_prefix}users SET last_login = now(), sessionid = :user_session, session_expiry = now() + INTERVAL 1 DAY, ip_address = :ip WHERE user_name = :uname" );
          $stmt->bindParam( ':user_session', $user_session );
          $stmt->bindParam( ':ip', inet_pton( $ip ) );
          $stmt->bindParam( ':uname', $uname );
          $stmt->execute();

          return true;

        }
        else{
          return false;
        }
      }
    }
    catch( Exception $e ){
      echo $e->getMessage();
    }
  }

  // Get the session ID (for when this is called from the front end)
  public function isLoggedIn(){
    // Maybe also add a test for IP address so that if that changes, then it requires a new login.
    if( isset( $_SESSION[ 'user_session' ] ) && isset( $_SESSION[ 'user_name' ] ) ){
      $user_session = $_SESSION[ 'user_session' ];
      $uname = $_SESSION[ 'user_name' ];
    }
    else{
      $user_session = $this->getSession();
      $uname = $this->getName();
    }

    if( $this->hasSession( $uname, $user_session ) ){
      return true;
    }
    return false;
  }

  public function getSessionInfo( $uname, $user_session ){
    // Need to add some logging in here.
    try{
      $stmt = $this->conn->prepare( "SELECT session_expiry FROM {$this->database->table_prefix}users WHERE sessionid = :user_session AND user_name = :uname" );
      $stmt->bindParam( ':user_session', $user_session );
      $stmt->bindParam( ':uname', $uname );
      $stmt->execute();

      return $stmt->fetchColumn();
    }
    catch( Exception $e ){
      // The way the SQL is written this really ought not to happen.
      // Do nothing, but fall through to false.
    }
    return false;
  }


  // For any given user name and session ID this tests for validity. (Can be used in back end calls too)
  public function hasSession( $uname, $user_session ){
    // Need to add some logging in here.
    try{
      $stmt = $this->conn->prepare( "SELECT count(*) FROM {$this->database->table_prefix}users WHERE sessionid = :user_session AND user_name = :uname AND session_expiry >= now()" );
      $stmt->bindParam( ':user_session', $user_session );
      $stmt->bindParam( ':uname', $uname );
      $stmt->execute();

      if( $stmt->fetchColumn() == 1 ){
        // one row, one column in results.  Value should be 0 or 1.
        return true;
      }
    }
    catch( Exception $e ){
      // The way the SQL is written this really ought not to happen.
      // Do nothing, but fall through to false.
    }
    return false;
  }

  public function doLogout(){
    global $util;
$util::logDebug( 'USER->doLogout' );
    session_destroy();
    unset( $_SESSION[ 'user_session' ] );

    // Taken from: http://php.net/manual/en/function.session-destroy.php
    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if( ini_get( 'session.use_cookies' ) ){
        $params = session_get_cookie_params();
        setcookie( session_name(), '', time() - 42000,
            $params[ 'path'], $params[ 'domain' ],
            $params[ 'secure'], $params[ 'httponly' ]
        );
    }

    return true;
  }

}
?>