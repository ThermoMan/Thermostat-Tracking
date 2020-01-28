<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;
?>

<script type='application/javascript'>
function display_chart_build_and_display( chart_target ){
  var show_thermostat_id = 'id=' + document.getElementById( 'chart.history.thermostat' ).value;
  var show_indoor = 'Indoor=' + document.getElementById( 'history_selection' ).value;
  var show_hvac_runtime = 'show_hvac_runtime=' + document.getElementById( 'show_hvac_runtime' ).checked;

  var interval_measure_string = 'interval_measure=' + document.getElementById( 'interval_measure' ).value;
  var interval_length_string = 'interval_length=' + document.getElementById( 'interval_length' ).value;

  var history_to_date_string = 'history_to_date=' + document.getElementById( 'chart.history.toDate' ).value;

  var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching. That cleverness breaks this web page's function.

  var url_string = 'history_dl.php?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>';

  url_string = url_string + '&' + show_thermostat_id + '&' + show_indoor + '&' + show_hvac_runtime + '&' + interval_measure_string + '&' + interval_length_string + '&' + history_to_date_string  + '&' + no_cache_string;
  console.log( url_string );
  chart_target.src = url_string;
}


// Change names of the IDs to match this naming convention 'chart.history.toDate' instead of this convention 'history_to_date'
function display_historic_chart(){
  var chart_topic;

  chart_target = document.getElementById( 'history_chart' );
  chart_target.src = 'images/hvac_runtime_placeholder.png';  // Redraw the placekeeper while the chart is rendering

  // By using setTimeout we can separate the drawing of the placeholder image from the actual chart such that the browser will always draw the placeholder
  setTimeout( function(){ display_chart_build_and_display( chart_target ); }, 500 );

}
</script>

<input type='button' onClick='javascript: display_historic_chart();' value='Show'>

<select id='chart.history.thermostat'>
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

<select id='history_selection'>
  <option value='0' selected>Outoor</option>
  <option value='1'>Indoor</option>
  <option value='2'>Both</option>
</select>

Timeframe <input type='text' id='interval_length' value='21' size='3'>
<select id='interval_measure' style='width: 65px'>
  <option value='0' selected>days</option>
  <option value='1'>weeks</option>
  <option value='2'>months</option>
</select>
<?php
  $show_date = date( 'Y-m-d', time() );
?>
ending on <input type='date' id='chart.history.toDate' size='10' value='<?php print( $show_date ); ?>' max='<?php print( $show_date ); ?>' step='1'/>

&nbsp;&nbsp;Optionally show HVAC runtimes<input type='checkbox' id='show_hvac_runtime' name='show_hvac_runtime'/>

<div class='content'>
  <br>
  <div class='thermo_chart'>
    <img id='history_chart' src='images/hvac_runtime_placeholder.png' alt='History Graph' title='History Graph'>
  </div>
</div>

<script type='application/javascript'>
$( document ).ready( function(){
  display_historic_chart();
});
</script>

<?php
  require_once( 'standard_page_foot.php' );
?>