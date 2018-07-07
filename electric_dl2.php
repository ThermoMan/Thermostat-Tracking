<?php
require( 'common.php' );

// Bandaid to keep things moving
$database = new Database();
$pdo = $database->dbConnection();

// Filtering
$sql_filter = ' WHERE mtu_id = 1 '; // Presently hard coded to #1, later use the mtu_id for the present user
$sql_filter = $sql_filter . ' AND date BETWEEN "2016/08/01" and "2016/10/31" ';

// This gets the last thirty days
// WHERE date >= (SELECT DATE(MAX(date)) from elec__elec_usage) - INTERVAL 30 DAY

// // Get filtered count
// $stmt = $pdo->prepare( 'SELECT COUNT(*) FROM elec__elec_usage ' . $sql_filter );
// $stmt->execute();
// $filterCount = $stmt->fetch( PDO::FETCH_COLUMN, 0 );

// Ordering
//$sql_order = ' ORDER BY date ASC ';
$sql_order = ' ORDER BY date DESC ';

// Grouping
$sql_group = ' GROUP BY date_format( date, "%Y-%m-%d" ) ';

$sql_string = '
  SELECT date_format( date, "%Y-%m-%d" ) AS date
        ,ROUND( SUM( watts ) / 60000 , 2 ) AS vl
    FROM elec__elec_usage' . $sql_filter . $sql_group;

$stmt = $pdo->prepare( $sql_string );
$stmt->execute();

$allData = $stmt->fetchAll( PDO::FETCH_OBJ );
echo json_encode( $allData );

?>