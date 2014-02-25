<?php
class ExternalWeather_Exception extends Exception
{
}

class ExternalWeather
{
	protected $config = array();

	private $debug = 1;

	public function __construct( $config = array() )
	{
		$this->config = $config;
		if( !isset($config['type']) )
		{
			$config['type'] = 'weatherunderground';
		}
		if( !isset($config['units']) )
		{
			$config['units'] = 'F';
		}
	}

	/**
		* Should the calling function test $this->config['useWeather']
		* or should that happen in here and just return -1 for each value
		*/
	public function getOutdoorWeather( $zip = null )
	{
		if( ! $this->config['useWeather'] )
		{
			return array( 'temp' => -1, 'humidity' => -1 );
		}

		if( empty($zip) )
		{
			throw new ExternalWeather_Exception( 'Zip not set' );
		}

		$this->outdoorTemp = false;
		$this->outdoorHumidity = false;

		switch( $this->config['type'] )
		{
			default:
			case 'noaa':
				if( !isset( $this->config['api_loc'] ) || empty( $this->config['api_loc'] ) )
				{
					throw new ExternalWeather_Exception( 'NOAA API loc not set' );
				}
				if( !$doc = file_get_contents( $this->config['api_loc'] ) )
				{
					throw new ExternalWeather_Exception( 'Could not contact noaa xml' );
				}
				if( !$xml = simplexml_load_string( $doc ) )
				{
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
				}
				if( $this->config['units'] == 'C' )
				{
					$this->outdoorTemp = $xml->temp_c;
				}
				else
				{
					$this->outdoorTemp = $xml->temp_f;
				}
				$this->outdoorHumidity = $xml->relative_humidity;
			break;

			case 'weatherbug':
				if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) )
				{
					throw new ExternalWeather_Exception( 'Weatherbug API key not set' );
				}
				// Check if user configured URL for Weather API
				if( isset( $this->config['api_loc'] ) )
				{	// Use the user specified URL (e.g. personal weather station)
					$this->url = $this->config['api_loc'];
				}
				else
				{	// Create URL based on zip code and current conditions
					$this->url = 'http://api.wxbug.net/getLiveWeatherRss.aspx?ACode=' . $this->config['api_key'] . '&zipcode=' . $zip . '&unittype=0';
				}

				if( !$this->doc = file_get_contents($url) )
				{
					throw new ExternalWeather_Exception( 'Could not contact weatherbug api' );
				}
				if( !$xml = simplexml_load_string($this->doc) )
				{
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $this->doc );
				}
				$this->aws = $xml->channel->children( 'http://www.aws.com/aws' );

				$this->temporary_catch = $this->aws->weather->ob->temp;
				if( $this->config['units'] == 'C' )
				{ // Weatherbug API only supports units in F, not C.	Force conversion.
					$this->outdoorTemp = ((9 * $this->temporary_catch) / 5) + 32;
				}
				else
				{
					$this->this->outdoorTemp = $this->temporary_catch;
				}

				$this->outdoorHumidity = $this->aws->weather->ob->humidity;
			break;

			case 'weatherunderground':
				if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) )
				{
					throw new ExternalWeather_Exception( 'Weatherunderground API key not set' );
				}
				// Check if user configured URL for Weather API
				if( isset( $this->config['api_loc'] ) )
				{	// Use the user specified URL (e.g. personal weather station)
					$this->url = $this->config['api_loc'];
				}
				else
				{	// Create URL based on zip code and current conditions
					$this->url = 'http://api.wunderground.com/api/' . $this->config['api_key'] . '/conditions/q/' . $zip . '.xml';
				}
				if( !$this->doc = file_get_contents( $this->url ) )
				{
					throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
				}
				if( !$this->xml = simplexml_load_string( $this->doc ) )
				{
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $this->doc );
				}
				if( $this->config['units'] == 'C' )
				{
					$this->outdoorTemp = $this->xml->current_observation->temp_c;
				}
				else
				{
					// If it's not C assume it is F (what, you want Kelvin or Rankine?)
					$this->outdoorTemp = $this->xml->current_observation->temp_f;
				}
				$this->outdoorHumidity = $this->xml->current_observation->relative_humidity;
				$this->outdoorHumidity = str_replace('%', '', $this->outdoorHumidity);
			break;
		}
		return array( 'temp' => $this->outdoorTemp, 'humidity' => $this->outdoorHumidity );
	}

	public function getOutdoorForcast( $zip = null )
	{
global $log;
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path start' );
		if( ! $this->config['useForecast'] )
		{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says it is turned OFF' );
			return NULL;
		}

		if( empty($zip) )
		{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says you have no ZIP' );
			throw new ExternalWeather_Exception( 'Zip not set' );
		}

$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says your type is ' . $this->config['type'] );
		switch( $this->config['type'] )
		{
			default:
			case 'noaa':
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says I do not know how to do noaa.' );
				throw new ExternalWeather_Exception( 'Not implemented yet' );
			break;

			case 'weatherbug':
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says I do not know how to do weatherbug.' );
				throw new ExternalWeather_Exception( 'Not implemented yet' );
			break;

			case 'weatherunderground':
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says Yaaay, weatherunderground.' );
				if( !isset( $this->config['api_key'] ) || empty( $this->config['api_key'] ) )
				{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says API key not set.' );
					throw new ExternalWeather_Exception( 'Weatherunderground API key not set' );
				}
				// Check if user configured URL for Weather API
				if( isset( $this->config['api_loc'] ) )
				{	// Use the user specified URL (e.g. personal weather station)
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says using config location.' );
					$this->url = $this->config['api_loc'];
				}
				else
				{	// Create URL based on zip code and current conditions
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says using DEFAULT location.' );
					$this->url = 'http://api.wunderground.com/api/' . $this->config['api_key'] . '/forecast/q/' . $zip . '.xml';
				}
				if( !$this->doc = file_get_contents( $this->url ) )
				{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says no worky number 1.' );
					throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
				}
				if( !$this->xml = simplexml_load_string( $this->doc ) )
				{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path config says no worky number 2.' );
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
				}
			break;
		}

		foreach( $this->xml->forecast->simpleforecast->forecastdays as $forecast )
		{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path inside FOREACH number 1.' );
			foreach( $forecast->forecastday as $day )
			{
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path inside FOREACH number 2.' );
				$result[] = $day;
				//$result[] = $day->high->fahrenheit;
				//$result[] = $day->icon_url;
				//$result[] = $day->high->celsius;
			}
		}
$log->logDebug( 'ExternalWeather: getOutdoorForcast: Execution path all done.' );
		return $result;

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