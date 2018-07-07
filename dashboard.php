<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;

$util::logDebug( 'dashboard.php 0' );
//$util::send_mail( 'theinscrutable@yahoo.com', 'Your message here', 'User ID registration email' );

?>

<style>
.thrm_dashboard
{
/*   display: inline-block; */
/*  margin: 0px; */
  margin-left: 50px;
  text-align: left;
/*  float: left; */
  width: 400px;
  height: 250px;
/*  overflow-y: auto; */
}

</style>

<script type='text/javascript'>
function update( p_section, p_time ){
  var time = typeof p_time !== 'undefined' ? p_time : 3000;
  if( [ 'status', 'forecast', 'electro' ].indexOf( p_section ) >= 0){
    $( '#'+p_section ).html( $( '#'+p_section ).data( 'default' ) );
    var submitURL = 'get_instant?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>&type=' + p_section;
    $.ajax({
       url:     submitURL
      ,timeout: time
    }).done( function( data ){
      $( '#'+p_section ).html( data );
    }).fail( function( jqXHR, textStatus ){
      console.log( 'Dashboard refresh timed out for ' + p_section + ' with message ' + textStatus );
      $( '#'+p_section ).html( 'Status is presently unavailable.  Has your IP address changed?' );
    });
  }
}

function show(){
  update( 'status', 15000 );
  update( 'forecast' );
  update( 'electro', 10000 );
}

$( document ).ready( function(){
  show();
  $( '#refresh_dashboard' ).unbind( 'click' ).click( function(){
    show();
  });
});
</script>

<button type='button' class='btn btn-primary' id='refresh_dashboard'>Refresh All <span class='glyphicon glyphicon-repeat'></span></button>
<br><br><br>

<div class='row'>
  <div class='col-md-4 thrm_dashboard' id='status' data-default='<span><img src="images/img_trans.gif" width="1" height="1" class="large_sprite wheels" /></span><span>Looking up present status and conditions. (This may take some time)</span>' >
    <span><img src='images/img_trans.gif' width='1' height='1' class='large_sprite wheels' /></span><span>Looking up the present status and conditions.</span>
  </div>
  <div class='col-md-4 thrm_dashboard' id='forecast' data-default='<span><img src="images/img_trans.gif" width="1" height="1" class="large_sprite wheels" /></span><span>Looking up the forecast.</span>' >
    <span><img src='images/img_trans.gif' width='1' height='1' class='large_sprite wheels' /></span><span>Looking up the forecast.</span>
  </div>
  <div class='col-md-4 thrm_dashboard' id='electro' data-default='<span><img src="images/img_trans.gif" width="1" height="1" class="large_sprite wheels" /></span><span>Looking up the present electric usage.</span>' >
    <span><img src='images/img_trans.gif' width='1' height='1' class='large_sprite wheels' /></span><span>Looking up the present electric usage.</span>
  </div>
</div>
<?php
  require_once( 'standard_page_foot.php' );
?>