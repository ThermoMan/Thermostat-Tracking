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

  var secondsInYear = 31556926;   // All years are 365.2422 days (31556926.08 seconds).
  var secondsInMonth = 2629743;   // All months are 30.43685 days (2629743.84 seconds).  Because who really cares about February anyway?
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