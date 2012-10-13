<?php
require_once 'common.php';
include( 'lib/pChart2.1.3/class/pData.class.php' );
include( 'lib/pChart2.1.3/class/pDraw.class.php' );
include( 'lib/pChart2.1.3/class/pImage.class.php' );

// Replaces chart with anti-hacking graphic (usually when web user has used a mal-formed date string)
function bobby_tables()
{
  $filename = './images/exploits_of_a_mom.png';
  $handle = fopen( $filename, 'r' );
  $contents = fread( $handle, filesize($filename) );
  fclose( $handle );
  echo $contents;
}

function validate_date( $some_date )
{
  $date_pattern = "/[2]{1}[0]{1}[0-9]{2}-[0-9]{2}-[0-9]{2}/";
  if( !preg_match( $date_pattern, $some_date ) || strlen($some_date) != 10)
  {	// I want it to be EXACTLY YYYY-MM-DD
    bobby_tables();
    return false;
  }
  return true;
}


// Common code that should run for EVERY CHART page follows here
$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : null;    // Set id to chosen thermost (or null if not chosen)
if( $id == null )
{ // If the thermostat to display was not chosen, choose one
  $thermostat = array_pop($thermostats);
  if( is_array($thermostat) && isset($thermostat['id']))
  {
    $id = $thermostat['id'];
  }
}
if( $id == null )
{ // If there still is not one chosen then abort (instead of printing text that won't show up, display an error image that will)
    print 'Thermostat not found.';
    return;
}

// Having now chosen a thermostat to display, gather information about it.
$sql = "SELECT tstat_uuid, name FROM {$dbConfig['table_prefix']}thermostats WHERE id = ?";
$query = $pdo->prepare( $sql );
$query->execute( array( $id ) );
$thermData = $query->fetchAll();
if( !isset($thermData[0]['tstat_uuid']) || empty($thermData[0]['tstat_uuid']))
{ // If the chosen thermostat is not known to the system then abort (instead of printing text that won't show up, display an error image that will)
    print 'Thermostat not found.';
    return;
}
$uuid = $thermData[0]['tstat_uuid'];
$statName = $thermData[0]['name'];


?>