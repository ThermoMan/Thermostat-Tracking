<?php
require_once '../common.php';

/**
	* This code upgrades "version 2" code for the setpoints addition.
	*/

$new_prefix = 'thermo2__';
//$tstat_uuid = '5cdad4276ec5';


/**
	* Step 1 is ALWAYS ... "Make a backup before you do anything!"
	*
	* There are six total tables.
	*/

$file = 'backup_hvac_cycles.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$new_prefix}hvac_cycles";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$file = 'backup_hvac_status.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$new_prefix}hvac_status";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$file = 'backup_run_times.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$new_prefix}run_times";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$file = 'backup_temperatures.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$new_prefix}temperatures";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$file = 'backup_thermostats.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$new_prefix}thermostats";
$query = $pdo->prepare($sql);
$query->execute(array($id));

// This table is included for completness only
$file = 'backup_time_index.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$new_prefix}time_index";
$query = $pdo->prepare($sql);
$query->execute(array($id));

/**
	* The following code block is an example of how to restore from a backup - perhaps, it is untested
	*
	* $file = 'backup_hvac_cycles.sql';
	* $sql = "LOAD DATA INFILE {$file} INTO TABLE {$old_prefix}hvac_cycles";
	* $query = $pdo->prepare($sql);
	* $query->execute(array($id));
	*
	* Both export and import are generic enough that a function could be written and
	*  the table name could be passed in as a single parameter and the rest dynamically
	*  generated.
	*/


/**
	* Create the new tables
	*
	*/

$sql = "ALTER TABLE {$new_prefix}thermostats
					MODIFY id TINYINT unsigned NOT NULL";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}setpoints (
					id tinyint(3) unsigned NOT NULL,
					set_point decimal(5,2) DEFAULT NULL,
					switch_time datetime NOT NULL,
					KEY id (id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "ALTER TABLE {$new_prefix}setpoints
					ADD CONSTRAINT {$new_prefix}setpoints_ibfk_1
					FOREIGN KEY (id)
					REFERENCES {$new_prefix}thermostats (id)";
$query = $pdo->prepare($sql);
$query->execute(array($id));


// Preserve historical set point changes
// Set initial condition of 0.
$last_date = 0;
$last_set_point = 0;

// SQL to examine historical rows
// Need to include uuid so ID substitution can be made for multiple stat situations.
$sql = "SELECT date, set_point FROM thermo2__temperatures ORDER BY date";
$query = $pdo->prepare($sql);

// SQL to insert historical data in new table
$sql = "INSERT INTO thermo2__setpoints( id, set_point, switch_time ) VALUES ( ?, ?, ?) ";
$insertQuery = $pdo->prepare($sql);

$query->execute();

while( $row = $query->fetch( PDO::FETCH_ASSOC ) )
{	// Check every row of data in date order

	if( $row[ 'set_point' ] != $last_set_point )
	{	// When the set point changes from the previous value, there is something to put in the new table

// Using a hard coded "1" here ia a bad idea - some people actually already have TWO stats.  Need to look up the ID
		$insertQuery->execute( array( 1, $last_set_point, $last_date ) );
		//echo "<br>I found an change at $last_date to $last_set_point";

		// Update what the values are
		$last_date = $row[ 'date' ];
		$last_set_point = $row[ 'set_point' ];
	}
}
// I think I need to force feed the last one.
$insertQuery->execute( array( 1, $last_set_point, $last_date ) );
//echo "<br>Force feed change at $last_date to $last_set_point";

// Probably need a manual inspection of the very first and very last row.
// The first one will be all zero and not terrifically useful.

$sql = "ALTER TABLE {$new_prefix}temperatures
					DROP set_point";
$query = $pdo->prepare($sql);
$query->execute(array($id));

?>