<?php


if (version_compare(PHP_VERSION, '5.2.4') < 0)
{ // Check php version.  I'm not sure there is a minimum requirement, but be safe.  (This logic is borrowed from phpBB installer)
  die('You are running an unsupported PHP version. Please upgrade to PHP 5.2.4 or higher before trying to 3M-50 Thermostat Tracking');
}

$software = '3M-50-Thermostat-Tracking';
// The software is actually version 2.0, but the addition of the installer and some changes to the charts warrant a new number.
$version = 'v2.1.1';
echo "This script will install 3M-50 Thermostat Tracking software version $version";
// The hard coded version number in this file should match the label applied in git.

// This script needs to re-runnable during one session without having to delete the previous work and start from scratch
// So it needs a state flag to see how far it has progressed
// Set the flag to a numeric value based on how many steps are to be done and decrement when one is complete.  When you get to zero, you're done.
$install_total_steps = 10;
$install_present_step = 10;
echo "<br>You are on $install_present_step of $install_total_steps";
// Perhaps make a sub-routine of each step so that the skeleton of the install process is easily seen.


// Should this install script create a log file?  It might make troubleshooting in cases of failure easier.

// Reset fatal_error flag and check for presence of all required files.
function check_dir( $dirname )
{
  if( !is_file( $dirname ) )
  {
    echo "<br>The directory $dirname is missing, please check your download for completeness.";
    $fatal_error = $fatal_error + 1;
  }
}
function check_file( $filename )
{
  if( !is_file( $filename ) )
  {
    echo '<br>The file $filename is missing, please check your download for completeness.';
    $fatal_error = $fatal_error + 1;
  }
}
// Check for presence of expected directories (pChart directory must be preset, but do not check for every file in there)
$fatal_error = 0;
check_dir( '../images' ); // This is separate from the resources directory to make moving to other static object easier later on.
check_dir( '../install' );
check_dir( '../backup' );
check_dir( '../scripts' );
check_dir( '../lib' );
check_dir( '../logs' );
check_dir( '../resources' );  // Place keeper for CSS and JavaScript files

// Check for presence of expected files (check for every file that is unique to this program)
check_file( '../install/index.php' ); // This file (you’re making sure it's in the right place, because duh, it exists)
check_file( '../draw_daily.php' );
check_file( '../draw_weekly.php' );
check_file( '../index.php' );
check_file( '../favicon.ico' );
check_file( '../README' );
check_file( '../scripts/thermo_update_status.bat' );
check_file( '../scripts/thermo_update_status.ksh' );
check_file( '../scripts/thermo_update_status.php' );
check_file( '../scripts/thermo_update_temps.bat' );
check_file( '../scripts/thermo_update_temps.ksh' );
check_file( '../scripts/thermo_update_temps.php' );
check_file( '../images/exploits_of_a_mom.png' );
check_file( '../lib/t_lib.php' );
if( $fatal_error > 0 )
{
  echo '<br>Some of the errors that were detected will prevent this software from working.  Please correct them and try again.';
  exit();
}


// Reset fatal_error flag and check for errors in SQL stuff

// Ask for database connection info (server name, port, user ID, password)
$default_db_server_name = 'localhost';
$default_db_port = '3306';
$default_db_user = 'user';
$default_db_password = 'password';

// Test that connection works
// Test that ID has permission to create tables
// Test that ID has permission to insert, update, delete data

// The SQL should test for presence AND structure of the tables before simply adding them.
// (future) The installer should look at version number in teh tables and know how to update them if needed.
// The SQL should be a in a separate .sql text file and NOT be hard coded into this .php file.
// Before running the SQL, the name of the database and the prefix (if any) should be determined and applied to a template of the SQL.


// The DB to create.
$default_db_name = 'thermo2';

// Actually '' might be a better default, only need to add a prefix when name collision will occur or when you want the names to fall together alphabetically
$default_db_object_prefix = 'thermo__';
// But, using two underscores in the prefix activates some handy magic in phpMyAdmin.

$replace_list = array(
'**REPLACE_DB_NAME**' => $db_name,
'**REPLACE_OBJECT_PREFIX**' => $db_object_prefix
);

function replace_names( $filename, $replace_list )
{
  // Open the file
  // Read in the file (it is expected to be reasonably sized, not some monstrous thing)
  // Find instances of text from column 1 and replace them with text from column 2
  // Write out the file using the filename minus the ".IN" suffix as the new name (create_tables.sql.IN becomes create_tables.sql)
  //   Do overwrite without warning any previous file with that name.
}

// Create the file 'create_tables.sql' from the file 'create_tables.sql.IN'
replace_names( 'create_tables.sql.IN', $replace_list );

check_file( '../install/create_tables.sql' );
run_sql( 'create_tables.sql' );
// On successful run, copy the generated SQL to the log file so that even after the install directory is deleted it's there for reference.


// Need a meta data table that contains the version number in case future installs need to alter the table to update.
$sql = "insert into " . $db_object_prefix . "meta ( key, value ) values ( \"version\", $version )";
// Run that SQL (The meta table does not yet exist!)
// This is the INSTALL script so we can assume there is no pre-existing value to deal with.  In the future an update.php type script will have to care.

// Reset fatal_error flag and test thermostat config

// Ask for thermostat config information (IP address, etc...)
// Communicate with thermostat and get firmware version number (as proof of connection)
// Do I need to check the stat firmware (or API) revision level for compatibility?


// Reset fatal_error flag and write the config.php file

// Collect the rest of the info to put in config.php (ZIP code, timezone, etc...)
// Default config.php should contain a version number that matches the one in the table
// The hard coded version number in this file should match the label applied in git.


// Reset fatal_error flag and try to kick off the scheduled events

// Ask for scheduler info (windows ID, password)  Might be same as ID that runs Apache?
// Create both task schedules
// Check the schedules to see if they are in there.
// Wait one minute to see if the status update has created a record "select count(*) from ..." should come back with a number > 0

echo "<br>You have successfully installed the $software software.  It is collecting data and will have useful charts available in a a few hours.";
echo '<br>Please delete the /install directory before continuing';
// Add routines to the other .php files that block execution if the /install directory is still present.

?>