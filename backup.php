<?php
define( '__ROOT__', dirname(__FILE__) );
require_once( __ROOT__ . '/config.php' );


function backupOneTable( $tableName )
{
	global $dbConfig;

	$now = date( 'Y-m-d-H-i', time() );

	$command = "mysqldump -u {$dbConfig['username']} -p{$dbConfig['password']} -h {$dbConfig['host']} {$dbConfig['db']} {$tableName} | gzip -9 - > backup/$now.{$tableName}.sql.gz";

	echo '<br>Starting backup for ' . $tableName;
	//echo '<br>' . $command;

	// Need a try/catch around this
	exec( $command );
	// And only say "Complete" if it worked - otherwise say "Fail"

	echo '<br> Complete';
}

function backupAllTables()
{
	$tableList = array( 'hvac_cycles', 'hvac_status', 'run_times', 'temperatures', 'thermostats', 'time_index' );
	foreach(  $tableList as $tableName )
	{
		backup(  $dbConfig['table_prefix'] . $tableName );
	}
}


?>