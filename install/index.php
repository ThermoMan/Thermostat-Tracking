<?php

if (version_compare(PHP_VERSION, '4.3.3') < 0)
{ // Check php version.  I'm not sure there is a minimum requirement, but be safe.  (This logic is borrowed from phpBB installer)
  die('You are running an unsupported PHP version. Please upgrade to PHP 4.3.3 or higher before trying to 3M-50 Thermostat Tracking');
}

// Reset fatal_error flag and check for presence of all required files.
$fatal_error = 0;
function check_file( $filename )
{
  if( !is_file( $filename ) )
  {
    echo "<br>The file $filename is missing, please check your download for completeness.";
    $fatal_error = $fatal_error + 1;
  }
}
// Check for presence of expected directories (pChart directory must be preset, but do not cehck for every file in there)
// Check for presence of expected files (check for every file that is unique to this program)
check_file( "../index.php" );
if( $fatal_error > 0 )
{
  echo "Some of the errors that were detected will prevent this software from working.  Please correct them and try again.";
  exit();
}


// Reset fatal_error flag and cehck for errors in SQL stuff

// Ask for database connection info (server name, port, user ID, password)
// Test that connection works
// Test that ID has permission to create tables
// Test that ID has permission to insert, update, delete data

// The SQL should test for presence of the tables before simply adding them.
// Should the SQL be a in a separate .sql text file or shoulkd it be coded into this .php file?

// Need a meta data table that contains the version number in case future installs need to alter the table to update.


// Reset fatal_error flag and test thermostat config

// Ask for thermostat config information (IP address, etc...)
// Communicate with thermostat and get firmware version number (as proof of connection)
// Do I need to check the stat firmware revision level for compatibility?


// Reset fatal_error flag and write the config.php file

// Collect the rest of the info to put in config.php (ZIP code, timezone, etc...)
// Default config.php should contain a version number that matches the one in the table
// This file should have a hard coded version number that matches the label applied in git.


// Reset fatal_error flag and write the config.php file

// Ask for scheduler info (windows ID, password)  Might be same as ID that runs Apache?
// Create both task schedules
// Check the schedules to see if they are in there.
// Wait one minute to see if the status update has created a record "select count(*) from ..." should come back with a number > 0


// Let user know that he shuold wait several hours before a useful amount of data will have accumulated, but that collection is starting immediatly.

?>