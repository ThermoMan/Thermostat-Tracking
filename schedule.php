<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
  if( ! $util::checkThermostat( $user ) ) return;
?>

<script type='text/javascript'>
function copy( mode, day ){
  document.getElementById( 'd'+day+'p0time'+mode ).value = document.getElementById( 'd'+(day-1)+'p0time'+mode ).value;
  document.getElementById( 'd'+day+'p1time'+mode ).value = document.getElementById( 'd'+(day-1)+'p1time'+mode ).value;
  document.getElementById( 'd'+day+'p2time'+mode ).value = document.getElementById( 'd'+(day-1)+'p2time'+mode ).value;
  document.getElementById( 'd'+day+'p3time'+mode ).value = document.getElementById( 'd'+(day-1)+'p3time'+mode ).value;

  document.getElementById( 'd'+day+'p0temp'+mode ).value = document.getElementById( 'd'+(day-1)+'p0temp'+mode ).value;
  document.getElementById( 'd'+day+'p1temp'+mode ).value = document.getElementById( 'd'+(day-1)+'p1temp'+mode ).value;
  document.getElementById( 'd'+day+'p2temp'+mode ).value = document.getElementById( 'd'+(day-1)+'p2temp'+mode ).value;
  document.getElementById( 'd'+day+'p3temp'+mode ).value = document.getElementById( 'd'+(day-1)+'p3temp'+mode ).value;

}

function setDefaults( mode ){
  // Defaults should perhaps be in config.php?
  var coolDefaults = [
      // Period 1                         Period 2                         Period 3                         Period 4
     [ { "time": '08:00', "temp": 75 }, { "time": '11:00', "temp": 80 }, { "time": '15:00', "temp": 75 }, { "time": '23:00', "temp": 72 } ],  // Sunday
     [ { "time": '09:00', "temp": 80 }, { "time": '12:00', "temp": 75 }, { "time": '13:00', "temp": 80 }, { "time": '18:00', "temp": 72 } ],  // Monday
     [ { "time": '09:00', "temp": 80 }, { "time": '12:00', "temp": 75 }, { "time": '13:00', "temp": 80 }, { "time": '18:00', "temp": 72 } ],  // Tuesday
     [ { "time": '09:00', "temp": 80 }, { "time": '12:00', "temp": 75 }, { "time": '13:00', "temp": 80 }, { "time": '18:00', "temp": 72 } ],  // Wednesday
     [ { "time": '09:00', "temp": 80 }, { "time": '12:00', "temp": 75 }, { "time": '13:00', "temp": 80 }, { "time": '18:00', "temp": 72 } ],  // Thursday
     [ { "time": '09:00', "temp": 80 }, { "time": '12:00', "temp": 75 }, { "time": '13:00', "temp": 80 }, { "time": '18:00', "temp": 72 } ],  // Friday
     [ { "time": '09:00', "temp": 75 }, { "time": '15:00', "temp": 80 }, { "time": '18:00', "temp": 75 }, { "time": '14:06', "temp": 72 } ]   // Saturday
  ];

  var heatDefaults = [
     [ { "time": '09:00', "temp": 70 }, { "time": '10:00', "temp": 71 }, { "time": '12:00', "temp": 72 }, { "time": '14:00', "temp": 73 } ],
     [ { "time": '09:01', "temp": 70 }, { "time": '10:01', "temp": 71 }, { "time": '12:01', "temp": 72 }, { "time": '14:01', "temp": 73 } ],
     [ { "time": '09:02', "temp": 70 }, { "time": '10:02', "temp": 71 }, { "time": '12:02', "temp": 72 }, { "time": '14:02', "temp": 73 } ],
     [ { "time": '09:03', "temp": 70 }, { "time": '10:03', "temp": 71 }, { "time": '12:03', "temp": 72 }, { "time": '14:03', "temp": 73 } ],
     [ { "time": '09:04', "temp": 70 }, { "time": '10:04', "temp": 71 }, { "time": '12:04', "temp": 72 }, { "time": '14:04', "temp": 73 } ],
     [ { "time": '09:05', "temp": 70 }, { "time": '10:05', "temp": 71 }, { "time": '12:05', "temp": 72 }, { "time": '14:05', "temp": 73 } ],
     [ { "time": '09:06', "temp": 70 }, { "time": '10:06', "temp": 71 }, { "time": '12:06', "temp": 72 }, { "time": '14:06', "temp": 73 } ]
  ];

  var defaults = heatDefaults;
  //if( document.getElementById( 'mode' ).value == 0 )
  if( mode == 0 ){
    defaults = coolDefaults;
  }

  for( var day = 0; day < defaults.length; day++ ){
    for( var period = 0; period < defaults[day].length; period++ ){
      document.getElementById( 'd'+day+'p'+period+'time'+mode ).value = defaults[day][period].time;
      document.getElementById( 'd'+day+'p'+period+'temp'+mode ).value = defaults[day][period].temp;
    }
  }

}
</script>

</br>
Mode: <select name='mode' id='mode'>
  <option value='0'>Cool</option>
  <option value='1' default>Heat</option>
  <option value='2'>Auto</option>
</select>';
Away: <input type='number'>
Fan: <select name='mode' id='mode'>
  <option value='0'>On</option>
  <option value='1' default>Auto</option>
</select>';

&nbsp;&nbsp;<input type='button' title='Fetch latest schedule from thermostat (may be same as already shown)' value='Rescan'>
<form id='coolSched' action='index.php' method='post' accept-charset='UTF-8'>
<fieldset class='schedule'>
<legend>Cooling</legend>
<input type='hidden' name='ac' value='coolSched'>
<table class='schedule'>
<?php
  $str = '';
  $str .= '<colgroup>';
  foreach( array( 'odd', 'even', 'odd', 'even', 'odd', 'even', 'odd' ) as $parity ){
    foreach( array( 'time', 'temp' ) as $column ){
      $str .= "<col class='$parity $column' />";
    }
  }
  $str .= '</colgroup>';
  echo $str;
?>
<tr class='day'>
<td colspan='2'>Sunday</td><td colspan='2'>Monday</td><td colspan='2'>Tuesday</td><td colspan='2'>Wednesday</td><td colspan='2'>Thursday</td><td colspan='2'>Friday</td><td colspan='2'>Saturday</td>
</tr>

<?php
  /**
    * Somehow I need to be terrifically clever with tabindex as I want the tab order to be other than HTML object creation order.
    * I want to go through all four TIME and then all four TEMP fields for one day before going to the next.
    * Also need to set focus to the proper (TBD) element when tab becomes visible each time.
    *
    * tabindex is not per form, it is global on this tab (I really hope it is NOT global per web page!)
    * Drat, it does seem to be global per web page - manually tabbing from one tab to the next disappears
    *  into that tabs inputs WITHOUT making that tab visible/uppermost.
    *
    * Need also to determine what place in the tab key ordering the Copy buttons appear.
    */
  $str = '';
  $timeTabIndex = 1;
  $tempTabIndex = 5;
  for( $period = 0; $period < 4; $period++ ){
    $str .= '<tr>';
    for( $day = 0; $day < 7; $day++ ){
      // I need some way to validate the string as a time 00:00 through 23:59
      $str .= "<td class='time'><input type='time' tabindex='{$timeTabIndex}' id='d{$day}p{$period}time0' size='5' maxlength='5'></td>";

      // I need some way to validate the string as a temperature within limits of say 50 through 90
      // OK, the HTML type=number supports limits, but also the server MUST NOT TRUST the data and validate range limits.
      $str .= "<td class='temp'><input type='number' tabindex='{$tempTabIndex}' id='d{$day}p{$period}temp0' size='2' maxlength='2' min='50' max='90'></td>";
      $timeTabIndex += 8;
      $tempTabIndex += 8;
    }
    $timeTabIndex -= 55;
    $tempTabIndex -= 55;
    $str .= '</tr>';
  }
  $str .= "<tr class='day'><td colspan='2'>&nbsp;</td>";
  for( $day = 1; $day < 7; $day++ ){
    $str .= "<td colspan='2'><input type='button' title='Copy previous day schedule to today' value='Copy' onClick='javascript: copy(0,$day);'></td>";
  }
  $str .= "</tr>";
  echo $str;
?>

</table>
<input type='button' title='Load default cooling schedule' value='Defaults' onClick='javascript: setDefaults(0);'>
<!--    // Need new table in DB that holds schedule -->
<input type='button' title='Reload most recent saved cooling schedule' value='Restore'> <!-- Ajax call to server to fetch program from DB -->
<input type='button' title='Send changes to thermostat' value='Save'> <!-- Ajax call to server to send new schedule to thermostat (and cue server to re-downloadschedule to archive) -->
<!--    // Need new cron task.  Once per day download schedule.  If it is different save it (and email user?) -->
</fieldset>
</form>


</br>
<form id='heatSched' action='index.php' method='post' accept-charset='UTF-8'>
<fieldset class='schedule'>
<legend>Heating</legend>';
<input type='hidden' name='ac' value='heatSched'>
<table class='schedule'>
<?php
  $str = '';
  $str .= '<colgroup>';
  foreach( array( 'odd', 'even', 'odd', 'even', 'odd', 'even', 'odd' ) as $parity ){
    foreach( array( 'time', 'temp' ) as $column ){
      $str .= "<col class='$parity $column' />";
    }
  }
  $str .= '</colgroup>';
  echo $str;
?>

<tr class='day'>
<td colspan='2'>Sunday</td><td colspan='2'>Monday</td><td colspan='2'>Tuesday</td><td colspan='2'>Wednesday</td><td colspan='2'>Thursday</td><td colspan='2'>Friday</td><td colspan='2'>Saturday</td>
</tr>
<?php
  $str = '';
  $timeTabIndex = 57;
  $tempTabIndex = 61;
  for( $period = 0; $period < 4; $period++ ){
    $str .= '<tr>';
    for( $day = 0; $day < 7; $day++ ){
      $str .= "<td class='time'><input type='time' tabindex='{$timeTabIndex}' id='d{$day}p{$period}time1' size='5' maxlength='5'></td>";
      $str .= "<td class='temp'><input type='number' tabindex='{$tempTabIndex}' id='d{$day}p{$period}temp1' size='2' maxlength='2'></td>";
      $timeTabIndex += 8;
      $tempTabIndex += 8;
    }
    $timeTabIndex -= 55;
    $tempTabIndex -= 55;
    $str .= '</tr>';
  }
  $str .= "<tr class='day'><td colspan='2'>&nbsp;</td>";
  for( $day = 1; $day < 7; $day++ ){
    $str .= "<td colspan='2'><input type='button' title='Copy previous day schedule to today' value='Copy' onClick='javascript: copy(1,$day);'></td>";
  }
  $str .= "</tr>";
  echo $str;
?>
</table>
<input type='button' title='Load default heating schedule' value='Defaults' onClick='javascript: setDefaults(1);'>
<input type='button' title='Reload most recent saved heating schedule' value='Restore'>
<input type='button' title='Send changes to thermostat' value='Save'>
</fieldset>
</form>

<?php
  require_once( 'standard_page_foot.php' );
?>