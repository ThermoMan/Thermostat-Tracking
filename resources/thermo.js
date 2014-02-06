"use strict";
/** These are the functions associated with the thermostat viewer web page.
	*
	*/

/**
		* chart is one of 'daily' or 'history'
		* sytle is one of 'chart' or 'table'
		*
		*/
function display_chart( chart, style )
{
	var chart_target;
	var table_flag = '';
	
	if( chart == 'daily' && style == 'chart' )
	{
		chart_target = document.getElementById( 'daily_temperature_chart' );
		chart_target.src = 'images/daily_temperature_placeholder.png';	// Redraw the placekeeper while the chart is rendering
		// By using setTimeout we can separate the drawing of the placeholder image from the actual chart such that the browser will always draw the placeholder
		setTimeout(function(){ display_chart_build_and_display(chart, style, 'false', chart_target);}, 500);
	}
	else if( chart == 'daily' && style == 'table' )
	{
		table_flag = 'table_flag=true';
		chart_target = document.getElementById( 'daily_temperature_table' );
		display_chart_build_and_display( chart, style, table_flag, chart_target );
	}
	else
	{
		alert( 'You asked for '+chart+' and '+style+' and I do not know how to do that (yet).' );
		return;
	}
}

function display_chart_build_and_display( chart, style, table_flag, chart_target )
{

	var show_thermostat_id       = 'id='                          + document.getElementById( 'chart.daily.thermostat' ).value;
	var daily_setpoint_selection = 'chart.daily.setpoint='        + document.getElementById( 'chart.daily.setpoint' ).checked;
	var daily_source_selection   = 'chart.daily.source='          + document.getElementById( 'chart.daily.source' ).value;
	var daily_interval_length    = 'chart.daily.interval.length=' + document.getElementById( 'chart.daily.interval.length' ).value;
	var daily_interval_group     = 'chart.daily.interval.group='  + document.getElementById( 'chart.daily.interval.group' ).value;
	var daily_to_date_string     = 'chart.daily.toDate='          + document.getElementById( 'chart.daily.toDate' ).value;
	var show_heat_cycle_string   = 'chart.daily.showHeat='        + document.getElementById( 'chart.daily.showHeat' ).checked;
	var show_cool_cycle_string   = 'chart.daily.showCool='        + document.getElementById( 'chart.daily.showCool' ).checked;
	var show_fan_cycle_string	   = 'chart.daily.showFan='	        + document.getElementById( 'chart.daily.showFan' ).checked;

	// Browsers are very clever with image caching.	In this case it breaks the web page function.
	var no_cache_string = 'nocache=' + Math.random();

	var url_string = '';
	if( chart == 'daily' )
	{
		url_string = 'draw_daily.php';
	}
	else if( chart == 'history' )
	{
		// This space intentionally left blank (actually I don't recall why it was left blank, but everything seems to work)
	}

	url_string = url_string + '?' + show_thermostat_id + '&' + daily_source_selection + '&' + daily_setpoint_selection + '&' + table_flag + '&' +
							 daily_interval_length + '&' + daily_interval_group + '&' + daily_to_date_string + '&' +
							 show_heat_cycle_string	+ '&' + show_cool_cycle_string	+ '&' + show_fan_cycle_string + '&' +
							 no_cache_string;

	if( style == 'chart' )
	{
		chart_target.src = url_string;
	}
	else if( style == 'table' )
	{	// Right now it assumes the DAILY table.  Fix that later
		// Size should be in the CSS file?
		
		// Gosh this iframe is a whole load of overkill and I hate it.
		chart_target.innerHTML = '<iframe src="'+url_string+'" height="100" width="530"></iframe>';
	}
}


/**
	*	Save the value of the checkbox for later - and update the chart with the new value
	*/
function toggle_daily_flag( flag )
{
	setCookie( flag, document.getElementById(flag).checked );
}

/**
	* chart is one of 'daily' or 'historic'
	*/
function saveDateData( chart )	// Change to use actual name of element...
{
	// These need to be split apart to individual saves
	switch( chart )
	{
		case 'daily':
			setCookie( 'chart.daily.interval.length', document.getElementById( 'chart.daily.interval.length' ).value );	// How many?
			setCookie( 'chart.daily.interval.group',  document.getElementById( 'chart.daily.interval.group' ).value );	// Days, weeks, months, years
			setCookie( 'chart.daily.toDate',          document.getElementById( 'chart.daily.toDate' ).value );					// Ending on date
		break;
		
		case 'historic':	// Change this to literal "history"
			setCookie( 'chart.history.interval.length', document.getElementById( 'chart.history.interval.length' ).value );	// How many?
			setCookie( 'chart.history.interval.group',  document.getElementById( 'chart.history.interval.group' ).value );	// Days, weeks, months, years
			setCookie( 'chart.history.toDate',          document.getElementById( 'chart.history.toDate' ).value );					// Ending on date
		break;
		
		default:
		break;
	}
}

function loadDateData( chart )
{
	switch( chart )
	{
		case 'daily':
			if( getCookie( 'chart.daily.interval.length' ) ) document.getElementById( 'chart.daily.interval.length' ).value = getCookie( 'chart.daily.interval.length' );	// How many?
			if( getCookie( 'chart.daily.interval.group' ) )  document.getElementById( 'chart.daily.interval.group' ).value  = getCookie( 'chart.daily.interval.group' );	// Days, weeks, months, years
			if( getCookie( 'chart.daily.toDate' ) )          document.getElementById( 'chart.daily.toDate' ).value          = getCookie( 'chart.daily.toDate' );					// Ending on date
		break;
		
		case 'historic':
			if( getCookie( 'chart.history.interval.length' ) ) document.getElementById( 'chart.history.interval.length' ).value = getCookie( 'chart.history.interval.length' );	// How many?
			if( getCookie( 'chart.history.interval.group' ) )  document.getElementById( 'chart.history.interval.group' ).value  = getCookie( 'chart.history.interval.group' );	// Days, weeks, months, years
			if( getCookie( 'chart.history.toDate' ) )          document.getElementById( 'chart.history.toDate' ).value          = getCookie( 'chart.history.toDate' );					// Ending on date
		break;
		
		default:
		break;
	}
}


/**
	*	Save the value of the field for later - and update the chart with the new value
	*/
function update_daily_value( field )
{
	setCookie( field, document.getElementById(field).value );
	display_daily_temperature();
}

//Change names of the IDs to match this naming convention 'chart.history.toDate' instead of this convention 'history_to_date'
function display_historic_chart()
{
	var show_thermostat_id = 'id=' + document.getElementById( 'chart.history.thermostat' ).value;
	var show_indoor = 'Indoor=' + document.getElementById( 'history_selection' ).value;
	var show_hvac_runtime = 'show_hvac_runtime=' + document.getElementById( 'show_hvac_runtime' ).checked;

	var interval_measure_string = 'interval_measure=' + document.getElementById( 'interval_measure' ).value;
	var interval_length_string = 'interval_length=' + document.getElementById( 'interval_length' ).value;

	var history_to_date_string = 'history_to_date=' + document.getElementById( 'chart.history.toDate' ).value;

	var no_cache_string = 'nocache=' + Math.random(); // Browsers are very clever with image caching.	That cleverness breaks this web page's function.

	var url_string = 'draw_weekly.php?' + show_thermostat_id + '&' + show_indoor + '&' + show_hvac_runtime + '&' + interval_measure_string + '&' + interval_length_string + '&' + history_to_date_string	+ '&' + no_cache_string;
	console.log( url_string );
	document.getElementById( 'history_chart' ).src = url_string;
}

/**
	* Default cookies last for ten years.
	*
	* exdays is an optional parameter that defaults to ten years when missing.
	*/
function setCookie( c_name, value, exdays )
{
	// Chrome does not like to see '=' in the argument list of a function declaration.  Here is plan B that works in all browsers.
	if( typeof( exdays ) === 'undefined' ) exdays = 3650;	// Three === check type as well as value.

	var exdate = new Date();
	exdate.setDate( exdate.getDate() + exdays );
	var c_value = escape(value) + ( ( exdays == null ) ? '' : '; expires = ' + exdate.toUTCString() );
	document.cookie = c_name + '=' + c_value;
}

// Got a problem here because "false" in the cookie is coming back as being "set" and it should not.
// The work around is to test for a literal "true" in the value when using the cookie and anything that is not true is therefore false.
function getCookie( c_name )
{
	var i, key, value, ARRcookies = document.cookie.split( ';' );
	for( i = 0; i < ARRcookies.length; i++ )
	{
		key = ARRcookies[i].substr( 0, ARRcookies[i].indexOf( '=' ) );
		value = ARRcookies[i].substr( ARRcookies[i].indexOf( '=' ) + 1 );
		key = key.replace( /^\s+|\s+$/g, '' );
		if( key == c_name )
		{
			return unescape( value );
		}
	}
}

/**
	* To erase a cookie, set it with an expiration date prior to now.
	*/
function deleteCookies( chart )
{
	if( chart == 0 )
	{	// Clear cookies that remember daily settings
		setCookie( 'auto_refresh', '', -1 );
		setCookie( 'chart.daily.setpoint', '', -1 );
		setCookie( 'chart.daily.showHeat', '', -1 );
		setCookie( 'chart.daily.showCool', '', -1 );
		setCookie( 'chart.daily.showFan', '', -1 );
		setCookie( 'chart.daily.interval.length', '', -1 );
		setCookie( 'chart.daily.interval.group', '', -1 );
		setCookie( 'chart.daily.toDate', '', -1 );

		/* These are left over from the failed experiment to set a background color when a value came from a cookie
		   The experiment failed because browsers do not let you set styles on ALL the imputs.  Several inherit from the OS
		document.getElementById('chart.daily.setpoint').className = '';
		document.getElementById('chart.daily.showHeat').className = '';
		document.getElementById('chart.daily.showCool').className = '';
		document.getElementById('chart.daily.showFan').className = '';
		document.getElementById('chart.daily.interval.length').className = '';
		document.getElementById('chart.daily.interval.group').className = '';
		document.getElementById('chart.daily.toDate').className = '';
		*/
	}

	if( chart == 1 )
	{	// Clear cookies that remember history settings
		setCookie( 'chart.history.interval.length', '', -1 );
		setCookie( 'chart.history.interval.group', '', -1 );
		setCookie( 'chart.history.toDate', '', -1 );

		/* These are left over from the failed experiment to set a background color when a value came from a cookie
		   The experiment failed because browsers do not let you set styles on ALL the imputs.  Several inherit from the OS
		document.getElementById('chart.history.interval.length').className = '';
		document.getElementById('chart.history.interval.group').className = '';
		document.getElementById('chart.history.toDate').className = '';
		*/
	}
}

// Expected values are -1 or +1
function interval( direction )
{
	var valueString = document.getElementById( 'chart.daily.toDate' ).value;	// Hold in intermediate variable for debugging
	var stupidDate = new Date( valueString );	// This date is stupid because '2014-02-06' becomes '2014-02-05 18:00'
	var toDate = new Date( stupidDate.getTime() + (stupidDate.getTimezoneOffset()*60*1000) );	// The time zone offset is presented in minutes
	
	var oneDay = 86400000;  // 24*60*60*1000 (milliseconds)
	var multiplier;
	switch( document.getElementById( 'chart.daily.interval.group' ).value )
	{
		case '1':
			// Weeks
			multiplier = 7;
		break;
		case '2':
			// Months.  Yes, technically depending on WHICH month it is, this should be a different length.
			multiplier = 30;
		break;
		case '3':
			// Years
			multiplier = 365;
		break;
		default:
			// Days and catchall
			multiplier = 1;
		
	}
	var intervalLength = document.getElementById( 'chart.daily.interval.length' ).value * (oneDay * multiplier);
	var nextDate;
	if( direction == 1 )
	{	// Compute next interval ending date
		nextDate = new Date( toDate.getTime() + intervalLength );
	}
	else
	{	// Compute previous interval ending date
		nextDate = new Date( toDate.getTime() - intervalLength );
	}
	var monthString = nextDate.getMonth() + 1; // Because getMonth() is zero based
	if( monthString < 10 ) monthString = '0' + monthString;
	var dateString = nextDate.getDate();
	if( dateString < 10 ) dateString = '0' + dateString;
	valueString = '' + nextDate.getFullYear() + '-' + monthString + '-' + dateString; 	// Hold in intermediate variable for debugging
	document.getElementById( 'chart.daily.toDate' ).value = valueString;
	
	display_chart( 'daily', 'chart' );	// Now go show the new interval
}


/**
	* Process return from Ajax call.
	*/
function processAjaxResponse( doc, action )
{
	if( doc.readyState != 4 ) return;	// Not done cooking yet, ignore...

	switch( action )
	{
		case 'conditions':
			document.getElementById( 'status' ).innerHTML = doc.responseText;
		break;
		
		case 'forecast':
			document.getElementById( 'forecast' ).innerHTML = doc.responseText;
		break;
		
		case 'backup':
			document.getElementById( 'backup' ).innerHTML = doc.responseText;
		break;
		
		default:
			// Do nothing - not even complain!
		break;
	}
}

/**
	* Make Ajax call to get present data to fill in blanks on the dashboard
	*/
function update( action )
{
	var xmlDoc;
	
	// Need to add the Wheels icon to the sprite map and set relative position in the thermo.css file
	switch( action )
	{
		case 'conditions':
			document.getElementById( 'status' ).innerHTML = "<p class='status'><table><tr><td><img src='images/img_trans.gif' width='1' height='1' class='wheels' /></td><td>Looking up present status and conditions. (This may take some time)</td></tr></table></p>";
		break;
		
		case 'forecast':
			//alert( 'If it is true, I should see this.  It is (' + document.getElementsByName('useForecast')[0].value + ')' );
			
			// The hidden input field may only have a string value, it may not have a Boolean, so have to test for literal "false"
			if( document.getElementsByName('useForecast')[0].value === 'false' )
			{	// Only bother asking for info if we're set up to ask for it.
				return;
			}
			//alert( 'If it is false, I should not see this.  It is (' + document.getElementsByName('useForecast')[0].value + ')' );
			document.getElementById( 'forecast' ).innerHTML = "<p class='status'><table><tr><td><img src='images/img_trans.gif' width='1' height='1' class='wheels' /></td><td>Looking up the forecast.</td></tr></table></p>";
		break;
		
		case 'backup':
			document.getElementById( 'backup' ).innerHTML = "Backup started";
		break;
		
		default:
			// Do nothing - not even complain!
		break;
	}
	
	// Please someone test that this actually works in IE (I don't even have IE on my system)
	if( typeof window.ActiveXObject != 'undefined' )
	{
		xmlDoc = new ActiveXObject( 'Microsoft.XMLHTTP' );
		xmlDoc.onreadystatechange = function(){ processAjaxResponse( xmlDoc, action ); };
	}
	else
	{
		xmlDoc = new XMLHttpRequest();
		xmlDoc.onload = function(){return processAjaxResponse( xmlDoc, action ); };	// Can use arguments in here if I wrap in an anonymous function
	}
	// Replace use of session ID here with thermo.seed and a pseudorandom generator.  Send both the prng and the iteration number
	// On server side, check that iteration number matches what it should be (each one used once and that the prng is right (because seed is stored there too)
	//var session_id = getCookie( 'thermo.session' );
	
	switch( action )
	{
		case 'conditions':
			xmlDoc.open( 'GET', 'get_instant_status.php', true );
		break;
		
		case 'forecast':
			xmlDoc.open( 'GET', 'get_instant_forecast.php', true );
		break;
		
		case 'backup':
			xmlDoc.open( 'GET', 'backup.php', true );
		break;
		
		default:
			// Do nothing - not even complain!
		break;
	}

	xmlDoc.send( null );
}

function backup()
{
	//alert( 'Not imlemented yet.' );
	update( 'backup' );	// Need a more accurate name than "update" if I'm going to use it for this too!
}

function switch_style( css_title )
{
	// You may use this script on your site free of charge provided
	// you do not remove this notice or the URL below. Script from
	// http://www.thesitewizard.com/javascripts/change-style-sheets.shtml
	var i, link_tag;
	for( i = 0, link_tag = document.getElementsByTagName('link'); i < link_tag.length ; i++ )
	{
		if( (link_tag[i].rel.indexOf( 'stylesheet' ) != -1 ) && link_tag[i].title )
		{
			link_tag[i].disabled = true ;
			if( link_tag[i].title == css_title )
			{
				link_tag[i].disabled = false;
			}
		}
	}
}
