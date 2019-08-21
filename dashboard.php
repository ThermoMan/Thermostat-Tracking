<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;

$util::logDebug( 'dashboard: 0' );
//$util::send_mail( 'theinscrutable@yahoo.com', 'Your message here', 'User ID registration email' );

?>

<style>
.thrm_row
{
  display: inline-block;
  width: 100%;
}
.thrm_dashboard
{
/*   display: inline-block; */
  margin: 0px 5px 0px 5px;  /* top right bottom left */
/*  margin-left: 50px; */
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
// QQQ modify this to return a JSON object with a string and some other meta data so you can do logic on the elapsed time.
function timeSince( oldDate, RoE ){
  var Exact = 0;
  var Rough = 1;
  var Veryrough = 2;    // Only go 2 intervals deep (eg Years + Months or Months + Days or Days + Hours or Hours + Minutes and NEVER seconds)
  // optional argument RoE is "Rough or Exact" time since requested date with Rough as default.
  RoE = RoE || Rough;

  var seconds = Math.floor( ( new Date( ( Date.now() - oldDate ) ) ) / 1000 );
  var returnString = '';

  var secondsInYear = 31536000;   // All years are 365 days.
  var secondsInMonth = 2592000;   // All months are 30 days.
  var secondsInDay = 86400;
  var secondsInHour = 3600;
  var secondsInMinute = 60;

  var intervalYears = Math.floor( seconds / secondsInYear );
  if( intervalYears > 0 ){
    seconds = seconds - ( intervalYears * secondsInYear );
    returnString = intervalYears + ' year';
    if( intervalYears > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }


  var intervalMonths = Math.floor( seconds / secondsInMonth );
  if( intervalMonths > 0 ){
    seconds = seconds - ( intervalMonths * secondsInMonth );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalMonths + ' month';
    if( intervalMonths > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  var intervalDays = Math.floor( seconds / secondsInDay );
  if( intervalDays > 0 ){
    seconds = seconds - ( intervalDays * secondsInDay );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalDays + ' day';
    if( intervalDays > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  var intervalHours = Math.floor( seconds / secondsInHour );
  if( intervalHours > 0 ){
    seconds = seconds - ( intervalHours * secondsInHour );
    if( returnString.length > 0 ){ returnString = returnString + ' '; }
    returnString = returnString + intervalHours + ' hour';
    if( intervalHours > 1 ){ returnString = returnString + 's'; }
    if( RoE == Rough ){ return 'about ' + returnString + ' ago'; }
  }

  var intervalMinutes = Math.floor( seconds / secondsInMinute );
  if( intervalMinutes > 0 ){
    seconds = seconds - ( intervalMinutes * secondsInMinute );
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
//  $( '#status' ).html( p_data );
// return 0;
  var p_data = JSON.parse( p_data );

  var v_stuff = '';
  var v_last_contact = '';

  var answer = p_data.answer;
  var message = answer.message;
  var status = answer.status;
  if( status == 0 ){
    var locations = answer.locations;

    v_last_contact = '<p style="font-size: xx-small;">';
    for( ll = 0; ll < locations.length; ll++ ){
      loc = locations[ll];
      var loc_name = loc.name;
      var thermostats = loc.thermostats;

      v_stuff = v_stuff + '<p>At ' + loc_name;
      for( tt = 0; tt < thermostats.length; tt++ ){
        var stat = thermostats[tt];
        var stat_name = stat.name;
        var stat_date = stat.present_time;
        var stat_temperature = stat.temperature;
        var stat_heat_state = stat.heater;
        var stat_compressor_state = stat.compressor;
        var stat_fan_state = stat.fan;
        var stat_last_read_date = stat.last_read_date;

        // QQQ Add check to display F or C depending on setting after the &deg; symbol.
        v_stuff = v_stuff + '<br>Thermostat ' + stat_name + ' says that it is ' + stat_temperature + '&deg; at ' + stat_date;
        v_stuff = v_stuff + '<br><img src="images/img_trans.gif" width="1" height="1" class="large_sprite heater_'+stat_heat_state+'" alt="heat" title="The heater is '+ stat_heat_state +'" /> The heater is ' + stat_heat_state + '.';
        v_stuff = v_stuff + '<br><img src="images/img_trans.gif" width="1" height="1" class="large_sprite compressor_'+stat_compressor_state+'" alt="cool" title="The compressor is '+ stat_compressor_state +'" /> The compressor is ' + stat_compressor_state + '.';
        v_stuff = v_stuff + '<br><img src="images/img_trans.gif" width="1" height="1" class="large_sprite fan_'+stat_fan_state+'" alt="fan" title="The fan is '+ stat_fan_state +'" /> The fan is ' + stat_fan_state + '.';

        v_last_contact = v_last_contact + '<br>' + 'Last contact from ' + loc_name + '/' + stat_name + ' was at ' + timeSince( new Date( stat_last_read_date ) );
      }
      v_stuff = v_stuff + '</p>';
    }
    v_last_contact = v_last_contact + '</p>';
  }
  else{
    v_stuff = "<p><img src='images/Alert.png'/ alt='alert'>No soup for you!</p>";
  }
  $( '#status' ).html( v_stuff + v_last_contact );
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
//    lastCollection = lastCollection + '<br>Last contact from MTU(' + meter.mtu[ii].mtu + ') was ' + timeSince( new Date( meter.mtu[ii].date ), 1 );
    lastCollection = lastCollection + '<br>Last contact from MTU(' + meter.mtu[ii].mtu + ') was ' + timeSince( new Date( meter.mtu[ii].date ), 0 );
  }
  $( '#electro' ).html( presentDate + presentUse + presentProjectedUse + lastCollection );
}

function update( p_section, p_time ){
  var time = typeof p_time !== 'undefined' ? p_time : 3000;
  if( [ 'status', 'forecast', 'electro' ].indexOf( p_section ) >= 0){
    $( '#'+p_section ).html( $( '#'+p_section ).data( 'default' ) );
    // QQQ get_instant.php should morph into the generic Controller for all Model requests.
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

<div class='thrm_row'>
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


<div  style='visibility: visible;'>
<section style='text-align: left;'>
<style>
fieldset
{
  border: 2px solid red;
  background-color: #DDDDDD;
  max-width: 650px;
  margin-left: 10px;
  padding: 0px;
}
legend
{
  width: auto;
  margin-left: 10px;
  padding-left: 10px;
  padding-right: 10px;
}
ul.a
{
  list-style-type: circle;
  padding-left: 30px;
}
</style>

  <fieldset>
    <legend>To Do List</legend>
    <ul class='a'>
      <li>Why is my fieldset not totally outlined in red?  I seem to be inheriting a legend style from somewhere else!</li>
      <li>Update the JavaScript time-ago to understand future too.</li>
      <li>Fix ALL data inserts to set alarm when insert date is future (by more than some threshhold)</li>
      <li>This means I need an alarm state table (per user) that shows on dashboard first, and turns on LIFX later, can also email user (but not spam him!)</li>
      <li>Can have user enabled alarms like</li>
      <ul class='a'>
        <li>Too cold inside</li>
        <li>Too hot inside</li>
        <li>Outdoor temp (or forecast) is freezing - go check the spigots are covered!</li>
        <li>HVAC running but change in temp not happening as expected</li>
        <li>Data not being collected from device</li>
      </ul>
      <li>Need to drop locations table <b>AFTER MAKING SURE NO CODE REFERENCES IT</b></li>
      <li>Need to drop location_data table <b>AFTER MAKING SURE NO CODE REFERENCES IT</b></li>
      <li>Why does users table link to outdxoors table?</li>
      <li>Outdoor data could add cloud cover column (for solar panel)</li>
      <li>Need a degree day table linked to outdoor data.  Can either compute it based on data gathered myself or look it up.</li>
      <li>Need to add a lightbulb table</li>
      <li>For each of lightbulb, meter, thermostat DB have a file that has the model defined for it.  This is the record passed into the constructor for the device communiation model code</li>
      <li>Maybe a file name update to show MVC better? (the lib files are essentially part of the model layer)</li>
    </ul>
  </fieldset>
</section>
</div>
<?php
  require_once( 'standard_page_foot.php' );
?>