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


$sql = "ALTER TABLE {$new_prefix}temperatures
					DROP set_point";
$query = $pdo->prepare($sql);
$query->execute(array($id));

?>