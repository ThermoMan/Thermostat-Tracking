<?php
$start_time = microtime(true);
require_once( dirname(__FILE__).'/../common.php' );

global $util;

$util::logDebug( 'Start' );

$NOW = getenv( 'NOW' );
//$util::logDebug( "NOW is $NOW" );
//$util::logDebug( "Am I running on command line: " . $util::is_cli() );

// Bandaid to keep things moving
$database = new Database();
$pdo = $database->dbConnection();

/** Get list of "meters"
  *
  * This is really a list of MTUs, so a single install with 4 MTUs would have 4 records here
  *
  * Yes, this means that the domainname/ip address and port number information is duplicated multiple times.
  *
  */
$allMeters = array();
try{
  $sql = "
  SELECT *
    FROM {$database->table_prefix}meters
ORDER BY name asc";
  $stmt = $pdo->prepare( $sql );
  $stmt->execute();
  $allMeters = $stmt->fetchAll( PDO::FETCH_ASSOC );
}
catch( Exception $e ){
  $util::logError( 'FATAL Error getting meter list' );
  die();
}

//$util::logDebug( '1' );

try{
  //$sql = "INSERT INTO {$database->table_prefix}elec_usage( date, watts, rate ) VALUES ( FROM_UNIXTIME(?), ?, ? )";
  $sql = "
  INSERT INTO {$database->table_prefix}meter_data(
     mtu_id
    ,date
    ,watts
    ,volts
  )
  VALUES (
     ?
    ,STR_TO_DATE( ?, '%m/%d/%Y %H:%i:%S' )
    ,?
    ,? )";
//$util::logDebug( 'sql is ' . $sql );
  $queryRunInsert = $pdo->prepare( $sql );
}
catch( Exception $e ){
  $util::logError( 'DB Exception: ' . $e->getMessage() );
  die();
}

//$util::logDebug( '2' );

$ch = curl_init();
curl_setopt( $ch, CURLOPT_USERAGENT, 'A' );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

foreach( $allMeters as $meterRec ){
//$util::logDebug( '3' );
  try{
//$util::logDebug( '4' );
    /** Hard coded for now.
      * Later on this will be a computed number.
      *
      * It should be the difference between the latest recorded date in the system and "now"
      *
      * It might require two contacts with the device as part of this process
      *  1. Determine largest date for the requested MTU
      *  2. Contact the device and find out what time it thinks it is
      *  3. Compute the difference between the two times.  Add one because the minute might be about to roll over right now.
      *  4. Contact the unit to get the data.
      *
      * Here is a better idea.
      *  1. Determine largest date for the requested MTU
      *  2. Assume I know what time it is and compute how many I need.  Add 1 just because.
      *  3. Contact the unit, get the data, and insert into the DB.
      *  4. IF there is a gap between the old date the oldest date in my new list.  Go fill the difference with an extra contact
      *
      * Here is what I actually do.
      *  1. Assume cron is working and it runs every 6 hours.
      *  2. Ask for 7 hours of data and let the DB reject duplicate data.
      */
    $howMany = 60 * 7;  // 6 hours + 1 hour of overlap protect (the DB prevents duplicate data)
//    $howMany = 60 * 13;  // 12 hours + 1 hour of overlap protect (the DB prevents duplicate data)
//    $howMany = 60 * 48;  // Grab ALL the data (to try to recover missing data)

    $commandURL = 'http://' . $meterRec['ip'] . '/history/minutehistory.xml?MTU=' . $meterRec['mtu'] . '&COUNT='. $howMany .'&INDEX=1';
//$util::logDebug( '4.5 ' . $commandURL );

// QQQ All this curl stuff ought to be in the e_lib.php library!
    curl_setopt( $ch, CURLOPT_URL, $commandURL );
    $outputs = curl_exec( $ch );

//$util::logDebug( '5' );
    if( strlen( $outputs ) < 1 ){
//$util::logDebug( '6' );
      $util::logError( 'Did not get a useful return from this meter' );
      $util::logError( "elec_update_meter_read:Trying to send email \n\tsend_eod_email_address : $send_eod_email_address\n\tsubject : $subject\n\tadditional_headers : $additional_headers" );

      $subject = 'No response from TED 5000';
      $body = 'Please check your device for connectivity';
      $util::send_mail( $send_eod_email_address, $body, $subject );

      continue;
    }
//else{
//$util::logDebug( '7' );
//}

//$util::logDebug( "The length of outputs is " . strlen( $outputs ) );

    $replyXML = simplexml_load_string( $outputs );

    $line_num = 0;
    foreach( $replyXML as $dataElement ){
//$util::logDebug( '8' );
      $line_num++;  // line_num is the input line number, not the number of lines accepted into the DB

      try{
//$util::logDebug( '9 with ' . $meterRec['mtu_id'] . ' -- ' . $dataElement->DATE . ' -- ' . $dataElement->POWER . ' -- ' . $dataElement->VOLTAGE / 10 );
        $queryRunInsert->execute( array( $meterRec['mtu_id'], $dataElement->DATE, $dataElement->POWER, $dataElement->VOLTAGE / 10 ) );
        //$text = "<br>$line_num / $dataElement->DATE / $dataElement->POWER watts / ". $dataElement->VOLTAGE / 10 . ' volts';
        //echo $text;
      }
      catch( Exception $e ){
//$util::logDebug( '10' );
        if( $e->getCode() == '1062' || $e->getCode() == '23000' ){
          // I expect to occasionally get some of these.  I want to acknowlege they happened, but they don't bug me.
          $util::logInfo( 'Dupe ignored.' );
          // 1062 was supposed to be duplicate code, but 23000 is what I kept getting
          // 23000 is the sql state. "SQLSTATE[23000]: Integrity constraint violation"
        }
        else{
          // Dump the data into the logfile so I can retrieve it later manually
          $util::logError( 'Some horrid DB error: ' . $e->getMessage() . "\t MTU: ".$meterRec['mtu_id'].', DATE: '.$dataElement->DATE.', POWER: '.$dataElement->POWER.', VOLTAGE: '.$dataElement->VOLTAGE );
          $util::logError( 'The data type of the error code is ('.gettype($e->getCode()).') and the value of the error code is ('.$e->getCode().')');
        }
      }
    }
  }
  catch( Exception $e ){
    $util::logError( 'Meter Exception: ' . $e->getMessage() );
  }
}

$util::logInfo( 'End.  Execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>