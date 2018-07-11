<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
?>

<style>
div.electric_dt
{
  height: 500px;
  width: 750px;
  position: relative;
  left: 100px;
  font-size: 90%;
}
div.electric_dt td.highlight
{
  font-weight: bold;
  color: blue;
}
.toolbar
{
  float: left;
  top: 8px;
  position: relative;
/*  overflow: hidden; */
}
div.electric_dt td.details-control
{
  background: url( 'images/details_open.png' ) no-repeat center center;
  cursor: pointer;
}
div.electric_dt tr.details td.details-control
{
  background: url( 'images/details_close.png' ) no-repeat center center;
}
</style>

<script>
$( document ).ready( function(){

//  var show_mtu_id       = 'mtu_id='                   + $( '#chart\\.daily\\.thermostat' ).val();

  var show_mtu_id       = 'mtu_id='                   + 1; // Hard code 1

//  var daily_interval_length    = 'chart.daily.interval.length='     + $( '#chart\\.daily\\.interval\\.length' ).val();
//  var daily_interval_group     = 'chart.daily.interval.group='      + $( '#chart\\.daily\\.interval\\.group' ).val();
//  var daily_to_date_string     = 'chart.daily.toDate='              + $( '#chart\\.daily\\.toDate' ).val();

  // So this is asking for the ten minutes ending on July 6 at midnight ( so it's the last 9 minutes of July 5 and the first minute of July 6 = 23:51, :52, :53, :54, :55, :56, :57, :58, :59, 24:00 )
  var daily_interval_length    = 'chart.daily.interval.length='     + 10;           // Hard code asking for ten units
  var daily_interval_group     = 'chart.daily.interval.group='      + 0;            // Hard code set unit type as minutres (others are hours, days, weeks, months, years)
  var daily_to_date_string     = 'chart.daily.toDate='              + '2018-07-06'; // Hard code set end date

  // Browsers are very clever with image caching. In this case it breaks the web page function.
  var no_cache_string = 'nocache=' + Math.random();

  var url_string = '';
  url_string = 'electric_dl?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>';

  url_string = url_string + '&' + show_mtu_id + '&' +
               daily_interval_length  + '&' + daily_interval_group   + '&' + daily_to_date_string  + '&' +
               no_cache_string;


  // Make ajax call to data source
  $.getJSON( url_string, function( data ){
    rawData = [];   // Reset data array i ncase this is a re-run
    var min = 100;  // Set chart Y axis boundary flags so they are guaranteed to move
    var max = 0;

debugger;
    $.each( data.answer[ 'allData' ], function( key, value ){
debugger;
/*
      if( $.isNumeric( value ) && value > max ) max = value;
      else if( $.isNumeric( value ) && value < min ) min = value;
      rawData.push( {"date": key, "value": value } );
*/
$('#electric_table > tbody:last-child').append('<tr><td>'+value[0]+'</td><td>'+value[1]+'</td><td>'+value[2]+'</td><td>'+value[3]+'</td></tr>');
    });


  });

});

</script>



<div class='electric_dt'>
  Electric usage

  <table id='electric_table' class='display' cellspacing='0' width='100%'>
    <thead>
      <tr>
        <th>mtu_id</th>
        <th>date</th>
        <th>watts</th>
        <th>volts</th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>

</div>


<?php
  require_once( 'standard_page_foot.php' );
?>