<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
?>
<script type='application/javascript' src='../common/js/calendarPicker/jquery.calendarPicker.js'></script>
<link rel='stylesheet' type='text/css' href='../common/js/calendarPicker/jquery.calendarPicker.css'/>
<script type='application/javascript' src='../common/js/moment-with-locales.js'></script>


<style>
.grid-container
{
  display: grid;
  grid-template-columns: repeat( 5, 1fr );
  top: -47px;
  position: relative;
}

.grid-container>div
{
  border: 2px solid black;
  margin: 2px;
  background-color: #928d81;
}

.grid-container select
{
  width: 160px !important;
  height: 100px;
  background-color: #928d81;
  border-bottom: 1px dashed #666;
}
</style>

<script>
var rawData = [];

function fetchData(){

  var fromDate = moment( calPickFrom.currentDate ).format( 'YYYY-MM-DD HH:mm' );
  var toDate = moment( calPickTo.currentDate ).format( 'YYYY-MM-DD HH:mm' );

  var query1 = {};
  query1[ 'key' ] = 'use';
  query1[ 'meter' ] = 1;
  query1[ 'from_date' ] = fromDate;
  query1[ 'to_date' ] = toDate;

  var query2 = {};
  query2[ 'key' ] = 'gen';
  query2[ 'meter' ] = 2;
  query2[ 'from_date' ] = fromDate;
  query2[ 'to_date' ] = toDate;

  var query = [];
  query[0] = query1;
  query[1] = query2;

  var request = {};
  request[ 'query' ] = query;
  request[ 'response_type' ] = 'merge';


  var jsonData = {};
  jsonData[ 'user' ] = '<?php echo $user->getName() ?>';
  jsonData[ 'session' ] = '<?php echo $user->getSession() ?>';
  jsonData[ 'request' ] = request;

debugger;
 $.ajax({
         type:        'GET'
        ,url:         'electric_dl'
        ,data:        jsonData
        ,contentType: 'application/json; charset=utf-8'
        ,dataType:    'json'
        ,success:     function( data ){
debugger; // Check the result
console.log( data );
          rawData = [];   // Reset data array in case this is a re-run
          $.each( data.response.answer, function( key, value ){
              rawData.push( {"date": value.date, "con": value.watts, "gen": 0 } );

/*
            if( value[0] == 1){
//              $( '#consumption > tbody:last-child' ).append('<tr><td>'+value[1]+'</td><td>'+value[2]+'</td><td>'+value[3]+'</td></tr>');
              generate = false;
              consume = true;
              rawData.push( {"date": value.date, "con": value.watts, "gen": 0 } );
            }
            else{
//              $( '#generation > tbody:last-child' ).append('<tr><td>'+value[1]+'</td><td>'+value[2]+'</td><td>'+value[3]+'</td></tr>');
              generate = true;
              consume = false;
              rawData.push( {"date": value[1], "con": 0, "gen": value[2] } );
            }
*/
          });
chart1 = doChart( false, true, rawData );
/*
          if( consume ){
            chart1 = doChart( generate, consume, rawData );
          }
*/
        }
        ,failure:     function( errMsg ){
debugger; // Check the result
console.log( errMsg );
        }
  });
/*
var generate = false;
var consume = false;
  // Make ajax call to data source
  $.getJSON( url_string, function( data ){
    rawData = [];   // Reset data array in case this is a re-run
    $.each( data.answer[ 'allData' ], function( key, value ){
      if( value[0] == 1){
        $( '#consumption > tbody:last-child' ).append('<tr><td>'+value[1]+'</td><td>'+value[2]+'</td><td>'+value[3]+'</td></tr>');
        generate = false;
        consume = true;
        rawData.push( {"date": value[1], "con": value[2], "gen": 0 } );
      }
      else{
        $( '#generation > tbody:last-child' ).append('<tr><td>'+value[1]+'</td><td>'+value[2]+'</td><td>'+value[3]+'</td></tr>');
        generate = true;
        consume = false;
        rawData.push( {"date": value[1], "con": 0, "gen": value[2] } );
      }
    });
    if( consume ){
      chart1 = doChart( generate, consume, rawData );
    }
  });
*/
}

var calPickFrom;
var calPickTo;
$( document ).ready( function(){
// QQQ Check out the calendars that support aselecting a date RANGE
// QQQ http://roberto.open-lab.com/2010/03/23/so-hard-to-have-a-date/
  calPickFrom = $( '#cpf' ).calendarPicker({
     monthNames:    [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ]
    ,dayNames:      [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ]
    //,useWheel: true
    //,callbackDelay: 500
    ,years:         2 // Two on each side of selected
    ,months:        4 // Four on each side of selected
    ,days:          5 // Five on each side of selected
    ,date:          new Date( (new Date().valueOf()) - (1000 * 60 * 60 * 24 * 7) )  // Last week
    ,showDayArrows: true
    ,callback:      function( cal ){
      $( '#cpfd' ).html( 'From date: ' + cal.currentDate );
    }});
  calPickTo = $( '#cpt' ).calendarPicker({
     monthNames:    [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ]
    ,dayNames:      [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ]
    //,useWheel: true
    //,callbackDelay: 500
    ,years:         2 // Two on each side of selected
    ,months:        4 // Four on each side of selected
    ,days:          5 // Five on each side of selected
    ,date: new Date() // Today
    ,showDayArrows: true
    ,callback:      function( cal ){
      $( '#cptd' ).html( 'To date: ' + cal.currentDate );
    }});

  fetchData();
});

</script>


<div class='grid-container' style='grid-auto-flow: row;'>
  <div>
    <div>Where</div>
    <div>
      <select id='LOC' size='3' style='width: 95px;'>
        <option value='0'>Home</option>
        <option value='1'>Parents</option>
        <option value='2'>Rental 1</option>
        <option value='3'>Rental 2</option>
        <option value='4'>Rental 3</option>
      </select>
    </div>
  </div>
  <div>
    <div>What</div>
    <div>
      <select multiple id='USE' size='3' style='width: 95px;'>
        <option value='0'>Usage</option>
      </select>
    </div>
  </div>
  <div>
    <div>What2</div>
    <div>
      <select multiple id='GEN' size='3' style='width: 95px;'>
        <option value='0'>Generation</option>
      </select>
    </div>
  </div>

  <div>
    <div>From <button onclick='calPickFrom.changeDate( new Date() );' style='line-height: 11px; float: right; position: relative; top: 2px; right: 2px;'>Today</button></div>
    <div id='cpf' style='width: 340px'></div>
    <span id='cpfd'></span>
<!--
    <div>
      <input type='date' id='FROM' size='10' value='' max='' min='' step='1'/>
    </div>
-->
  </div>
  <div>
    <div>To <button onclick='calPickTo.changeDate( new Date() );' style='line-height: 11px; float: right; position: relative; top: 2px; right: 2px;'>Today</button></div>
    <div id='cpt' style='width: 340px'></div>
    <span id='cptd'></span>
<!--
    <div>
      <input type='date' id='TO' size='10'   value='' max='' min='' step='1'/>
    </div>
-->
  </div>


</div>
<button onClick='fetchData();'>Clicker</button>
<style>
#chartdiv {
  width: 100%;
  height: 500px;
}
</style>
<script type='application/javascript' src='../common/js/amcharts/amcharts.js'></script>
<script type='application/javascript' src='../common/js/amcharts/serial.js'></script>
<script type='application/javascript' src='../common/js/amcharts/themes/dark.js'></script>
<div id='chartdiv' style='width: 90%; height: 400px; background-color: #282828; margin: 0 auto; padding: 10px;'></div>
<script>
var chart1;
var chart2;
function doChart( g, c, rd ){
  if( g ){
  }
  else{
  }
debugger;
var chart = AmCharts.makeChart( 'chartdiv', {
    "type": "serial",
    "theme": "dark",
    "dataProvider": rd,
    "marginTop": 20,
    "marginRight": 70,
    "marginLeft": 40,
    "marginBottom": 20,
    "autoMargins": true,
    "valueScrollbar":{
      "enabled": false,
      "offset":30
    },
    "valueAxes": [{
        "id":"v1",
        "axisColor": "#FF6600",
        "axisThickness": 2,
        "axisAlpha": 1,
        "position": "left"
    }, {
        "id":"v1",
        "axisColor": "#B0DE09",
        "axisThickness": 2,
        "gridAlpha": 0,
        "offset": 50,
        "axisAlpha": 1,
        "position": "left"
    }],
    "graphs": [{
        "valueField": "con",
        "valueAxis": "v1",
        "connect": false,
        "lineColor": "#FF6600",
        "bullet": "round",
        "bulletBorderThickness": 1,
        "hideBulletsCount": 30,
        "title": "red line",
/*        "balloonText":"<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:18px'>Value:[[value]]</span>", */
        "balloonText":"[[value]]",
        "fillAlphas": 0
    }, {
        "valueField": "gen",
        "valueAxis": "v1",
        "connect": false,
        "lineColor": "#B0DE09",
        "bullet": "triangleUp",
        "bulletBorderThickness": 1,
        "hideBulletsCount": 30,
        "title": "green line",
/*        "balloonText":"<div style='margin:10px; text-align:left;'><span style='font-size:13px'>[[category]]</span><br><span style='font-size:18px'>Value:[[value]]</span>", */
        "balloonText":"[[value]]",
        "fillAlphas": 0
    }],
    "chartCursor": {
        "graphBulletSize": 1.5,
        "zoomable":false,
        "valueZoomable":false,
        "categoryBalloonText": "[[category]]",
        "categoryBalloonDateFormat": "YYYY/MM/DD JJ:NN",
        "cursorAlpha":0,
        "valueLineEnabled":true,
        "valueLineBalloonEnabled":true,
        "valueLineAlpha":0.2
    },
    "dataDateFormat": "YYYY/MM/DD JJ:NN",
    "categoryField": "date",
    "categoryAxis": {
        "parseDates": true,
        "minPeriod": "mm",
        "axisAlpha": 0,
        "gridAlpha": 0,
        "inside": true,
        "title": "X axis title here",
        "tickLength": 0
    },
    "export": {
        "enabled": false
    }
});
return chart;
}
</script>

<div style='display: grid; grid-template-columns: repeat( 2, 1fr );'>

<div style='width: 50%; margin: 5px; padding: 5px; font-family: ariel; font-size: normal;'>
  Electric Consumption

  <table id='consumption' style='width: 100%'>
    <thead>
      <tr>
        <th>date</th>
        <th>watts</th>
        <th>volts</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>

</div>

<div style='width: 50%; margin: 5px; padding: 5px; font-family: ariel; font-size: normal;'>
  Electric Generation

  <table id='generation' style='width: 100%'>
    <thead>
      <tr>
        <th>date</th>
        <th>watts</th>
        <th>volts</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>

</div>

</div>


<?php
  require_once( 'standard_page_foot.php' );
?>