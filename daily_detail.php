<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;
?>

<style>
#chartdiv {
  width: 100%;
  height: 500px;
}
</style>
<script type='application/javascript' src='../common/js/amcharts/amcharts.js'></script>
<script type='application/javascript' src='../common/js/amcharts/serial.js'></script>

<script type='application/javascript'>
var rawData = [];
function display_chart(){
  // QQQ ALL arguments need a name review.  1) consistency  2) BREVITY  3) comprehension
  var daily_setpoint_selection = 'chart.daily.setpoint='            + document.getElementById( 'chart.daily.setpoint' ).checked;
  var show_heat_cycle_string   = 'chart.daily.showHeat='            + document.getElementById( 'chart.daily.showHeat' ).checked;
  var show_cool_cycle_string   = 'chart.daily.showCool='            + document.getElementById( 'chart.daily.showCool' ).checked;
  var show_fan_cycle_string    = 'chart.daily.showFan='             + document.getElementById( 'chart.daily.showFan' ).checked;
  var show_outdoor_humid_str   = 'chart.daily.showOutdoorHumidity=' + document.getElementById( 'chart.daily.showOutdoorHumidity' ).checked;
  var show_indoor_humid_str    = 'chart.daily.showIndoorHumidity='  + document.getElementById( 'chart.daily.showIndoorHumidity' ).checked;

  var show_thermostat_id       = 'thermostat_id='                   + $( '#chart\\.daily\\.thermostat' ).val();
  var daily_source_selection   = 'chart.daily.source='              + $( '#chart\\.daily\\.source' ).val();
  var daily_interval_length    = 'chart.daily.interval.length='     + $( '#chart\\.daily\\.interval\\.length' ).val();
  var daily_interval_group     = 'chart.daily.interval.group='      + $( '#chart\\.daily\\.interval\\.group' ).val();
  var daily_to_date_string     = 'chart.daily.toDate='              + $( '#chart\\.daily\\.toDate' ).val();

  // Browsers are very clever with image caching. In this case it breaks the web page function.
  var no_cache_string = 'nocache=' + Math.random();

  var url_string = '';
  url_string = 'daily_detail_dl';

  var showIndoor = false;
  var showOutdoor = false;
  var source = $( '#chart\\.daily\\.source' ).val();
  if( source == 0 || source == 2 ) showIndoor = true;
  if( source == 1 || source == 2 ) showOutdoor = true;

  var outData = {
     "user"               : "<?php echo $user->getName() ?>"                              // Who wants to know?
    ,"session"            : "<?php echo $user->getSession() ?>"                           // Show the keys to prove they can know
    ,"thermostat_id"      : $( '#chart\\.daily\\.thermostat' ).val()                      // What thermostat do they want to see?
    ,"showIndoor"         : showIndoor
    ,"showOutdoor"        : showOutdoor
    ,"showSetpoint"       : document.getElementById( 'chart.daily.setpoint' ).checked     // Do they want to seee the settings too?
    ,"interval_measure"   : $( '#chart\\.daily\\.interval\\.group' ).val()                // 0: days, 1: weeks, 2: months, 3: years
    ,"interval_length"    : $( '#chart\\.daily\\.interval\\.length' ).val()               // How many intervals do they want to see?
    ,"toDate"             : $( '#chart\\.daily\\.toDate' ).val()                          // Ending on which date?
    ,"showHeat"           : document.getElementById( 'chart.daily.showHeat' ).checked     // See the heater run times?
    ,"showCool"           : document.getElementById( 'chart.daily.showCool' ).checked     // See the AC run times?
    ,"showFan"            : document.getElementById( 'chart.daily.showFan' ).checked      // See the fan run times?
    ,"showOutdoorHumidity": document.getElementById( 'chart.daily.showOutdoorHumidity' ).checked  // See outdoor humidity?
    ,"showIndoorHumidity" : document.getElementById( 'chart.daily.showIndoorHumidity' ).checked   // See indoor humidity?
  };

debugger;
  $.ajax( {
       "dataType": "json"     // Returning data is expeted to be JSON
      ,"method"  : "POST"      // One of POST, GET, or PUT
      ,"url"     : url_string // Where to send the request
      ,"data"    : JSON.stringify( outData )
      ,"contentType": "application/json"  // Outgoing data is JSON
      ,"success" : function( inData, status, xhr ){
        // The call worked, extract and display the data
debugger;
      }
      ,"error"   : function( xhr, status, error ){
        // Do some error handling here
debugger;
      }
  });

/*
$.getJSON( url_string, outData, function( inData ){
  rawData = [];   // Reset global data array in case this is a re-run
  var min = 100;  // Set chart Y axis boundary flags so they are guaranteed to move
  var max = 0;

debugger;
  $.each( inData.answer[ 'indoorTemp' ], function( key, value ){
    if( $.isNumeric( value ) && value > max ){
      max = value;
    }else if( $.isNumeric( value ) && value < min ){
      min = value;
    }
    rawData.push( {"date": key, "value": value } );
  });
  max = (Math.ceil( max / 10 ) * 10) + 10;    // Widen to nearest ten degrees upper and lower bound
  min = (Math.floor( min / 10 ) * 10) - 10;
  chart.valueAxes[0].maximum = max;
  chart.valueAxes[0].minimum = min;
  chart.dataProvider = rawData;
  chart.validateData();
});
*/
return;
}
/*
  url_string = 'daily_detail_dl?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>';

  url_string = url_string + '&' + show_thermostat_id + '&' + daily_source_selection + '&' + daily_setpoint_selection + '&' +
               daily_interval_length  + '&' + daily_interval_group   + '&' + daily_to_date_string  + '&' +
               show_heat_cycle_string + '&' + show_cool_cycle_string + '&' + show_fan_cycle_string + '&' +
               show_outdoor_humid_str + '&' + show_indoor_humid_str  + '&' +
               no_cache_string;


  // Make ajax call to data source
  $.getJSON( url_string, function( data ){
    rawData = [];   // Reset data array in case this is a re-run
    var min = 100;  // Set chart Y axis boundary flags so they are guaranteed to move
    var max = 0;

debugger;
    $.each( data.answer[ 'indoorTemp' ], function( key, value ){
      if( $.isNumeric( value ) && value > max ) max = value;
      else if( $.isNumeric( value ) && value < min ) min = value;
      rawData.push( {"date": key, "value": value } );
    });
// Need to programmatically add or delete sections in the chart data structure
//    $.each( data.answer[ 'outdoorTemp' ], function( key, value ){
//      if( $.isNumeric( value ) && value > max ) max = value;
//      else if( $.isNumeric( value ) && value < min ) min = value;
//      rawData.push( {"date": key, "value": value } );
//    });
//

    max = (Math.ceil( max / 10 ) * 10) + 10;    // Widen to nearest ten degrees upper and lower bound
    min = (Math.floor( min / 10 ) * 10) - 10;
    chart.valueAxes[0].maximum = max;
    chart.valueAxes[0].minimum = min;
    chart.dataProvider = rawData;
    chart.validateData();
  });
*/

</script>

<div style='display: block;'>

<div style='display: inline;'>
<input type='button' onClick='javascript: display_chart();' value='Show'>
</div>

<div style='display: inline; position: relative; top: 31px;'>
<select id='LOC' size='3' style='width: 95px;'>
<option value='0'>Home</option>
<option value='1'>Parents</option>
<option value='2'>Rental 1</option>
<option value='3'>Rental 2</option>
<option value='4'>Rental 3</option>
</select>
</div>

<select id='chart.daily.thermostat'>
<?php
  foreach( $user->thermostats as $thermostatRec ){
?>
  <option
<?php
    if( $id == $thermostatRec['thermostat_id'] ) print( ' selected ' );
?>
    value='<?php print( $thermostatRec['thermostat_id'] ); ?>'><?php print( $thermostatRec['name'] ); ?> </option>
<?php
  }
?>
</select>

<div style='display: inline; position: relative; top: 31px;'>
<select multiple id='TMP' size='3' style='width: 95px;'>
<option value='0'>Outside</option>
<option value='1'>Upstairs</option>
<option value='2'>Basement</option>
</select>
</div>

<select id='chart.daily.source'>
  <option value='0'>Outoor</option>
  <option value='1'>Indoor</option>
  <option value='2' selected>Both</option>
</select>

&nbsp;<input type='checkbox' id='chart.daily.setpoint' name='chart.daily.setpoint'/> Set Point

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; showing Heat<input type='checkbox' id='chart.daily.showHeat' name='chart.daily.showHeat'/>
&nbsp;Cool<input type='checkbox' id='chart.daily.showCool' name='chart.daily.showCool'/>
&nbsp;Fan<input type='checkbox' id='chart.daily.showFan'  name='chart.daily.showFan'/> cycles

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; humidity Indoor<input type='checkbox' id='chart.daily.showIndoorHumidity' name='chart.daily.showIndoorHumidity'/>
&nbsp;Outdoor<input type='checkbox' id='chart.daily.showOutdoorHumidity' name='chart.daily.showOutdoorHumidity' checked/>
</div>

<br><br>

<div style='block;'>
<input type='button' onClick='javascript: interval(-1);' value='Previous' title='Show previous timeframe' >
Timeframe <input type='text' id='chart.daily.interval.length' value='7' size='3'>
<select id='chart.daily.interval.group' style='width: 65px'>
  <option value='0' selected>days</option>
  <option value='1'>weeks</option>
  <option value='2'>months</option>
  <option value='3'>years</option>
</select>

<?php
  $show_date = date( 'Y-m-d', time() );
?>
ending on <input type='date' id='chart.daily.toDate' size='10' value='<?php print( $show_date ); ?>' max='<?php print( $show_date ); ?>' step='1'/>
<input type='button' onClick='javascript: interval(1);' value='Next' title='Show next timeframe' >
</div>

<div class='content'>
<br>
  <div id='chartdiv' style='height: 370px; width: 95%;'></div>
  <div class='thermo_chart'>
  </div>
</div>


<script type="application/javascript">
var chart = AmCharts.makeChart("chartdiv", {
    "type":                 "serial"
    ,"theme":                "light"
    ,"marginRight":           40
    ,"marginLeft":            40
    ,"autoMarginOffset":      20
    ,"mouseWheelZoomEnabled": true
    ,"dataDateFormat":       "YYYY-MM-DD JJ:NN"
    ,"valueAxes": [{
        "id":             "v1"
        ,"axisAlpha":       0
        ,"title":          "Temperatures"
        ,"position":       "left"
        ,"maximum": 80
        ,"minimum": 70
        ,"ignoreAxisWidth": true
    }]
    ,"balloon": {
         "borderThickness": 1
        ,"shadowAlpha":     0
    }
    ,"graphs": [{
        "id": "g1",
        "balloon":{
          "drop":              true,
          "adjustBorderColor": false,
          "color":            "#ffffff"
        },
        "bullet":           "round",
        "bulletBorderAlpha": 1,
        "bulletColor":      "#FFFFFF",
        "bulletSize":        5,
        "hideBulletsCount":  50,
        "lineThickness":               2,
        "title":                      "red line",
        "useLineColorForBulletBorder": true,
        "valueField":                 "value",
        "balloonText":                "<span style='font-size:16px;'>[[value]]</span>"
    }],

    "chartScrollbar": {
        "graph": "g1",
        "oppositeAxis":false,
        "offset":30,
        "scrollbarHeight": 80,
        "backgroundAlpha": 0,
        "selectedBackgroundAlpha": 0.1,
        "selectedBackgroundColor": "#888888",
        "graphFillAlpha": 0,
        "graphLineAlpha": 0.5,
        "selectedGraphFillAlpha": 0,
        "selectedGraphLineAlpha": 1,
        "autoGridCount":true,
        "color":"#AAAAAA"
    },
    "chartCursor": {
        "pan": true,
        "categoryBalloonDateFormat": "YYYY-MM-DD JJ:NN",
        "cursorPosition":            "mouse",
        "valueLineEnabled":           true,
        "valueLineBalloonEnabled":    true,
        "cursorAlpha":                1,
        "cursorColor":               "#258cbb",
        "limitToGraph":              "g1",
        "valueLineAlpha":             0.2,
        "valueZoomable":              true
    },
    "valueScrollbar":{
      "oppositeAxis":    false,
      "offset":          50,
      "scrollbarHeight": 10
    },
    "categoryField": "date",
    "categoryAxis": {
        "parseDates":       false,
        "equalSpacing":     true,
        "title":           "Date/Time",
        "dateFormat":      "YYYY-MM-DD JJ:NN",
        "dashLength":       1,
    "autoGridCount": false,
    "gridCount": 5,
        "minorGridEnabled": true
    },
    "export": {
        "enabled": true
    },
    "dataProvider": rawData
});

chart.addListener( "rendered", zoomChart );

zoomChart();

function zoomChart() {
    chart.zoomToIndexes(chart.dataProvider.length - 40, chart.dataProvider.length - 1);
}

$( document ).ready(function() {
  display_chart();
});
</script>

<?php
  require_once( 'standard_page_foot.php' );
?>