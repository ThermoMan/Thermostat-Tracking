<?php
require_once( 'common.php' );


function backupOneTable( $tableName )
{
	global $dbConfig;

	$now = date( 'Y-m-d-H-i', time() );

	$command = "mysqldump -u {$dbConfig['username']} -p{$dbConfig['password']} -h {$dbConfig['host']} {$dbConfig['db']} {$tableName} | gzip -9 - > backup/$now.{$tableName}.sql.gz";
	logIt( 'Trying backup using\n' . $command );

	//echo '<br>Starting backup for ' . $tableName;
	//echo '<br>' . $command;

	// Need a try/catch around this
	exec( $command );
	// And only say "Complete" if it worked - otherwise say "Fail"

	//echo '<br> Complete';
}

function backupAllTables()
{
	global $dbConfig;

	$tableList = array( 'hvac_cycles', 'hvac_status', 'run_times', 'temperatures', 'thermostats', 'time_index' );
	foreach(  $tableList as $tableName )
	{
		backupOneTable(  $dbConfig['table_prefix'] . $tableName );
	}
}

$returnString = '';
try
{
	backupAllTables();
	$returnString = 'Backup successful.';
}
catch( Exception $e )
{
	$returnString = 'Backup failed.';
}

echo $returnString;
?>