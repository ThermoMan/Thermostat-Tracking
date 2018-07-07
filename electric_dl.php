<?php
require( 'common.php' );

$draw = (isset($_REQUEST['draw'])) ? $_REQUEST['draw'] : 1;
$start = (isset($_REQUEST['start'])) ? $_REQUEST['start'] : 0;
$length = (isset($_REQUEST['length'])) ? $_REQUEST['length'] : 10;
if( $length == -1 ){ $length = count( $foo_json['data'] ); }
$search = (isset($_REQUEST['search'])) ? $_REQUEST['search'] : null;

// Bandaid to keep things moving
$database = new Database();
$pdo = $database->dbConnection();

// Filtering
$sql_filter = ' WHERE mtu_id = 1 '; // Presently hard coded to #1, later use the mtu_id for the present user

// Get total count
$stmt = $pdo->prepare( 'SELECT COUNT(*) FROM elec__elec_usage ' . $sql_filter );
$stmt->execute();
$totalCount = $stmt->fetch( PDO::FETCH_COLUMN, 0 );

if( strlen( $search[ 'value' ] ) > 0 ){
  // Need to turn this into a bind variable
  $sql_filter = $sql_filter . ' AND date_format( date, "%Y/%m/%d %H:%i" ) LIKE "%' . $search[ 'value' ] . '%" ';
}

// Get filtered count
$stmt = $pdo->prepare( 'SELECT COUNT(*) FROM elec__elec_usage ' . $sql_filter );
$stmt->execute();
$filterCount = $stmt->fetch( PDO::FETCH_COLUMN, 0 );

// Ordering
//$sql_order = ' ORDER BY date ASC ';
$sql_order = ' ORDER BY date DESC ';

// Paging support
// Turn these into bind variables
$sql_limit = ' LIMIT '.intval( $start ).', '.  intval( $length ) . ' ';
// As of Oracle 12c (I just learned this too) this would be something like
// sql_limit = ' OFFSET ' || ln_start || ' ROWS FETCH NEXT ' || ln_length || ' ROWS ONLY ';
// But I don't have a local Oracle so I can't try it.

$sql_string = '
  SELECT mtu_id
        ,date_format( date, "%Y/%m/%d %H:%i" )
        ,watts
        ,volts
    FROM elec__elec_usage' . $sql_filter . $sql_order . $sql_limit;

$stmt = $pdo->prepare( $sql_string );
$stmt->execute();

$allData = $stmt->fetchAll( PDO::FETCH_NUM );

echo '{';
echo '"draw": ' . $draw;
echo ',"recordsTotal": ' . $totalCount;
echo ',"recordsFiltered": ' . $filterCount;
echo ',"data": ';
echo json_encode( $allData );
echo '}';

?>