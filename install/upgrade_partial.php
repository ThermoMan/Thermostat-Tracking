<?php
require_once '../common.php';

/**
  * This code upgrades from "version 1" code to "version 2" code.  Since there was no real clear version 1
  *  you may need to tweak the SELECT side of the queries.
  *
  *
  */


/**
  * Specifically avoiding use of $dbConfig['table_prefix'] because you might run this
  *  before or after you update your config.php file
  *
  * Manually edit these before running;
  */
$old_prefix = 'thermo__';
$new_prefix = 'thermo2__';
$tstat_uuid = '5cdad4276ec5';

$sql = "INSERT INTO {$new_prefix}hvac_cycles( tstat_uuid, system, start_time, end_time )
        SELECT '{$tstat_uuid}', system, start_time, end_time
        FROM {$old_prefix}hvac_cycles
        ORDER BY start_time, system";
$query = $pdo->prepare($sql);
$query->execute(array($id));


exit;
