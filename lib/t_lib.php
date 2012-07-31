<?php

class Stat
{
  protected $ch;
  protected $IP;
  protected $ZIP;

  private $debug = 0;
/*
  private $temp,
          $tmode,
          $fmode,
          $override,
          $hold,
          $t_cool,
          $tstate,
          $fstate,
          $day,
          $time,
          $t_type_post;
  private $runTimeCool,
          $runTimeHeat,
          $runTimeCoolYesterday,
          $runTimeHeatYesterday;
  private $errStatus;
  private $model;

  // system variables
  private $uuid = 0,
          $api_version = 0,
          $fw_version = 0,
          $wlan_fw_version = 0,
          $ssid = 0,
          $bssid = 0,
          $channel = 0,
          $security = 0,
          $passphrase = 0,
          $ipaddr = 0,
          $ipmask = 0,
          $ipgw = 0,
          $rssi = 0;
*/

  public function __construct( $ip, $zip )
  {
    $this->IP = $ip;
    $this->ZIP = $zip;
    $this->ch = curl_init();
    curl_setopt( $this->ch, CURLOPT_USERAGENT, "A" );
    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );

    $this->debug = 0;

    // Stat variables
    $this->temp = 0;
    $this->tmode = 0;
    $this->fmode = 0;
    $this->override = 0;
    $this->hold = 0;
    $this->t_cool = 0;
    $this->tstate = 0;
    $this->fstate = 0;
    $this->day = 0;
    $this->time = 0;
    $this->t_type_post = 0;
    //
    $this->runTimeCool = 0;
    $this->runTimeHeat = 0;
    $this->runTimeCoolYesterday = 0;
    $this->runTimeHeatYesterday = 0;
    //
    $this->errStatus = 0;
    //
    $this->model = 0;

    // System variables
    $this->uuid = 0;
    $this->api_version = 0;
    $this->fw_version = 0;
    $this->wlan_fw_version = 0;
    $this->ssid = 0;
    $this->bssid = 0;
    $this->channel = 0;
    $this->security = 0;
    $this->passphrase = 0;
    $this->ipaddr = 0;
    $this->ipmask = 0;
    $this->ipgw = 0;
    $this->rssi = 0;


    // Cloud variables


  }

  public function __destruct()
  {
    curl_close($this->ch);
  }

  public function getOutdoorTemp()
  {
    global $weather_underground_api_key;
    $outdoorTemp = -999;

    // Old API that was open to everyone
    //$base_url = "http://api.wunderground.com/auto/wui/geo/WXCurrentObXML/index.xml?query=";
    //$url = $base_url . $this->ZIP;

    // New API that requires registration
    $url = "http://api.wunderground.com/api/" . $weather_underground_api_key . "/conditions/q/" . $this->ZIP . ".xml";
    $doc = new DOMDocument();
    if( $doc->load( $url, LIBXML_NOERROR ) )
    {
      $locs = $doc->getElementsByTagName( "current_observation" );
      foreach( $locs as $loc )
      {
        $outdoorTemp =  $loc->getElementsByTagName( "temp_f" )->item(0)->nodeValue; // . "&deg; F";
      //$outdoorTemp =  $loc->getElementsByTagName( "temp_c" )->item(0)->nodeValue; // . "&deg; C";
      }
    }
    return $outdoorTemp;
  }

  protected function getStatData( $cmd )
  {
    $commandURL = "http://" . $this->IP . $cmd;

    curl_setopt( $this->ch, CURLOPT_URL, $commandURL );
    $outputs = curl_exec( $this->ch );
if( $this->debug == 1 )
{
echo "<br>commandURL: " . $commandURL . "<br>";
echo "<br>Stat says:<br>";
echo var_dump( json_decode( $outputs ) );
echo "<br><br>";
}
    // Move fetched data to internal data structure
    return $outputs;
  }

  protected function setStatData( $command, $value )
  {
    $commandURL = "http://" . $this->IP . $command;

    curl_setopt( $this->ch, CURLOPT_URL, $commandURL );
    curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $value );

if( $this->debug == 1 )
{
echo "<br>commandURL: " . $commandURL . "<br>";
}

    $outputs = curl_exec( $this->ch );

    // Need to wait for a response...  object(stdClass)#4 (1) { ["success"]=> int(0) }
    return;
  }

// Need 28 functions
/*
*    1 /tstat
*    2 /tstat/datalog
*    3 /tstat/errstatus
?    4 /tstat/eventlog
D    5 /tstat/fmode
X?   6 /tstat/help
D    7 /tstat/hold
D    8 /tstat/humidity
*    9 /tstat/info
*    10 /tstat/model
D    11 /tstat/override
*    12 /tstat/power
?    13 /tstat/set-beep
?    14 /tstat/set-uma
?    15 /tstat/set-pma
*    16 /tstat/temp
D    17 /tstat/time/day
D    18 /tstat/time/hour
D    19 /tstat/time/minute
    20 /tstat/time
    21 /tstat/tmode
    22 /tstat/thumidity
    23 /tstat/ttemp
    24 /tstat/program
    25 /tstat/program/heat
    26 /tstat/program/heat/DDD  (where DD is one of mon, tue, wed, thu, fri, sat, sun)
    27 /tstat/program/cool
    28 /tstat/program/cool/DDD  (where DD is one of mon, tue, wed, thu, fri, sat, sun)

*    29 /sys
*    30 /sys/network

====================================
According to /tstat/help  OLD
/tstat/}atalog      get  x
/tstat/errstatus    get  x
/tstat/eventlog     get  x
/tstat/fmode        get set
/tstat/help         get  x
/tstat/hold         get set
/tstat/humidity     get  x
/tstat/led          x  set
/tstat/model        get  x
/tstat/override     get  x
/tstat/power        get set
/tstat/beep         x  set
/tstat/uma          x  set
/tstat/pma          x  set
/tstat/temp         get  x
/tstat/time/day     get  x
/tstat/time/hour    get  x
/tstat/time/minute  get  x
/tstat/time         get set
/tstat/tmode        get set
/tstat/thumidity    get set
/tstat/ttemp        get  x
/tstat/program      get set
/tstat/version      get  x
/tstat/             get set

====================================
According to /tstat/help  2012/7/6
!! = new feature
CC = changed feature
several DELETED?

!! /}ir_baffle        get set
   /datalog get       x
!! /dehumidifier      get set
!! /ext_dehumidifier  get set
   /errstatus         get  x
   /eventlog          get  x
   /fmode             get set
!! /fan_ctime         get set
   /help              get  x
   /hold              get set
   /humidity          get  x
!! /hvac_recovery     get set
!! /hvac_settings     get  x
CC /humidifier        get set
   /led               x   set
!! /lock              get set
   /model             get  x
!! /night_light       get set
   /override          get  x
   /power             get set
   /beep              x   set
   /uma               x   set
   /pma               x   set
   /temp              get  x
!! /differential      get set
   /time/day          get  x
   /time/hour         get  x
   /time

*/
  public function showMe()
  {
    // For now hard coded HTML <br> but later let CSS do that work
    echo "<br><br>Thermostat data (Yaay!  I found the introspection API - hard coding SUCKS)";
    echo "<table id='stat_data'>";
    echo "<tr><th>Setting</th><th>Value</th><th>Explanation</th></tr>";

    $rc = new ReflectionClass('Stat');
    $prs = $rc->getProperties();
    $i = 0;
    foreach( $prs as $pr )
    {
      if( $i == 0 )
      {
        $i = 1;
        echo "<tr>";
      }
      else
      {
        $i = 0;
        echo "<tr class='alt'>";
      }
      $key = $pr->getName();
      $val = $this->{$pr->getName()};
      if( $key == "ZIP" || $key == "ssid" )
      {
        $val = "MASKED";
      }
      echo "<td>" . $key . "</td><td>" . $val . "</td></tr>";
    }
  }

  // Still need a list of explanation and values to interpret.
  public function showMe2()
  {
    // For now hard coded HTML <br> but later let CSS do that work
    echo "<br><br>Thermostat data";
    echo "<table id='stat_data'>";
    echo "<tr><th>Setting</th><th>Value</th><th>Explanation</th></tr>";

//    echo "<br><br>From /tstat command";
    echo "<tr><td>this->temp</td><td>" . $this->temp . "</td><td>°F</td></tr>";

    $statTMode = array( "Auto?", "Heating", "Cooling" );
    echo "<tr class='alt'><td>this->tmode</td><td>" . $this->tmode        . "</td><td> [ " . $statTMode[$this->tmode] . " ] </td></tr>";

    $statFanMode = array( "Auto", "On" );
    echo "<tr><td>this->fmode</td><td>" . $this->fmode        . "</td><td> [ " . $statFanMode[$this->fmode] . " ] </td></tr>";

    echo "<tr class='alt'><td>this->override</td><td>" . $this->override . "</td><td></td></tr>";

    $statHold = array( "Normal", "Hold Active" );
    echo "<tr><td>this->hold</td><td>" . $this->hold         . "</td><td> [ " . $statHold[$this->hold] . " ] </td></tr>";

    echo "<tr class='alt'><td>this->t_cool</td><td>" . $this->t_cool . "</td><td>°F</td></tr>";

    $statTState = array( "Off", "Heating", "Cooling" );
    echo "<tr class='alt'><td>this->tstate</td><td>" . $this->tstate       . "</td><td> [ " . $statTState[$this->tstate] . " ] </td></tr>";

    $statFanState = array( "Off", "On" );
    echo "<tr><td>this->fstate</td><td>" . $this->fstate       . "</td><td> [ " . $statFanState[$this->fstate] . " ] </td></tr>";

//    echo "<br>this->day        : " . $this->day          . " [ " . jddayofweek($this->day,1) . " ] </td><td></td></tr>";
    $statDayOfWeek = array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday" );
    echo "<tr class='alt'><td>this->day</td><td>" . $this->day          . "</td><td> [ " . $statDayOfWeek[$this->day] . " ] </td></tr>";

    echo "<tr><td>this->time</td><td>" . $this->time . "</td><td></td></tr>";
    echo "<tr class='alt'><td>this->t_type_post</td><td>" . $this->t_type_post . "</td><td></td></tr>";

//    echo "<br><br>From /tstat/datalog command (converted to minutes)";
    echo "<tr><td>this->runTimeCool</td><td>" . $this->runTimeCool . "</td><td></td></tr>";
    echo "<tr class='alt'><td>this->runTimeHeat</td><td>" . $this->runTimeHeat . "</td><td></td></tr>";
    echo "<tr><td>this->runTimeCoolYesterday</td><td>" . $this->runTimeCoolYesterday . "</td><td></td></tr>";
    echo "<tr class='alt'><td>this->runTimeHeatYesterday</td><td>" . $this->runTimeHeatYesterday . "</td><td></td></tr>";

//    echo "<br><br>From /tstat/errorstatus command";
    echo "<tr><td>this->errStatus</td><td>" . $this->errStatus          . "</td><td>[ 0 is OK ]</td></tr>";

//    echo "<br><br>From /tstat/model command";
    echo "<tr class='alt'><td>this->model</td><td>" . $this->model . "</td><td></td></tr>";

    echo "</table>";

    echo "<br><br>System data";

    echo "<table id='sys_data'>";
    echo "<tr><th>Setting</th><th>Value</th><th>Explanation</th></tr>";

//    echo "<tr><td>this->uuid</td><td>"            . $this->uuid            . "</td><td> MAC address of thermostat</td></tr>";
echo "<tr><td>this->uuid</td><td>"            . "MASKED"            . "</td><td> MAC address of thermostat</td></tr>";
    echo "<tr class='alt'><td>this->api_version</td><td>"     . $this->api_version     . "</td><td> 1 (?)</td></tr>";
    echo "<tr><td>this->fw_version</td><td>"      . $this->fw_version      . "</td><td> e.g. 1.03.24</td></tr>";
    echo "<tr class='alt'><td>this->wlan_fw_version</td><td>" . $this->wlan_fw_version . "</td><td> e.g. v10.99839</td></tr>";


//    echo "<tr><td>this->ssid</td><td>"       . $this->ssid       . "</td><td>SSID</td></tr>";
echo "<tr><td>this->ssid</td><td>"       . "MASKED"       . "</td><td>SSID</td></tr>";
//    echo "<tr class='alt'><td>this->bssid</td><td>"      . $this->bssid      . "</td><td>MAC address of wifi device</td></tr>";
echo "<tr class='alt'><td>this->bssid</td><td>"      . MASKED      . "</td><td>MAC address of wifi device</td></tr>";
    echo "<tr><td>this->channel</td><td>"    . $this->channel    . "</td><td>Current wifi channel e.g. 11</td></tr>";
//    echo "<tr class='alt'><td>this->security</td><td>"   . $this->security   . "</td><td>WiFi security protocol: 1 (WEP Open), 3 (WPA), 4 (WPA2 Personal)</td></tr>";
echo "<tr class='alt'><td>this->security</td><td>"   . "MASKED"   . "</td><td>WiFi security protocol: 1 (WEP Open), 3 (WPA), 4 (WPA2 Personal)</td></tr>";
//    echo "<tr><td>this->passphrase</td><td>" . $this->passphrase . "</td><td>password (not shown in api_version 113)</td></tr>";
echo "<tr><td>this->passphrase</td><td>" . "MASKED" . "</td><td>password (not shown in api_version 113)</td></tr>";
    echo "<tr class='alt'><td>this->ipaddr</td><td>"     . $this->ipaddr     . "</td><td>IP address of thermostat (api_version 113 shows '1' ?)</td></tr>";
    echo "<tr><td>this->ipmask</td><td>"     . $this->ipmask     . "</td><td>Netmask (not shown in api_version 113?)</td></tr>";
    echo "<tr class='alt'><td>this->ipgw</td><td>"       . $this->ipgw       . "</td><td>Gateway (not shown in api_version 113?)</td></tr>";
    echo "<tr><td>this->rssi</td><td>"       . $this->rssi       . "</td><td>Received Signal Strength (api_version 113)</td></tr>";
    echo "</table>";


    return;
  }

  public function getStat()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat" );
    $obj = json_decode( $outputs );
//$this->debug = 0;
/* Sample data (CR added for readability) 2012-07-06 Firmware 1.04.83
 {
   "temp":78.00,
   "tmode":2,
   "fmode":0,
   "override":1,
   "hold":0,
   "t_cool":79.00,
   "tstate":0,
   "fstate":0,
   "time":
   {
     "day":4,
     "hour":16,
     "minute":25
   },
   "t_type_post":0}
 */

    // Move fetched data to internal data structure
    $this->temp = $obj->{"temp"};             // Present temp in deg F
    $this->tmode = $obj->{"tmode"};           // Present t-stat mode
    $this->fmode = $obj->{"fmode"};           // Present fan mode
    $this->override = $obj->{"override"};     // Present override status 0 = off, 1 = on
    $this->hold = $obj->{"hold"};             // Present hold status 0 = off, 1 = on

    if( $this->tmode == 1 )
    { // mode 1 is heat
      $this->t_heat = $obj->{"t_heat"};       // Present heat target temperature in deg F
    }
    else if( $this->tmode == 2 )
    { // mode 2 is cool
      $this->t_cool = $obj->{"t_cool"};       // Present cool target temperature in deg F
    }

    $this->tstate = $obj->{"tstate"};         // Present heater/compressor state 0 = off, 1 = heating, 2 = cooling
    $this->fstate = $obj->{"fstate"};         // Present fan state 0 = off, 1 = on

    $var1 = $obj->{"time"};                   // Present time
    $this->day = $var1->{"day"};
//    $this->time = sprintf(" %2d:%02d %s",($var1->{"hour"} % 13) + floor($var1->{"hour"} / 12), $var1->{"minute"} ,$var1->{"hour"}>=12 ? "PM":"AM");
    $this->time = sprintf(" %2d:%02d",($var1->{"hour"}), $var1->{"minute"} );

    $this->t_type_post = $obj->{"t_type_post"};

    return;
  }

  public function getDataLog()
  {
    $outputs = $this->getStatData( "/tstat/datalog" );
    $obj = json_decode( $outputs );

    $var1 = $obj->{"today"};
    $var2 = $var1->{"heat_runtime"};
    $this->runTimeHeat = ($var2->{"hour"} * 60) + $var2->{"minute"};

    $var2 = $var1->{"cool_runtime"};
    $this->runTimeCool = ($var2->{"hour"} * 60) + $var2->{"minute"};

    $var1 = $obj->{"yesterday"};
    $var2 = $var1->{"heat_runtime"};
    $this->runTimeHeatYesterday = ($var2->{"hour"} * 60) + $var2->{"minute"};

    $var2 = $var1->{"cool_runtime"};
    $this->runTimeCoolYesterday = ($var2->{"hour"} * 60) + $var2->{"minute"};

    return;
  }

  public function getErrors()
  {
    $outputs = $this->getStatData( "/tstat/errstatus" );
    $obj = json_decode( $outputs );

    $this->errStatus = $obj->{"errstatus"};
    return;
  }

  public function getEventLog()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/eventlog" );
    $obj = json_decode( $outputs );
//$this->debug = 0;
    $var1 = $obj->{"eventlog"};

/* These are the outputs of eventlog

raw/literal output
{
  "eventlog":
  [
    ["hour","minute","relay","temp","humidity","ttemp"],
    [    11,       31,    64, 77.50,         0, 77]
  ]
}

php/parsed output
object(stdClass)#4 (1)
{
  ["eventlog"]=> array(3)
  {
    [0]=> array(6)
    {
      [0]=> string(4) "hour"
      [1]=> string(6) "minute"
      [2]=> string(5) "relay"
      [3]=> string(4) "temp"
      [4]=> string(8) "humidity"
      [5]=> string(5) "ttemp"
    }
    [1]=> array(6)
    {
      [0]=> int(11)
      [1]=> int(16)
      [2]=> int(64)
      [3]=> float(77.5)
      [4]=> int(0)
      [5]=> int(77)
    }
    [2]=> array(6)
    {
      [0]=> int(11)
      [1]=> int(21)
      [2]=> int(64)
      [3]=> float(77)
      [4]=> int(0)
      [5]=> int(77)
    }
  }
}
*/
    return;
  }

  // Essentially a duplicate function, but it works
  public function getFMode()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/fmode" );
    $obj = json_decode( $outputs );
//$this->debug = 0;

    $this->fmode = $obj->{"fmode"};           // Present fan mode
    return;
  }

  public function getHelp()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/help" );
    $obj = json_decode( $outputs );
//$this->debug = 0;

    return;
  }

  // Essentially a duplicate function, but it works
  public function getHold()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/hold" );
    $obj = json_decode( $outputs );
//$this->debug = 0;

    $this->hold = $obj->{"hold"};             // Present hold status 0 = off, 1 = on
    return;
  }

  // Returns -1 because it is not implemented in hardware
  public function getHumidity()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/humidity" );
    $obj = json_decode( $outputs );
//$this->debug = 0;

//    $this->humidity = $obj->{"humidity"};             // Present humidity
    return;
  }

  public function setLED()
  {
    return;  // Prevent problems for now
    $outputs = $this->getStatData( "/tstat/led" );
    $obj = json_decode( $outputs );

    return;
  }

  public function getModel()
  {
    $outputs = $this->getStatData( "/tstat/model" );
    $obj = json_decode( $outputs );
    $this->model = $obj->{"model"};

    return;
  }

  // Essentially a duplicate function, but it works
  public function getOverride()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/override" );
    $obj = json_decode( $outputs );
//$this->debug = 0;

    $this->override = $obj->{"override"};
    return;
  }

  public function getPower()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/power" );
    $obj = json_decode( $outputs );
//$this->debug = 0;

    $this->power = $obj->{"power"};  // Milliamps?
    return;
  }

  public function setBeep()
  {
    return;  // Prevent problems for now
    $outputs = $this->getStatData( "/tstat/beep" );
    $obj = json_decode( $outputs );

    return;
  }

  public function setUMA()
  {
    return;  // Prevent problems for now
    $outputs = $this->getStatData( "/tstat/uma" );
    $obj = json_decode( $outputs );

    return;
  }

  public function setPMA()
  {
    return;  // Prevent problems for now
    $outputs = $this->getStatData( "/tstat/pma" );
    $obj = json_decode( $outputs );

    return;
  }

  // Essentially a duplicate function, but it works
  public function getTemp()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/temp" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    $this->temp = $obj->{"temp"};             // Present temp in deg F
    return;
  }

  // Essentially a duplicate function, but it works
  public function getTimeDay()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/time/day" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    return;
  }

  // Essentially a duplicate function, but it works
  public function getTimeHour()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/time/hour" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    return;
  }

  // Essentially a duplicate function, but it works
  public function getTimeMinute()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/time/minute" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    return;
  }

  // Essentially a duplicate function, but it works
  public function getTime()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/tstat/time" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    return;
  }

  // Hard coded to Tuesday for now
  public function setTimeDay()
  {
    return;  // Prevent problems for now
//$this->debug = 1;
    $cmd = "/tstat/time/day";
    $value = "{\"day\":2}";   // 2 = Tuesday
    $outputs = $this->setStatData( $cmd, $value );
$this->debug = 1;

echo var_dump( json_decode( $outputs ) );
    return;
  }

  public function getSysInfo()
  {
//$this->debug = 1;
    //$outputs = $this->getStatData( "/sys/info" );
    $outputs = $this->getStatData( "/sys" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    $this->uuid = $obj->{"uuid"};
    $this->api_version = $obj->{"api_version"};
    $this->fw_version = $obj->{"fw_version"};
    $this->wlan_fw_version = $obj->{"wlan_fw_version"};

    return;
  }

  public function getSysNetwork()
  {
//$this->debug = 1;
    $outputs = $this->getStatData( "/sys/network" );
    $obj = json_decode( $outputs );
$this->debug = 0;

    $this->ssid = $obj->{"ssid"};
    $this->bssid = $obj->{"bssid"};
    $this->channel = $obj->{"channel"};
    $this->security = $obj->{"security"};
    $this->passphrase = $obj->{"passphrase"};
    $this->ipaddr = $obj->{"ipaddr"};
    $this->ipmask = $obj->{"ipmask"};
    $this->ipgw = $obj->{"ipgw"};
    $this->rssi = $obj->{"rssi"};

    return;
  }

}

?>