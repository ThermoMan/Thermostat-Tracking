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
  //$util::logDebug( 'I got a user!' );
  }
  catch( Exception $e ){
    $util::logError( 'BUG' );
  }

  // QQQ Need to test that mtu_id belongs to user_id
  // QQQ Should start IDs from number other than 1!


  /*
    foreach( $user->TED5000_Gateways as $gatewayRec ){
  //$util::logDebug( 'row has ' . var_export( $gatewayRec ) );
    }
  */
}
else
{
  $util::logError( 'No user or session specified' );
  exit();
}

$database = new Database();
$pdo = $database->dbConnection();

$util::logDebug( 'working on queries' );

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
This has the request in an JSON object.
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
*/
foreach( $queryList as $query ){
$util::logDebug( 'key is ' . $query['key'] );

  $mtu_id = $query['meter'];
  $from_date = $query['from_date'];
  $to_date = $query['to_date'];

// QQQ Verify that both dates are dates/properly formatted
// QQQ Verify that neither date are not future?

$util::logDebug( "mtu_id = $mtu_id" );
$util::logDebug( "from_date = $from_date" );
$util::logDebug( "to_date = $to_date" );

$allData = array();

/*
  $sqlGetAllData =
"SELECT mtu_id
       ,date_format( date, '%Y/%m/%d %H:%i' ) AS date
       ,watts
       ,volts
   FROM {$database->table_prefix}meter_data
  WHERE mtu_id = :mtu_id
    AND date BETWEEN :from_date AND :to_date";
*/
  $sqlGetAllData =
"SELECT date_format( date, '%Y/%m/%d %H:%i' ) AS date
       ,watts
   FROM {$database->table_prefix}meter_data
  WHERE mtu_id = :mtu_id
    AND date BETWEEN :from_date AND :to_date";

  $queryGetAllData = $pdo->prepare( $sqlGetAllData );

  $queryGetAllData->bindParam( ':mtu_id', $mtu_id );
  $queryGetAllData->bindParam( ':from_date', $from_date, PDO::PARAM_STR );
  $queryGetAllData->bindParam( ':to_date', $to_date, PDO::PARAM_STR );

  $queryGetAllData->execute();

  //$allData = $queryGetAllData->fetchAll( PDO::FETCH_NUM );
  $allData = $queryGetAllData->fetchAll( PDO::FETCH_OBJ );
  //$allData = $queryGetAllData->fetchAll();

$util::logDebug( 'There were '.count($allData).' rows returned from the query.' );
}
$util::logDebug( 'done with queries' );

/*
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
      "2018-07-14 05:50": 7000,
      "2018-07-14 05:51": 7000,
      "2018-07-14 05:52": 7000,
      "2018-07-14 05:53": 7000,
      "2018-07-14 05:54": 7000,
      "2018-07-14 05:55": 7000,
      "2018-07-14 05:56": 7000,
      "2018-07-14 05:57": 7000,
      "2018-07-14 05:58": 7000,
      "2018-07-14 05:59": 7000,
      "2018-07-14 06:00": 7000
    }

An answer from two queries ("response_type" is "merge")
    "answer": {
      "2018-07-14 05:50": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:51": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:52": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:53": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:54": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:55": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:56": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:57": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:58": [{"use"7000},{"gen"-5000}],
      "2018-07-14 05:59": [{"use"7000},{"gen"-5000}],
      "2018-07-14 06:00": [{"use"7000},{"gen"-5000}]
    }

An answer from two queries ("response_type" is "append").  Note that by the rules of JSON if the key column contains literally identical
 values like the example below the subsequent appearance of a key will overwrite the values in the earlier appearance.
    "answer": {
      "2018-07-14 05:50": 7000,
      "2018-07-14 05:51": 7000,
      "2018-07-14 05:52": 7000,
      "2018-07-14 05:53": 7000,
      "2018-07-14 05:54": 7000,
      "2018-07-14 05:55": 7000,
      "2018-07-14 05:56": 7000,
      "2018-07-14 05:57": 7000,
      "2018-07-14 05:58": 7000,
      "2018-07-14 05:59": 7000,
      "2018-07-14 06:00": 7000,
      "2018-07-14 05:50": -5000,
      "2018-07-14 05:51": -5000,
      "2018-07-14 05:52": -5000,
      "2018-07-14 05:53": -5000,
      "2018-07-14 05:54": -5000,
      "2018-07-14 05:55": -5000,
      "2018-07-14 05:56": -5000,
      "2018-07-14 05:57": -5000,
      "2018-07-14 05:58": -5000,
      "2018-07-14 05:59": -5000,
      "2018-07-14 06:00": -5000
    }
In your query, if you don't want data over write make sure the query key date ranges do not overlap!
*/


$response = array();
$response[ 'request' ] = $request;
$response[ 'answer' ] = $allData;
echo json_encode( array( "response" => $response), JSON_NUMERIC_CHECK );

$util::logInfo( 'execution time was ' . (microtime(true) - $start_time) . ' seconds.' );

?>