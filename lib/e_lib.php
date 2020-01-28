<?php
/**
  * API Class to connect to TED 5000
  *
  */

class Gateway_Exception extends Exception{
}

class TED5000_Gateway{
  protected  $ch
            ,$IP            // Most likely an URL and port number rather than a strict set of TCP/IP octets.
            ,$dateTime      // The time the TED 5000 thinks is now.
            ,$voltage       // Voltage (for system, not per MTU)
            ,$power         // kWh in use RIGHT NOW
            ,$powerToday    // kWh used so far today
            ,$powerMTD      // kWh used so far this month
            ,$powerProj     // kWh projected to be used this month
            ,$mtuCount;     // Number of MTU(s) configured on TED 5000 Gateway


  // Low level communication adjustments
  protected static  $initialTimeout = 5000,     // Start with a 5 second timeout
                    $timeoutIncrement = 5000,   // Each time that the curl operation times out, add 5 seconds beore trying again
                    $maxRetries = 4;            // Try at most 4 times before giving up (5 + 10 + 15 + 20 = 50 seconds spent trying!)

  private $debug = false;

  public function __construct( $mtuRec ){
    $this->IP = $mtuRec['ip'];
    $this->ch = curl_init();
    curl_setopt( $this->ch, CURLOPT_USERAGENT, 'A' );
    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );

    $this->debug = 0;
/*
this is actually a bridge? and it can have up to four MTUs in it
add these variables
    $this->uptime
    ->lastimtestamp
    ->voltage
    ->mtuID
*/
  }

  public function __destruct(){
    curl_close( $this->ch );
  }

  protected function getMTUData( $cmd ){
/*
    $commandURL = 'http://' . $this->IP . $cmd;
    $this->connectOK = -1;
    $newTimeout = self::$initialTimeout;

    // For reference http://www.php.net/curl_setopt
    curl_setopt( $this->ch, CURLOPT_URL, $commandURL );
    $retry = 0;
    do{
if( $retry > 0 ) $util::logInfo( "e_lib: getMTUData: setting timeout to $newTimeout for try number $retry." );
      curl_setopt( $this->ch, CURLOPT_TIMEOUT_MS, $newTimeout );
      $retry++;
      $outputs = curl_exec( $this->ch );
      if( curl_errno( $this->ch ) != 0 ){
        $util::logWarn( 't_lib: getStatData curl error (' .  curl_error( $this->ch ) .' -> '. curl_errno( $this->ch ) . ") when performing command ($cmd) on try number $retry" );
        if( curl_errno( $this->ch ) == 28 ){
          $newTimeout += self::$timeoutIncrement;
          $util::logInfo( "e_lib: getMTUData: changed timeout to $newTimeout because of timeout error in curl command." );
        }
      }
      / ** Build in one second sleep after each communication attempt
         *
         * Later on, in a many MTU environment, each mtu will need to be queried in a thread so that the delays
         * do not stack up and slow the overall application to a crawl.
         * /
      sleep( 1 );    }
    while( ( curl_errno( $this->ch ) != 0 ) && ($retry < self::$maxRetries) );

    if( $retry > 1 ){
      $util::logWarn( "e_lib: Made $retry attempts and last curl status was " . curl_errno( $this->ch ) );
    }
    $this->connectOK = curl_errno( $this->ch ); // Only capture the last status because the retries _might_ have worked!

    if( $this->debug ){
      // Convert to use log?
      echo '<br>commandURL: ' . $commandURL . '<br>';
      echo '<br>Stat says:<br>';
      if( $this->connectOK != 0 ){
        echo var_dump( json_decode( $outputs ) );
      }
      else{
        echo '<br>Communication error - the MTU did not say ANYTHING!';
      }
      echo '<br><br>';
    }

    if( $this->connectOK != 0 ){
      // Drat some problem.  Now what?
      $util::logError( 'e_lib: getMTUData communication error.' );
    }

    return $outputs;
*/
  }

  // Just ping the thing to get a sign of life
  public function getStatus(){
    global $util;

/*
THE UNIT STATUS PAGE HAS UPTIME THAT THE MAIN XML DOES NOT!!!!!

Need to grab data for system and 3rd Party posting data (if any)
  Date
  Uptime
  ACK CHART<br>
  REQ_HISTORY 0x01<br>
  SILENCE_MODE 0x02<br>
  BOOTLOADER_PKT 0x03<br>
  UPDATE_FREQ 0x04<br>
  UPDATE_MTUID 0x08<br>
  SAVE_SETUP 0x09<br>
  CALIBRATE 0x0A<br>
  ENH_MODE 0x0D<br>
  TX_POWER 0x0E<br>

    $commandURL = 'http://' . $this->IP . '/stats.htm';
    $html = file_get_html( $commandURL );   // Load one instance of the page
    $html->find( 'table' )->plaintext;      // Get an array of all the table data elements

    foreach( $html->find('td') as $element ){
      if( strpos( $element, 'seconds' ) ){  // When the text of the data element ends with "seconds"
        return explode( ' ', $element->plaintext )[0];   // Use a space delimited parse to get the number of seconds and return it.
      }
    }
    return;                                 // Return empty string when no results found;
*/
    curl_setopt( $this->ch, CURLOPT_USERAGENT, 'A' );
    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );

    $commandURL = 'http://' . $this->IP . '/api/LiveData.xml';
    curl_setopt( $this->ch, CURLOPT_URL, $commandURL );
    $outputs = curl_exec( $this->ch );

    if( strlen( $outputs ) < 1 ){
      $util::logError( "e_lib: No useful return from TED 5000" );
    }
    else{
      $xml = simplexml_load_string( $outputs );

      try{
        $this->dateTime = new DateTime( '20' . $xml->GatewayTime->Year .'/'. $xml->GatewayTime->Month .'/'. $xml->GatewayTime->Day .' '. $xml->GatewayTime->Hour .':'. $xml->GatewayTime->Minute .':'. $xml->GatewayTime->Second );
        $this->voltage = ($xml->Voltage->Total->VoltageNow) / 10;
        $this->power = ($xml->Power->Total->PowerNow) / 1000;
        $this->powerToday = ($xml->Power->Total->PowerTDY) / 1000;
        $this->powerMTD = ($xml->Power->Total->PowerMTD) / 1000;
        $this->powerProj = ($xml->Power->Total->PowerProj) / 1000;
        $this->mtuCount = $xml->System->NumberMTU;
        return true;
      }
      catch( Exception $e ){
        $util::logError( "e_lib: Error while parsing returned data - $e->getMessage()" );
      }
    }
    return false;
  }

  // Return time right now.
  public function getTime(){
    return $this->dateTime->format( 'Y-m-d H:i:s' );
  }

  // Return voltage right now.
  public function getVoltage(){
    return $this->voltage;
  }

  // Return kWh in use right now.
  public function getPower(){
    return $this->power;
  }

  // Return kWh used so far today
  public function getPowerToday(){
    return $this->powerToday;
  }

  // Return kWh used so far this month
  public function getPowerMTD(){
    return $this->powerMTD;
  }

  // Return kWh projected to be used this month
  public function getPowerProj(){
    return $this->powerProj;
  }

  // Return number of configured MTUs
  public function getMTUCount(){
    return $this->mtuCount;
  }


}

?>