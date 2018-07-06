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
      $config['type'] = 'weatherunderground';
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
    if( ! $this->config['useWeather'] ){
      return array( 'temp' => -1, 'humidity' => -1 );
    }

    if( empty($zip) ){
      $util::logError( 'ExternalWeather: getOutdoorWeather: Cannot proceed without some kind of location identifier.' );
      throw new ExternalWeather_Exception( 'Zip not set' );
    }

    $this->outdoorTemp = false;
    $this->outdoorHumidity = false;

    switch( $this->config['type'] ){
      default:
      case 'noaa':
        if( !isset( $this->config['api_loc'] ) || empty( $this->config['api_loc'] ) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: Cannot proceed without NOAA API location.' );
          throw new ExternalWeather_Exception( 'NOAA API loc not set' );
        }
        if( !$doc = file_get_contents( $this->config['api_loc'] ) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: NOAA API returned no data.' );
          throw new ExternalWeather_Exception( 'Could not contact noaa xml' );
        }
        if( !$xml = simplexml_load_string( $doc ) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: NOAA xml could not be parsed.' );
          throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
        }
        if( $this->config['units'] == 'C' ){
          $this->outdoorTemp = $xml->temp_c;
        }
        else{
          $this->outdoorTemp = $xml->temp_f;
        }
        $this->outdoorHumidity = $xml->relative_humidity;
      break;

      case 'weatherbug':
        if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: Cannot proceed without Weatherbug API key.' );
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
          $util::logError( 'ExternalWeather: getOutdoorWeather: Weatherbug API returned no data.' );
          throw new ExternalWeather_Exception( 'Could not contact weatherbug api' );
        }
        if( !$xml = simplexml_load_string($this->doc) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: Weatherbug xml could not be parsed.' );
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
      break;

      case 'weatherunderground':
        if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: Cannot proceed without Weatherunderground API key.' );
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
          $util::logError( 'ExternalWeather: getOutdoorWeather: Weatherunderground API returned no data.' );
          throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
        }
        if( !$this->xml = simplexml_load_string( $this->doc ) ){
          $util::logError( 'ExternalWeather: getOutdoorWeather: Weatherunderground xml could not be parsed.' );
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
      break;
    }
    return array( 'temp' => $this->outdoorTemp, 'humidity' => $this->outdoorHumidity );
  }

  public function getOutdoorForcast( $zip = null ){
    global $log;
    if( ! $this->config['useForecast'] ){
      $util::logWarn( 'ExternalWeather: getOutdoorForcast: Config is set to not report forecast.  So do not report forecast.' );
      return NULL;
    }

    if( empty( $zip ) ){
      $util::logError( 'ExternalWeather: getOutdoorForcast: Cannot proceed without some kind of location identifier.' );
      throw new ExternalWeather_Exception( 'Zip not set' );
    }

    switch( $this->config['type'] ){
      default:
      case 'noaa':
        throw new ExternalWeather_Exception( 'Not implemented yet' );
      break;

      case 'weatherbug':
        throw new ExternalWeather_Exception( 'Not implemented yet' );
      break;

      case 'weatherunderground':
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
        }
        if( !$this->doc = file_get_contents( $this->url ) ){
          throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
        }
        if( !$this->xml = simplexml_load_string( $this->doc ) ){
          throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
        }
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