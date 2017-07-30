<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;
?>

<script type='text/javascript'>
/**
  * chart is one of 'daily' or 'history'
  * sytle is one of 'chart' or 'table'
  *
  */
function display_chart( chart, style ){
  var chart_target;
  var table_flag = '';

  if( chart == 'daily' && style == 'chart' ){
    chart_target = document.getElementById( 'daily_temperature_chart' );
    chart_target.src = 'images/daily_temperature_placeholder.png';  // Redraw the placekeeper while the chart is rendering
    // By using setTimeout we can separate the drawing of the placeholder image from the actual chart such that the browser will always draw the placeholder
    setTimeout( function(){ display_chart_build_and_display( chart, style, 'false', chart_target ); }, 500 );
  }
  else if( chart == 'daily' && style == 'table' ){

    table_flag = 'table_flag=true';
    chart_target = document.getElementById( 'daily_temperature_table' );
    chart_target.innerHTML = '';
    display_chart_build_and_display( chart, style, table_flag, chart_target );
  }
  else{
    alert( 'You asked for '+chart+' and '+style+' and I do not know how to do that (yet).' );
    return;
  }
}


function display_chart_build_and_display( chart, style, table_flag, chart_target ){
//  var show_thermostat_id       = 'id='                          + document.getElementById( 'chart.daily.thermostat' ).value;
  var daily_setpoint_selection = 'chart.daily.setpoint='        + document.getElementById( 'chart.daily.setpoint' ).checked;
//  var daily_source_selection   = 'chart.daily.source='          + document.getElementById( 'chart.daily.source' ).value;
//  var daily_interval_length    = 'chart.daily.interval.length=' + document.getElementById( 'chart.daily.interval.length' ).value;
//  var daily_interval_group     = 'chart.daily.interval.group='  + document.getElementById( 'chart.daily.interval.group' ).value;
//  var daily_to_date_string     = 'chart.daily.toDate='          + document.getElementById( 'chart.daily.toDate' ).value;
  var show_heat_cycle_string   = 'chart.daily.showHeat='        + document.getElementById( 'chart.daily.showHeat' ).checked;
  var show_cool_cycle_string   = 'chart.daily.showCool='        + document.getElementById( 'chart.daily.showCool' ).checked;
  var show_fan_cycle_string    = 'chart.daily.showFan='         + document.getElementById( 'chart.daily.showFan' ).checked;
  var show_outdoor_humid_str   = 'chart.daily.showOutdoorHumidity=' + document.getElementById( 'chart.daily.showOutdoorHumidity' ).checked;
  var show_indoor_humid_str    = 'chart.daily.showIndoorHumidity='  + document.getElementById( 'chart.daily.showIndoorHumidity' ).checked;

  var show_thermostat_id       = 'id='                              + $( '#chart\\.daily\\.thermostat' ).val();
//  var daily_setpoint_selection = 'chart.daily.setpoint='            + $( '#chart\\.daily\\.setpoint' ).checked;
  var daily_source_selection   = 'chart.daily.source='              + $( '#chart\\.daily\\.source' ).val();
  var daily_interval_length    = 'chart.daily.interval.length='     + $( '#chart\\.daily\\.interval\\.length' ).val();
  var daily_interval_group     = 'chart.daily.interval.group='      + $( '#chart\\.daily\\.interval\\.group' ).val();
  var daily_to_date_string     = 'chart.daily.toDate='              + $( '#chart\\.daily\\.toDate' ).val();
//  var show_heat_cycle_string   = 'chart.daily.showHeat='            + $( '#chart\\.daily\\.showHeat' ).checked;
//  var show_cool_cycle_string   = 'chart.daily.showCool='            + $( '#chart\\.daily\\.showCool' ).checked;
//  var show_fan_cycle_string    = 'chart.daily.showFan='             + $( '#chart\\.daily\\.showFan' ).checked;
//  var show_outdoor_humid_str   = 'chart.daily.showOutdoorHumidity=' + $( '#chart\\.daily\\.showOutdoorHumidity' ).checked;
//  var show_indoor_humid_str    = 'chart.daily.showIndoorHumidity='  + $( '#chart\\.daily\\.showIndoorHumidity' ).checked;


  // Browsers are very clever with image caching. In this case it breaks the web page function.
  var no_cache_string = 'nocache=' + Math.random();

  var url_string = '';
  if( chart == 'daily' ){
    url_string = 'daily_detail_dl.php?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>';
  }
  else if( chart == 'history' ){
    // This space intentionally left blank (actually I don't recall why it was left blank, but everything seems to work)
  }

  url_string = url_string + '&' + show_thermostat_id + '&' + daily_source_selection + '&' + daily_setpoint_selection + '&' + table_flag + '&' +
               daily_interval_length  + '&' + daily_interval_group   + '&' + daily_to_date_string  + '&' +
               show_heat_cycle_string + '&' + show_cool_cycle_string + '&' + show_fan_cycle_string + '&' +
               show_outdoor_humid_str + '&' + show_indoor_humid_str  + '&' +
               no_cache_string;

  if( style == 'chart' ){
    chart_target.src = url_string;
  }
  else if( style == 'table' ){
    // Right now it assumes the DAILY table.  Fix that later
    // Size should be in the CSS file?

    // Gosh this iframe is a whole load of overkill and I hate it.
    chart_target.innerHTML = '<iframe src="'+url_string+'" height="100" width="530"></iframe>';

//    return url_string;
  }
}


/**
  * Save the value of the checkbox for later - and update the chart with the new value
  */
function toggle_daily_flag( flag ){
  setCookie( flag, document.getElementById(flag).checked );
}
</script>

<input type='button' onClick='javascript: display_chart( "daily", "chart" );' value='Show'>

<select id='chart.daily.thermostat'>
<?php
  foreach( $user->thermostats as $thermostatRec ){
?>
  <option
<?php
    if( $id == $thermostatRec['id'] ) print( ' selected ' );
?>
    value='<?php print( $thermostatRec['id'] ); ?>'><?php print( $thermostatRec['name'] ); ?> </option>
<?php
  }
?>
</select>

<select id='chart.daily.source'>
  <option value='0'>Outoor</option>
  <option value='1'>Indoor</option>
  <option value='2' selected>Both</option>
</select>

&nbsp;<input type='checkbox' id='chart.daily.setpoint' name='chart.daily.setpoint' onChange='javascript: toggle_daily_flag( "chart.daily.setpoint" );'/> Set Point

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; showing Heat<input type='checkbox' id='chart.daily.showHeat' name='chart.daily.showHeat' onChange='javascript: toggle_daily_flag( "chart.daily.showHeat" );'/>
&nbsp;Cool<input type='checkbox' id='chart.daily.showCool' name='chart.daily.showCool' onChange='javascript: toggle_daily_flag( "chart.daily.showCool" );'/>
&nbsp;Fan<input type='checkbox' id='chart.daily.showFan'  name='chart.daily.showFan'  onChange='javascript: toggle_daily_flag( "chart.daily.showFan" );'/> cycles

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; humidity Indoor<input type='checkbox' id='chart.daily.showIndoorHumidity' name='chart.daily.showIndoorHumidity' onChange='javascript: toggle_daily_flag( "chart.daily.showIndoorHumidity" );'/>
&nbsp;Outdoor<input type='checkbox' id='chart.daily.showOutdoorHumidity' name='chart.daily.showOutdoorHumidity' onChange='javascript: toggle_daily_flag( "chart.daily.showOutdoorHumidity" );'/>

<input type='button' onClick='javascript: deleteCookies(0);' value='Un-save settings' style='float: right;'>

<br>

<input type='button' onClick='javascript: interval(-1);' value='Previous' title='Show previous timeframe' >
Timeframe <input type='text' id='chart.daily.interval.length' onChange='javascript: saveDateData( "daily" );' value='7' size='3'>
<select id='chart.daily.interval.group' onChange='javascript: saveDateData( "daily" );' style='width: 65px'>
  <option value='0' selected>days</option>
  <option value='1'>weeks</option>
  <option value='2'>months</option>
  <option value='3'>years</option>
</select>

<?php
  $show_date = date( 'Y-m-d', time() );
?>
ending on <input type='date' id='chart.daily.toDate' onChange='javascript: saveDateData( "daily" );' size='10' value='<?php print( $show_date ); ?>' max='<?php print( $show_date ); ?>' step='1'/>
<input type='button' onClick='javascript: interval(1);' value='Next' title='Show next timeframe' >

<div class='content'>
<br>
  <div class='thermo_chart'>
    <img id='daily_temperature_chart' src='images/daily_temperature_placeholder.png' alt='The temperatures'>
  </div>

  <input type='button' onClick='javascript: display_chart( "daily", "table" );' value='Chart it' style='float: right;'>
  <div id='daily_temperature_table' class='status daily_temperature_table'></div>
</div>


<script type='text/javascript'>
/*
    // In JavaScript, a literal/string value of "false" is a string of non zero length and so it tests as logically true unless you look for the literal string "true"

    // Restore user preference/cookie for showing/hiding setpoints
if( getCookie('chart.daily.setpoint') == 'true' ){
  document.getElementById('chart.daily.setpoint').checked = true;
}

    // Restore user preference/cookie for showing/hiding AC operation
if( getCookie('chart.daily.showCool') == 'true' ){
  document.getElementById('chart.daily.showCool').checked = true;
}

    // Restore user preference/cookie for showing/hiding heater operation
if( getCookie('chart.daily.showHeat') == 'true' ){
  document.getElementById('chart.daily.showHeat').checked = true;
}

    // Restore user preference/cookie for showing/hiding fan operation
if( getCookie('chart.daily.showFan') == 'true' ){
  document.getElementById('chart.daily.showFan').checked = true;
}
*/

//  loadDateData( 'daily' );

    // Draw the graph using the applied settings
$( document ).ready( function(){
  display_chart( 'daily', 'chart' );
});
</script>

<?php
  require_once( 'standard_page_foot.php' );
?>