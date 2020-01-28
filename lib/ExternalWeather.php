<?php
class ExternalWeather_Exception extends Exception
{
}

class ExternalWeather
{
  protected $config = array();

  private $debug = 1;

  public function __construct( $config = array() ){
    $this->config = $config;
    if( !isset($config['type']) ){
      $config['type'] = 'openweathermap';
    }
    if( !isset($config['units']) ){
      $config['units'] = 'F';
    }
  }

  /**
    * Should the calling function test $this->config['useWeather']
    * or should that happen in here and just return -1 for each value
    */
  public function getOutdoorWeather( $zip = null ){
    global $util;
// Why the hell is $util not working here when it works literally EVERYWHERE else??!?!?!?!?!?!
    if( ! $this->config['useWeather'] ){
      return array( 'temp' => -1, 'humidity' => -1 );
    }

    if( empty($zip) ){
      $util::logError( 'Cannot proceed without some kind of location identifier.' );
      throw new ExternalWeather_Exception( 'Zip not set' );
    }

    $this->outdoorTemp = false;
    $this->outdoorHumidity = false;

    switch( $this->config['type'] ){
      case 'openweathermap':
        if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) ){
          $util::logError( 'Cannot proceed without OpenWeatherMap API key.' );
          throw new ExternalWeather_Exception( 'OpenWeatherMap API key not set' );
        }
        // Check if user configured URL for Weather API
        if( !isset( $this->config['api_loc'] ) || empty( $this->config['api_loc'] ) ){
          $util::logError( 'Cannot proceed without OpenWeatherMap API location.' );
          throw new ExternalWeather_Exception( 'OpenWeatherMap API loc not set' );
        }
        // Create URL based on zip code and current conditions
        // Always use Imperial units and convert to Metric if the config calls for it.
        $this->url = $this->config['api_loc'] . '?' . 'zip=' . $zip . '&units=imperial' . '&APPID=' . $this->config['api_key'];
// Works with and without the ",us" qualifier in the URL
// http://api.openweathermap.org/data/2.5/weather?zip=75075,us&units=imperial&APPID=0123456789ABCDEF0123456789ABCDEF
// http://api.openweathermap.org/data/2.5/weather?zip=75075&units=imperial&APPID=0123456789ABCDEF0123456789ABCDEF


        try{
          $curl = curl_init();
          curl_setopt( $curl, CURLOPT_FAILONERROR, true );
          curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
          curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
          curl_setopt( $curl, CURLOPT_URL, $this->url );
          // Should I put the arguments into parameters or leave them in the url?
          //curl_setopt( $curl, CURLOPT_HTTPHEADER, $params );
          $result = curl_exec( $curl );
          $data = json_decode( $result );
          curl_close( $curl );
//$util::logInfo( 'Here is the JSON result from OpenWeatherMap API\n ' . json_encode( $data, JSON_PRETTY_PRINT ) );
        }
        catch( Exception $e ){
          $util::logError( 'Fail to fetch data from OpenWeatherMap API.' );
        }
        if( $this->config['units'] == 'C' ){
          // Force conversion to C since I'm forcing the API to return Imperial units.
          $this->outdoorTemp = ((9 * $data->main->temp) / 5) + 32;
        } else {
          // If it's not C assume it is F (what, you want Kelvin or Rankine?)
          $this->outdoorTemp = $data->main->temp;
        }
        $this->outdoorHumidity = $data->main->humidity;

      break;

      case 'noaa':
        if( !isset( $this->config['api_loc'] ) || empty( $this->config['api_loc'] ) ){
          $util::logError( 'Cannot proceed without NOAA API location.' );
          throw new ExternalWeather_Exception( 'NOAA API loc not set' );
        }
        $this->url = $this->config['api_loc'];

// Need to find a way to use zip code if possible.


/* sample code
$url='https://www.ncdc.noaa.gov/cdo-web/api/v2/datacategories?limit=41'; //noaa url of choice
$params=array('token:ttrxriPGOWeaYmWAShmkvvHtIehpxxBZ');
$curl = curl_init();
curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $params);
curl_setopt($curl, CURLOPT_URL, $url);
$result = curl_exec($curl);
$data = json_decode($result);
curl_close($curl);
echo "<pre>";
echo json_encode($data, JSON_PRETTY_PRINT);
echo "<pre>";

*/
/* They no longer use simple XML and require an API key
        if( !$doc = file_get_contents( $this->config['api_loc'] ) ){
          $util::logError( 'NOAA API returned no data.' );
          throw new ExternalWeather_Exception( 'Could not contact noaa xml' );
        }
        if( !$xml = simplexml_load_string( $doc ) ){
          $util::logError( 'NOAA xml could not be parsed.' );
          throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
        }
        if( $this->config['units'] == 'C' ){
          $this->outdoorTemp = $xml->temp_c;
        }
        else{
          $this->outdoorTemp = $xml->temp_f;
        }
        $this->outdoorHumidity = $xml->relative_humidity;
*/
      break;

      case 'weatherbug':
        throw new ExternalWeather_Exception( 'Defunct' );
/* DEAD CODE
        if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) ){
          $util::logError( 'Cannot proceed without Weatherbug API key.' );
          throw new ExternalWeather_Exception( 'Weatherbug API key not set' );
        }
        // Check if user configured URL for Weather API
        if( isset( $this->config['api_loc'] ) ){
          // Use the user specified URL (e.g. personal weather station)
          $this->url = $this->config['api_loc'];
        }
        else{
          // Create URL based on zip code and current conditions
          $this->url = 'http://api.wxbug.net/getLiveWeatherRss.aspx?ACode=' . $this->config['api_key'] . '&zipcode=' . $zip . '&unittype=0';
        }

        if( !$this->doc = file_get_contents($url) ){
          $util::logError( 'Weatherbug API returned no data.' );
          throw new ExternalWeather_Exception( 'Could not contact weatherbug api' );
        }
        if( !$xml = simplexml_load_string($this->doc) ){
          $util::logError( 'Weatherbug xml could not be parsed.' );
          throw new ExternalWeather_Exception( 'Could not parse XML: ' . $this->doc );
        }
        $this->aws = $xml->channel->children( 'http://www.aws.com/aws' );

        $this->temporary_catch = $this->aws->weather->ob->temp;
        if( $this->config['units'] == 'C' ){
          // Weatherbug API only supports units in F, not C.  Force conversion.
          $this->outdoorTemp = ((9 * $this->temporary_catch) / 5) + 32;
        }
        else{
          $this->this->outdoorTemp = $this->temporary_catch;
        }

        $this->outdoorHumidity = $this->aws->weather->ob->humidity;
*/
      break;

      case 'weatherunderground':
        throw new ExternalWeather_Exception( 'Defunct as of 2018/12/31' );
/* DEAD CODE
        if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) ){
          $util::logError( 'Cannot proceed without Weatherunderground API key.' );
          throw new ExternalWeather_Exception( 'Weatherunderground API key not set' );
        }
        // Check if user configured URL for Weather API
        if( isset( $this->config['api_loc'] ) ){
          // Use the user specified URL (e.g. personal weather station)
          $this->url = $this->config['api_loc'];
        }
        else{
          // Create URL based on zip code and current conditions
          $this->url = 'https://api.wunderground.com/api/' . $this->config['api_key'] . '/conditions/q/' . $zip . '.xml';
        }
        if( !$this->doc = file_get_contents( $this->url ) ){
          $util::logError( 'Weatherunderground API returned no data.' );
          throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
        }
        if( !$this->xml = simplexml_load_string( $this->doc ) ){
          $util::logError( 'Weatherunderground xml could not be parsed.' );
          throw new ExternalWeather_Exception( 'Could not parse XML: ' . $this->doc );
        }
        if( $this->config['units'] == 'C' ){
          $this->outdoorTemp = $this->xml->current_observation->temp_c;
        }
        else{
          // If it's not C assume it is F (what, you want Kelvin or Rankine?)
          $this->outdoorTemp = $this->xml->current_observation->temp_f;
        }
        $this->outdoorHumidity = $this->xml->current_observation->relative_humidity;
        $this->outdoorHumidity = str_replace('%', '', $this->outdoorHumidity);
*/
      break;

      default:
        throw new ExternalWeather_Exception( 'Must specify a weather service.' );
      break;
    }
    return array( 'temp' => $this->outdoorTemp, 'humidity' => $this->outdoorHumidity );
  }

  public function getOutdoorForecast( $zip = null ){
    global $util;
$util::logInfo( 'START' );
    if( ! $this->config['useForecast'] ){
      $util::logWarn( 'Config is set to not report forecast.  So do not report forecast.' );
      return NULL;
    }

    if( empty( $zip ) ){
      $util::logError( 'Cannot proceed without some kind of location identifier.' );
      throw new ExternalWeather_Exception( 'Zip not set' );
    }

    switch( $this->config['type'] ){
      default:
      case 'noaa':
        throw new ExternalWeather_Exception( 'Not implemented yet' );
      break;

      case 'weatherbug':
        throw new ExternalWeather_Exception( 'Defunct' );
      break;

      case 'weatherunderground':
        throw new ExternalWeather_Exception( 'Defunct as of 2018/12/31' );
/* DEAD CODE
        if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) ){
          throw new ExternalWeather_Exception( 'Weatherunderground API key not set' );
        }
        // Check if user configured URL for Weather API
        if( isset( $this->config['api_loc'] ) ){
          // Use the user specified URL (e.g. personal weather station)
          $this->url = $this->config['api_loc'];
        }
        else{
          // Create URL based on zip code and current conditions
          // Even though using https for the API call, the returned HTML references http for images.
          $this->url = 'https://api.wunderground.com/api/' . $this->config['api_key'] . '/forecast/q/' . $zip . '.xml';
//$util::logInfo( 'url is [[['.$this->url.']]]' );
        }
        if( !$this->doc = file_get_contents( $this->url ) ){
          throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
        }
        if( !$this->xml = simplexml_load_string( $this->doc ) ){
          throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
        }
*/
      break;
    }

    try{
      foreach( $this->xml->forecast->simpleforecast->forecastdays as $forecast ){
        foreach( $forecast->forecastday as $day ){
          $result[] = $day;
          //$result[] = $day->high->fahrenheit;
          //$result[] = $day->icon_url;
          //$result[] = $day->high->celsius;
        }
      }
      return $result;
    }
    catch( Exception $e ){
      // For some stupid reason sometimes the $this->xml->forecast->simpleforecast->forecastdays is not an array and foreach pukes hard
      return NULL;
    }

    //return $this->xml->forecast->simpleforecast->forecastdays;
  }

/*
  function __toString()
  {
    $returnString = '<br>START';

    $returnString .= "<br>&nbsp;&nbsp;doc" . urlencode( $this->doc );
    //$returnString .= "<br>&nbsp;&nbsp;xml" . $this->xml;

    return $returnString . "<br>END";
  }
*/

}