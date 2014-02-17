<?php
require_once( 'common.php' );


function backupOneTable( $tableName, $now )
{
	global $log;
	global $dbConfig;
	global $rootDir;

	$command = "mysqldump -u {$dbConfig['username']} -p{$dbConfig['password']} -h {$dbConfig['host']} {$dbConfig['db']} {$tableName} | gzip -9 - > {$rootDir}backup/$now.{$tableName}.sql.gz";
	//$command = "mysqldump -u {$dbConfig['username']} -p{$dbConfig['password']} -h {$dbConfig['host']} {$dbConfig['db']} {$tableName} > {$rootDir}backup/$now.{$tableName}.sql";

	// Be careful, this log command writes your DB password!
//	$log->logInfo( "backup: backupOneTable: Trying backup using\n" . $command );
	// Be careful, this log command writes your DB password!

	// Maybe need a try/catch around this?
	$rv = exec( $command );

	if( $rv != 0 )
	{
		$log->logInfo( "backup: backupOneTable: Backup failed with $rv." );
	}

/* Technically works, but is ugly (not like tar)
	// Concatenate the .sql to the gzip
	$command = "gzip -c {$rootDir}backup/$now.{$tableName}.sql >> {$rootDir}backup/{$dbConfig['table_prefix']}.$now.gz";
$log->logInfo( 'backup: backupOneTable: Trying to concatenate with ' . $command );
	$rv = exec( $command );
*/

	return $rv;
}

function backupAllTables()
{
	global $log;
	global $dbConfig;
	global $rootDir;

	$now = date( 'Y-m-d-H-i', time() );

	$log->logInfo( "backup: backupAllTables: Backup starting." );
	$tableList = array( 'hvac_cycles', 'hvac_status', 'run_times', 'setpoints', 'temperatures', 'thermostats', 'time_index' );
	foreach(  $tableList as $tableName )
	{
		$log->logInfo( "backup: backupAllTables: Backup starting for table: (" . $dbConfig['table_prefix'] . $tableName . ")" );
		backupOneTable( $dbConfig['table_prefix'] . $tableName, $now );
	}
}
?>