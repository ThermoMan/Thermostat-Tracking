<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;
?>

<script type='text/javascript'>
$( document ).ready( function(){
  var show_thermostat_id       = 'thermostat_id='                   + $( '#chart\\.compare\\.thermostat' ).val();

  var url_string = '';
  var url_base_string = 'compare_dl?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>';
url_base_string = url_base_string + '&' + show_thermostat_id;

  // Populate the FROM select options
  url_string = url_base_string + '&get_from=true';
  $.getJSON( url_string, function( data ){
// QQQ OK, gotta fix the stupid in HTML.  I like teh dot in my names but the double escape to see it.  That's just stupid and I have to live with it.
    $( '#chart\\.compare\\.firstDate' ).empty();
    $.each( data.answer[ 'from_dates' ], function( key, value ){
      $( '#chart\\.compare\\.firstDate' ).append( $( '<option></option>' ).attr( 'value', value ).text( value ) );
    });
  });

  // Populate the TO select options
  url_string = url_base_string + '&get_to=true';
  $.getJSON( url_string, function( data ){
// QQQ OK, gotta fix the stupid in HTML.  I like teh dot in my names but the double escape to see it.  That's just stupid and I have to live with it.
    $( '#chart\\.compare\\.secondDate' ).empty();
    $.each( data.answer[ 'to_dates' ], function( key, value ){
      $( '#chart\\.compare\\.secondDate' ).append( $( '<option></option>' ).attr( 'value', value ).text( value ) );
    });
  });
});
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
    value='<?php print( $thermostatRec['thermostat_id'] ); ?>'><?php print( $thermostatRec['name'] ); ?> </option>
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
  Compare <select id='chart.compare.firstDate'></select>
  to <select id='chart.compare.secondDate'></select>

  for <select id='chart.compare.mode'><option value='0'>Heating</option><option value='1' selected>Cooling</option></select>

<div class='content'>
  <br>
  <div class='thermo_chart'>
  </div>
</div>




<?php
  require_once( 'standard_page_foot.php' );
?>