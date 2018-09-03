<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;

$util::logDebug( 'dashboard: 0' );
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
// timeSince is a good candidate for a standard library
// Set precision numerically instead of on/off?
function timeSince( oldDate, RoE ){
  var Rough = 0;
  var Exact = 1;
  // optional argument RoE is "Rough or Exact" time since requested date.
  RoE = RoE || Rough;

  var seconds = Math.floor( ( new Date( ( Date.now() - oldDate ) ) ) / 1000 );
  var returnString = '';

  var intervalYears = Math.floor( seconds / 31536000 );
  if( intervalYears > 0 ){
    seconds = seconds - ( intervalYears * 31536000 );
    returnString = intervalYears + ' year';
    if( intervalYears > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  // Using 30 days for all months....
  var intervalMonths = Math.floor( seconds / 2592000 );
  if( intervalMonths > 0 ){
    seconds = seconds - ( intervalMonths * 2592000 );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalMonths + ' month';
    if( intervalMonths > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  var intervalDays = Math.floor( seconds / 86400 );
  if( intervalDays > 0 ){
    seconds = seconds - ( intervalDays * 86400 );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalDays + ' day';
    if( intervalDays > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  var intervalHours = Math.floor( seconds / 3600 );
  if( intervalHours > 0 ){
    seconds = seconds - ( intervalHours * 3600 );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalHours + ' hour';
    if( intervalHours > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  var intervalMinutes = Math.floor( seconds / 60 );
  if( intervalMinutes > 0 ){
    seconds = seconds - ( intervalMinutes * 60 );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalMinutes + ' minute';
    if( intervalMinutes > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  if( seconds > 0 ){
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + Math.floor( seconds ) + ' second';
    if( seconds > 1 ){ returnString = returnString + 's'; }
  }

  if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  else{ return returnString + ' ago'; }
}


function formatHVAC( p_data ){
  $( '#status' ).html( p_data );
}

function formatForecast( p_data ){
  $( '#forecast' ).html( p_data );
}

function formatElectric( p_data ){
  var p_data = JSON.parse( p_data );
  var answer = p_data.answer;
  var meter = answer.meter;
  var presentDate = '<br>Your TED 5000 thinks that it is ' + meter.present_date;
  var presentUse = '<br>You are using ' + meter.use + ' right now.';
  var presentProjectedUse = '<br>You are projected to use ' + meter.projected + ' this month.';
  var lastCollection = '';
  for( var ii = 0; ii < meter.mtu.length; ii++ ){
//    lastCollection = lastCollection + '<br>Last contact from MTU(' + meter.mtu[ii].mtu + ') was at ' + meter.mtu[ii].date;
//    lastCollection = lastCollection + '<br>Last contact from MTU(' + meter.mtu[ii].mtu + ') was ' + timeSince( new Date( meter.mtu[ii].date ), 0 );
    lastCollection = lastCollection + '<br>Last contact from MTU(' + meter.mtu[ii].mtu + ') was ' + timeSince( new Date( meter.mtu[ii].date ), 1 );
  }
  $( '#electro' ).html( presentDate + presentUse + presentProjectedUse + lastCollection );
}

function update( p_section, p_time ){
  var time = typeof p_time !== 'undefined' ? p_time : 3000;
  if( [ 'status', 'forecast', 'electro' ].indexOf( p_section ) >= 0){
    $( '#'+p_section ).html( $( '#'+p_section ).data( 'default' ) );
    var submitURL = 'get_instant?user=<?php echo $user->getName() ?>&session=<?php echo $user->getSession() ?>&type=' + p_section;
    $.ajax({
       url:     submitURL
      ,timeout: time
    }).done( function( data ){
      switch( p_section ){
        case 'status':
          formatHVAC( data );
        break;
        case 'forecast':
          formatForecast( data );
        break;
        case 'electro':
          formatElectric( data );
        break;
        default:
          console.log( 'Unknown section' );
      }
    }).fail( function( jqXHR, textStatus ){
      console.log( 'Dashboard refresh timed out for ' + p_section + ' with message ' + textStatus );
      $( '#'+p_section ).html( 'Temporarily unable to contact device.' );
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