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


/**
  * Step 1 is ALWAYS ... "Make a backup before you do anything!"
  *
  * There are five total tables, technically only 3 need a backup.
  */

$file = 'backup_hvac_cycles.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$old_prefix}hvac_cycles";
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

/* This is the way I'd like to do it, but it doesn't work without file permision for the MySQL user ID
$file = 'backup_hvac_status.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$old_prefix}hvac_status";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$file = 'backup_run_times.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$old_prefix}run_times";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$file = 'backup_temperatures.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$old_prefix}temperatures";
$query = $pdo->prepare($sql);
$query->execute(array($id));

// This table is included for completness only
$file = 'backup_time_index.sql';
$sql = "SELECT * INTO OUTFILE {$file} FROM {$old_prefix}time_index";
$query = $pdo->prepare($sql);
$query->execute(array($id));
 */

// Output CSV, with EVERY field INCLUDING NUMERICS enclosed with a new line for each row.
function fputcsv2( $fh, array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false )
{
  $delimiter_esc = preg_quote( $delimiter, '/' );
  $enclosure_esc = preg_quote( $enclosure, '/' );

  $output = '';
  foreach( $fields as $row )
  {
    foreach( $row as $field )
    {
      if( $field === null && $mysql_null )
      {
        $output .= 'NULL';
        continue;
      }
      else
      {
        $output .= preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s|\d)/", $field ) ? (
            $enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure
        ) : $field;
      }
      $output .= $delimiter;
    }
    $output = substr_replace( $output, PHP_EOL, -1 );
  }

  fwrite( $fh, $output  );
}

function dump_table( $table_name, $conditional = NULL, $date_stamp = NULL )
{
  if( $date_stamp == NULL ) $date_stamp = date( 'YmdHi' );
  $full_path = '/home/fratell1/freitag.theinscrutable.us/thermo2/backup/';
  $file_name = $full_path . 'backup_' . $table_name . '.' . $date_stamp . '.csv';

  global $old_prefix;
  global $pdo;

/**
  * This is the best way to do it, but if your host doesn't give you the file permission, you can't.
  *
  * $sql = "SELECT *
  *         INTO OUTFILE '{$file_name}'
  *         FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n'
  *         FROM {$old_prefix}{$table_name}";
  */

  $sql = "SELECT * FROM {$old_prefix}{$table_name}";
  if( $conditional != NULL ) $sql .= ' ' . $conditional;

  $query = $pdo->prepare( $sql );
  $query->execute();
  $result = $query->fetchAll( PDO::FETCH_ASSOC );

  $fp = fopen( $file_name, 'w' );
  fputcsv2( $fp, $result );
  fclose( $fp );

  return;
}

$backup_date = date( 'YmdHi' ); // Use one date for all tables in this backup set.
dump_table( 'hvac_cycles',  'ORDER BY start_time', $backup_date );
dump_table( 'hvac_status',  'ORDER BY date', $backup_date );
dump_table( 'run_times',    'ORDER BY date', $backup_date );
dump_table( 'temperatures', 'ORDER BY date', $backup_date );
dump_table( 'time_index',   'ORDER BY time', $backup_date );





/**
  * Create the new tables
  *
  */

$sql = "CREATE TABLE IF NOT EXISTS thermostats (
          id tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
          tstat_uuid varchar(15) DEFAULT NULL,
          model varchar(10) DEFAULT NULL,
          fw_version varchar(10) DEFAULT NULL,
          wlan_fw_version varchar(10) DEFAULT NULL,
          ip varchar(128) NOT NULL,
          name varchar(254) DEFAULT NULL,
          description varchar(254) DEFAULT NULL,
          PRIMARY KEY( id ),
          KEY name( name )
        ) ENGINE = InnoDB  DEFAULT CHARSET = utf8 AUTO_INCREMENT = 2";
        
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}hvac_cycles (
          tstat_uuid varchar(15) NOT NULL,
          system smallint(6) NOT NULL,
          start_time datetime NOT NULL,
          end_time datetime NOT NULL,
          PRIMARY KEY( tstat_uuid )
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}hvac_status (
          tstat_uuid varchar(15) NOT NULL,
          date datetime NOT NULL,
          start_date_fan datetime DEFAULT NULL,
          start_date_cool datetime DEFAULT NULL,
          start_date_heat datetime DEFAULT NULL,
          heat_status tinyint(1) NOT NULL,
          cool_status tinyint(1) NOT NULL,
          fan_status tinyint(1) NOT NULL,
          PRIMARY KEY( tstat_uuid, date )
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}run_times (
          tstat_uuid varchar(15) NOT NULL,
          date date NOT NULL,
          heat_runtime smallint(6) NOT NULL,
          cool_runtime smallint(6) NOT NULL,
          PRIMARY KEY( tstat_uuid, date )
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}run_times (
          tstat_uuid varchar(15) NOT NULL,
          date date NOT NULL,
          heat_runtime smallint(6) NOT NULL,
          cool_runtime smallint(6) NOT NULL,
          PRIMARY KEY( tstat_uuid, date )
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8";
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
  				REFERENCES thermo2__thermostats (id)";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}temperatures (
          tstat_uuid varchar(15) NOT NULL,
          date datetime NOT NULL,
          indoor_temp decimal(5,2) NOT NULL,
          outdoor_temp decimal(5,2) DEFAULT NULL,
          indoor_humidity decimal(5,2) DEFAULT NULL,
          outdoor_humidity decimal(5,2) DEFAULT NULL,
          PRIMARY KEY( tstat_uuid, date )
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "CREATE TABLE IF NOT EXISTS {$new_prefix}time_index (
          time time NOT NULL
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8";
$query = $pdo->prepare($sql);
$query->execute(array($id));


/**
  * There are 7 tables that need setup.
  *
  * Since there are no DB enforced primary/foreign key constraints the order of update is not important.
  *
  * If your date has duplicates in it, you WILL have problems.  But you have a backup already, so
  *  manually delete the dupes and then run these updates
  *
  */

$sql = "INSERT INTO {$new_prefix}thermostats( ip ) VALUES ( 'YOUR THERMOSTAT UP HERE:PORT' )";
$query = $pdo->prepare($sql);
$query->execute(array($id));

/**
  * Since the data collection code also gets yesterdays run times, you may have a case of one day
  *  overlap / duplicate here.
  * The solution may be to modify this code to select from the old table where not already in the
  *  new table.
  */
$sql = "INSERT INTO {$new_prefix}run_times( tstat_uuid, date, heat_runtime, cool_runtime )
        SELECT '{$tstat_uuid}', date, heat_runtime, cool_runtime
        FROM {$old_prefix}run_times
        ORDER BY date";
$query = $pdo->prepare($sql);
$query->execute(array($id));

// hvac_status does NOT need an import because it has changed function between v1 and v2

$sql = "INSERT INTO {$new_prefix}hvac_cycles( tstat_uuid, system, start_time, end_time )
        SELECT '{$tstat_uuid}', system, start_time, end_time
        FROM {$old_prefix}hvac_cycles
        ORDER BY start_time, system";
$query = $pdo->prepare($sql);
$query->execute(array($id));


$sql = "INSERT INTO {$new_prefix}temperatures( tstat_uuid, date, indoor_temp, outdoor_temp, indoor_humidity, outdoor_humidity )
        SELECT '{$tstat_uuid}', date, indoor_temp, outdoor_temp, 'VOID', 'VOID'
        FROM {$old_prefix}temperatures
        ORDER BY date";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$sql = "INSERT INTO {$new_prefix}time_index( time ) VALUES
        ('00:00:00'), ('00:30:00'), ('01:00:00'), ('01:30:00'), ('02:00:00'), ('02:30:00'), ('03:00:00'), ('03:30:00'),
        ('04:00:00'), ('04:30:00'), ('05:00:00'), ('05:30:00'), ('06:00:00'), ('06:30:00'), ('07:00:00'), ('07:30:00'),
        ('08:00:00'), ('08:30:00'), ('09:00:00'), ('09:30:00'), ('10:00:00'), ('10:30:00'), ('11:00:00'), ('11:30:00'),
        ('12:00:00'), ('12:30:00'), ('13:00:00'), ('13:30:00'), ('14:00:00'), ('14:30:00'), ('15:00:00'), ('15:30:00'),
        ('16:00:00'), ('16:30:00'), ('17:00:00'), ('17:30:00'), ('18:00:00'), ('18:30:00'), ('19:00:00'), ('19:30:00'),
        ('20:00:00'), ('20:30:00'), ('21:00:00'), ('21:30:00'), ('22:00:00'), ('22:30:00'), ('23:00:00'), ('23:30:00'),
        ('24:00:00')";
$query = $pdo->prepare($sql);
$query->execute(array($id));


/**
  * Now remove the old tables.
  *
  * The following code is commented out to prevent accidents.  Do not remove the old tables until you have
  *  verified that the new tables are working both for new data insert AND that import of old data
  *  is complete to your satisfaction.
  *
  * $sql = "DROP TABLE {$old_prefix}hvac_cycles";
  * $query = $pdo->prepare($sql);
  * $query->execute(array($id));
  *
  * $sql = "DROP TABLE {$old_prefix}hvac_status";
  * $query = $pdo->prepare($sql);
  * $query->execute(array($id));
  *
  * $sql = "DROP TABLE {$old_prefix}run_times";
  * $query = $pdo->prepare($sql);
  * $query->execute(array($id));
  *
  * $sql = "DROP TABLE {$old_prefix}temperatures";
  * $query = $pdo->prepare($sql);
  * $query->execute(array($id));
  *
  * $sql = "DROP TABLE {$old_prefix}time_index";
  * $query = $pdo->prepare($sql);
  * $query->execute(array($id));
  *
  */

/* FOR REFERENCE ONLY - DO NOT RUN THIS
$sql = "TRUNCATE TABLE {$new_prefix}hvac_cycles";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$sql = "TRUNCATE TABLE {$new_prefix}hvac_status";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$sql = "TRUNCATE TABLE {$new_prefix}run_times";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$sql = "TRUNCATE TABLE {$new_prefix}temperatures";
$query = $pdo->prepare($sql);
$query->execute(array($id));

$sql = "TRUNCATE TABLE {$new_prefix}time_index";
$query = $pdo->prepare($sql);
$query->execute(array($id));
*/
