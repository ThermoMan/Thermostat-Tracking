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

	public function getOutdoorWeather( $zip = null )
	{
		if( empty($zip) )
		{
			throw new ExternalWeather_Exception( 'Zip not set' );
		}

		$outdoorTemp = false;
		$outdoorHumidity = false;

		switch( $this->config['type'] )
		{
			default:
			case 'noaa':
				if( !isset($this->config['api_loc']) || empty($this->config['api_loc']) )
				{
					throw new ExternalWeather_Exception( 'NOAA API loc not set' );
				}
				if( !$doc = file_get_contents($this->config['api_loc']) )
				{
					throw new ExternalWeather_Exception( 'Could not contact noaa xml' );
				}
				if( !$xml = simplexml_load_string($doc) )
				{
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
				}
				if( $this->config['units'] == 'C' )
				{
					$outdoorTemp = $xml->temp_c;
				}
				else
				{
					$outdoorTemp = $xml->temp_f;
				}
				$outdoorHumidity = $xml->relative_humidity;
			break;

			case 'weatherbug':
				if( !isset($this->config['api_key']) || empty($this->config['api_key']) )
				{
					throw new ExternalWeather_Exception( 'Weatherbug API key not set' );
				}
				// Check if user configured URL for Weather API
				if ( isset($this->config['api_loc']) )
				{	// Use the user specified URL (e.g. personal weather station)
					$url = $this->config['api_loc'];
				}
				else
				{	// Create URL based on zip code and current conditions
					$url = 'http://api.wxbug.net/getLiveWeatherRss.aspx?ACode=' . $this->config['api_key'] . '&zipcode=' . $zip . '&unittype=0';
				}

				if( !$doc = file_get_contents($url) )
				{
					throw new ExternalWeather_Exception( 'Could not contact weatherbug api' );
				}
				if( !$xml = simplexml_load_string($doc) )
				{
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
				}
				$aws = $xml->channel->children( 'http://www.aws.com/aws' );

				$temporary_catch = $aws->weather->ob->temp;
				if( $this->config['units'] == 'C' )
				{ // Weatherbug API only supports units in F, not C.	Force conversion.
					$outdoorTemp = ((9 * $temporary_catch) / 5) + 32;
				}
				else
				{
					$outdoorTemp = $temporary_catch;
				}

				$outdoorHumidity = $aws->weather->ob->humidity;
			break;

			case 'weatherunderground':
				if( !isset($this->config['api_key']) || empty($this->config['api_key']) )
				{
					throw new ExternalWeather_Exception( 'Weatherunderground API key not set' );
				}

				// Check if user configured URL for Weather API
				if ( isset($this->config['api_loc']) )
				{	// Use the user specified URL (e.g. personal weather station)
					$url = $this->config['api_loc'];
				}
				else
				{	// Create URL based on zip code and current conditions
					$url = 'http://api.wunderground.com/api/' . $this->config['api_key'] . '/conditions/q/' . $zip . '.xml';
				}
				if( !$doc = file_get_contents( $url ) )
				{
					throw new ExternalWeather_Exception( 'Could not contact weatherunderground weather api' );
				}
				if( !$xml = simplexml_load_string( $doc ) )
				{
					throw new ExternalWeather_Exception( 'Could not parse XML: ' . $doc );
				}
				if( $this->config['units'] == 'C' )
				{
					$outdoorTemp = $xml->current_observation->temp_c;
				}
				else
				{
					// If it's not C assume it is F (what, you want Kelvin or Rankine?)
					$outdoorTemp = $xml->current_observation->temp_f;
				}
				$outdoorHumidity = $xml->current_observation->relative_humidity;
				$outdoorHumidity = str_replace('%', '', $outdoorHumidity);
			break;
		}
		return array('temp' => $outdoorTemp, 'humidity' => $outdoorHumidity);
	}
}