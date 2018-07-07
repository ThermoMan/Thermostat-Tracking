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
  var dt = $( '#electric_table' ).on( 'processing.dt', function ( e, settings, processing ) {
        $( '#processingIndicator' ).css( 'display', processing ? 'block' : 'none' );
    } ).DataTable({
     processing:      true
    ,dom:             '<"toolbar">frtip'
    ,serverSide:      true
    ,ajax:            'electric_dl.php'
    ,displayLength:   25
    ,info:            true
    ,searching:       true
//    ,searchDelay:     2000
    ,ordering:        false
    ,scrollY:         '200px'
    ,paging:          true

  });

  $( 'div.toolbar' ).html( 'Custom tool bar! Text/images etc. Long text here to reach over to the search input box. MW' );

  $( 'div.dataTables_filter input' ).off( 'keyup.DT search.DT input.DT paste.DT cut.DT' );
  var searchDelay = null;
  $( 'div.dataTables_filter input' ).on( 'keyup', function(){
    clearTimeout( searchDelay );
    searchDelay = setTimeout( function(){
      var sss = $( 'div.dataTables_filter input' ).val();
      if( sss != null ){
        dt.search( sss ).draw();
      }
    }, 2000 );
  });
});

</script>

<br>Look here: http://stackoverflow.com/questions/1955810/div-floating-over-table
<br>And here: https://datatables.net/reference/event/processing
<br>And here: http://preloaders.net/preloaders/39/Funnel.gif
<br>And finally here: http://preloaders.net/en/circular
<br>
<br>Add buttons to change scale.
<br> When showing days, accumulate kWh by hour
<br> When showing weeks, accumulate kWh by day
<br> When showing months, accumulate kWh by day
<br> When showing year, accumulate kWh by month
<br>
<br>Add a button to show the present selection as a graph instead of a table
<br>
<br>Determine limits for data to display
<br> Need a range for number of days ( 1 .. ? )
<br> Need a range for number of weeks ( 1 .. ? )
<br> Need a range for number of months ( 1 .. ? )
<br> Need a range for number of years ( 1 .. ? )
<br>
<br>Data marts should precompute all of these
<br> Need a control table that keeps track of work completed?
<br> Do not need a task to do this in the background though.
<br>  The first time a new date range is requested that is not precomputed, compute it and save it.  Give the user a message to be patient.

<div class='electric_dt'>
  <div id='processingIndicator' >
   <img src='http://preloaders.net/preloaders/39/Funnel.gif' style='position: relative; top: 50% z-index: '>
  </div>
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
  </table>

</div>

<br><br><br><br>
<hr>
<br><br><br><br>

<?php
  require_once( 'standard_page_foot.php' );
?>