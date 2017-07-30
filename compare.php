<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;
?>

<script type='text/javascript'>
function displayCompareChartExec(){
  var show_thermostat_id = 'id=' + document.getElementById( 'chart.compare.thermostat' ).value;
  var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching. That cleverness breaks this web page's function.
  var url_string = 'compare_dl.php?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>';

  url_string = url_string + '&' + show_thermostat_id + '&' + no_cache_string;

  document.getElementById( 'compare_chart' ).src = url_string;
}

function displayCompareChart(){
  document.getElementById( 'compare_chart' ).src = 'images/need_default.png'; // Redraw the placekeeper while the chart is rendering
  // By using setTimeout we can separate the drawing of the placeholder image from the actual chart such that the browser will always draw the placeholder
  setTimeout(function(){ displayCompareChartExec();}, 500);
}
</script>

<input type='button' onClick='javascript: displayCompareChart();' value='Show'>
<select id='chart.compare.thermostat'>
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

<!--
/** These two compare dates need to be dynamically populated based on the thermostat selected
  *
  * So the select of thermostat either onChange or onBlur needs to use Ajax to choose new values for first and second dates.
  * First date needs to go from 1 to n-1 and second date needs to go from 2 to n (where 1 is the first year and n is the max year in the record)
  * ?Disable first and second when there are not two years in the record and set both to the same year
  * ?Or just tell the user there is not yet enough info for a comparison
  * ?Once many users are on consider showing comparison against average (in the same hemisphere at least) other users
  */
-->
  Compare <select id='chart.compare.firstDate'><option value='2012'>2012</option><option value='2013'>2013</option><option value='2014'>2014</option></select>
  to <select id='chart.compare.secondDate'><option value='2012'>2012</option><option value='2013'>2013</option><option value='2014'>2014</option></select>

  for <select id='chart.compare.mode'><option value='0'>Heating</option><option value='1' selected>Cooling</option></select>

<div class='content'>
  <br>
  <div class='thermo_chart'>
    <img id='compare_chart' src='img/need_default.png' alt='Year-over-year comparison graph' title='Year-over-year comparison'>
  </div>
</div>


<script type='text/javascript'>
$( document ).ready( function(){
  displayCompareChartExec();
});
</script>

<?php
  require_once( 'standard_page_foot.php' );
?>