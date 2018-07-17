<?php
$start_time = microtime(true);
require_once( 'common.php' );
require_once( 'user.php' );
$util::logInfo( 'Start' );

//$util::logDebug( 'THE WORKS ' . json_encode( $_REQUEST ) );
$jsonData = $_REQUEST;
$uname = $jsonData[ 'user' ];
$user_session = $jsonData[ 'session' ];
$request = $jsonData[ 'request' ];
$queryList = $request[ 'query' ];
$response_type = $request[ 'response_type' ];

/*
$util::logDebug( 'THE WORKS ' . var_export( $jsonData, true ) );
$util::logDebug( 'user ' . var_export( $uname, true ) );
$util::logDebug( 'session ' . var_export( $user_session, true ) );
$util::logDebug( 'request ' . var_export( $request, true ) );
$util::logDebug( 'query ' . var_export( $query, true ) );
$util::logDebug( 'response_type ' . var_export( $response_type, true ) );
$bogus = $request[ 'bogus' ];
$util::logDebug( 'bogus ' . var_export( $bogus, true ) ); // Should be NULL and is! (without causing error)
$double_bogus = $bogus[ 'bogus' ];
$util::logDebug( 'double_bogus ' . var_export( $double_bogus, true ) ); // Should be NULL and is! (without causing error)
*/

if( isset( $uname ) && isset( $user_session ) ){
try{
  $user = new USER( $uname, $user_session );
$util::logDebug( 'I got a user!' );
}
catch( Exception $e ){
  $util::logDebug( 'BUG' );
}

  foreach( $user->TED5000_Gateways as $gatewayRec ){
$util::logDebug( 'row has ' . var_export( $gatewayRec ) );
  }
}

$util::logDebug( 'working on queries' );
foreach( $queryList as $query ){
$util::logDebug( 'key is ' . $query['key'] );
$util::logDebug( 'meter is ' . $query['meter'] );
$util::logDebug( 'from_date is ' . $query['from_date'] );
$util::logDebug( 'to_date is ' . $query['to_date'] );
}
$util::logDebug( 'done with queries' );

/* Here is what the request should look like
{
  "user": "userName",
  "session": "sessionID",
  "request": {
    "query": [
      {
        "key": "use",
        "meter": 1,
        "from_date": "2018-07-14 05:50",
        "to_date": "2018-07-14 06:00"
      },
      {
        "key": "gen",
        "meter": 2,
        "from_date": "2018-07-14 05:50",
        "to_date": "2018-07-14 06:00"
      }
    ],
    "reponse_type": "merge"
  }
}
This has the request in an JSON onject.
The query is what is being asked for.  It's always an array even if it has only one element.
The query has four parts
key: the name of the value column to be used in the reponse
meter: the data series to locate
from_date: should be obvious from usage above - in the 24 hour format YYYY-MM-DD HH:mm
to_date:
The response_type is one of "merge", "append", "all"
all: If you make three queries, you get three answers.  It's a way of doing everything in one call instead of making three separate one query requests.
append: The resutls of the second (and subsequent) queries are appended on the end of the first one.
merge: Each query answer will become a new column in the final result - all using the common date key. this will need special logic in case there are gaps in the key sequence


Here is what the response should look like
{
  "response": {
    "request": {
      "query": [
        {
          "key": "use",
          "meter": 1,
          "from_date": "2018-07-14 05:50",
          "to_date": "2018-07-14 06:00"
        },
        {
          "key": "gen",
          "meter": 2,
          "from_date": "2018-07-14 05:50",
          "to_date": "2018-07-14 06:00"
        }
      ],
      "reponse_type": "merge"
    },
    "answer": {
      "your_data": "here"
    }
  }
}
It starts with a re-iteration of the question asked and includes an answer.
Depending on the "response_type" your answer will look like one of the following.
User, Session, and no_cache (if present in original) are NOT included in the reply

An answer from a single query ("response_type" could be "append" or "all")
    "answer": {
      "2018-07-14 05:50": 1,
      "2018-07-14 05:51": 1,
      "2018-07-14 05:52": 1,
      "2018-07-14 05:53": 1,
      "2018-07-14 05:54": 1,
      "2018-07-14 05:55": 1,
      "2018-07-14 05:56": 1,
      "2018-07-14 05:57": 1,
      "2018-07-14 05:58": 1,
      "2018-07-14 05:59": 1,
      "2018-07-14 06:00": 1
    }

An answer from two queries ("response_type" is "merge")
    "answer": {
      "2018-07-14 05:50": [{"use":1},{"gen":2}],
      "2018-07-14 05:51": [{"use":1},{"gen":2}],
      "2018-07-14 05:52": [{"use":1},{"gen":2}],
      "2018-07-14 05:53": [{"use":1},{"gen":2}],
      "2018-07-14 05:54": [{"use":1},{"gen":2}],
      "2018-07-14 05:55": [{"use":1},{"gen":2}],
      "2018-07-14 05:56": [{"use":1},{"gen":2}],
      "2018-07-14 05:57": [{"use":1},{"gen":2}],
      "2018-07-14 05:58": [{"use":1},{"gen":2}],
      "2018-07-14 05:59": [{"use":1},{"gen":2}],
      "2018-07-14 06:00": [{"use":1},{"gen":2}]
    }

An answer from two queries ("response_type" is "append").  Note that by the rules of JSON if the key column contains literally identical
 values like the example below the subsequent appearance of a key will overwrite the values in the earlier appearance.
    "answer": {
      "2018-07-14 05:50": 1,
      "2018-07-14 05:51": 1,
      "2018-07-14 05:52": 1,
      "2018-07-14 05:53": 1,
      "2018-07-14 05:54": 1,
      "2018-07-14 05:55": 1,
      "2018-07-14 05:56": 1,
      "2018-07-14 05:57": 1,
      "2018-07-14 05:58": 1,
      "2018-07-14 05:59": 1,
      "2018-07-14 06:00": 1,
      "2018-07-14 05:50": 2,
      "2018-07-14 05:51": 2,
      "2018-07-14 05:52": 2,
      "2018-07-14 05:53": 2,
      "2018-07-14 05:54": 2,
      "2018-07-14 05:55": 2,
      "2018-07-14 05:56": 2,
      "2018-07-14 05:57": 2,
      "2018-07-14 05:58": 2,
      "2018-07-14 05:59": 2,
      "2018-07-14 06:00": 2
    }
In your query, if you don't want data over write make sure the query key date ranges do not overlap!
*/




// Get ending date for chart
$to_date = (isset($_GET['chart_daily_toDate'])) ? htmlspecialchars( $_GET['chart_daily_toDate'] ) : date( 'Y-m-d' );
if( ! $util::isValidDate( $to_date, 'Y-m-d' ) ){
  throw new Config_Exception( 'electric_dl: error 4.  check logs.' );
$util::logInfo( "should NEVER see this message in logs!" );
}
//$to_date = date( 'Y-m-d 00:00', strtotime( "$to_date + 1 day" ) );
$to_date = date( 'Y-m-d 00:00', strtotime( "$to_date" ) );

// Verify that date is not future?

$interval_measure = (isset($_GET['chart_daily_interval_group'])) ? $_GET['chart_daily_interval_group'] : 0;
if( $interval_measure < 0 || $interval_measure > 3 ){
  // 0: minutes, 1: hours, 2: days, 3: weeks, 4: years
  $interval_measure = 0;
}

if( isset( $_GET['chart_daily_interval_length'] ) ){
  $interval_length = $_GET['chart_daily_interval_length'];

  // Bounds checking
  if( $interval_length < 1 ) $interval_length = 1;
  if( $interval_length > 1096 ) $interval_length = 1096;
}

$date_text = array( 0 => 'minutes', 1 => 'hours', 2 => 'days', 3 => 'weeks', 3 => 'years' );
$interval_string = $to_date . ' - ' . $interval_length . ' ' . $date_text[$interval_measure];

// Compute the "from date"
$from_date = date( 'Y-m-d %H:%i', strtotime( $interval_string ) );

/*
$util::logDebug( "mtu_id = $mtu_id" );
$util::logDebug( "from_date = $from_date" );
$util::logDebug( "to_date = $to_date" );
$util::logDebug( "interval_string = $interval_string" );
$util::logDebug( "interval_length = $interval_length" );
$util::logDebug( "interval_measure = $interval_measure" );
*/

$database = new Database();
$pdo = $database->dbConnection();

// QQQ Need to test that mtu_id belongs to user_id
// QQQ Should start IDs from number other than 1!


$allData = array();

$sqlGetAllData =
"SELECT mtu_id
       ,date_format( date, '%Y/%m/%d %H:%i' )
       ,watts
       ,volts
   FROM {$database->table_prefix}meter_data
  WHERE mtu_id = :mtu_id
    AND date BETWEEN :from_date AND :to_date";

$util::logDebug( "sqlGetAllData = $sqlGetAllData" );

$queryGetAllData = $pdo->prepare( $sqlGetAllData );
$queryGetAllData->bindParam( ':mtu_id', $mtu_id );
$queryGetAllData->bindParam( ':from_date', $from_date );
$queryGetAllData->bindParam( ':to_date', $to_date );
$queryGetAllData->execute();

$allData = $queryGetAllData->fetchAll( PDO::FETCH_NUM );
// $allData = $queryGetAllData->fetchAll( PDO::FETCH_OBJ );

$answer = array();
$answer[ 'allData' ] = $allData;
echo json_encode( array( "answer" => $answer), JSON_NUMERIC_CHECK );

$util::logInfo( 'electric_dl: execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>