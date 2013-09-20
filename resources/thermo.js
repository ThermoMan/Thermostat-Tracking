"use strict";

	/**
	* chart is one of 'daily' or 'history'
	* sytle is one of 'chart' or 'table'
		*
		*/
function display_chart( chart, style )
{
	var chart_target;
	var table_flag = '';
	
	chart_target = document.getElementById( 'daily_temperature_chart' );
	if( chart == 'daily' && style == 'chart' )
	{
		chart_target.src = 'images/daily_temperature_placeholder.png';	// Redraw the placekeeper while the chart is rendering
		// By using setTimeout we can separate the drawing of the placeholder image from the actual chart such that the browser will always draw the placeholder
		setTimeout(function(){ display_chart_build_and_display(chart, style, 'false', chart_target);}, 500);
	}
	else if( chart == 'daily' && style == 'table' )
	{
		table_flag = 'table_flag=true';
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
		document.getElementById( 'daily_temperature_table' ).innerHTML = '<iframe src="'+url_string+'" height="100" width="530"></iframe>';
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


var refreshInterval = 20 * 60 * 1000;	// It's measured in milliseconds and I want a unit of minutes.
function timedRefresh()
{
	// Save value (either true or false) for next time (keep cookie up to ten days)
	setCookie( 'auto_refresh', document.getElementById( 'auto_refresh' ).checked, 10 );

	if( document.getElementById( 'auto_refresh' ).checked == true )
	{
		document.getElementById( 'daily_update' ).style.visibility = 'visible';
		/**
			* Need to add a check here for present day.	Only present day actually has changing data
			* so don't bother with refresh on historic data.	But do leave the refresh flag set for later.
			*/
		update_daily_chart();
		setTimeout( 'timedRefresh();', refreshInterval );
	}
	else
	{
		document.getElementById( 'daily_update' ).style.visibility = 'hidden';
	}
}

/**
	* Default cookies last for ten years.
	*
	* exdays is an optional parameter that defaults to ten years when missing.
	*/
function setCookie( c_name, value, exdays )
{
	// Chrome does not like to see '=' in the argument list of a function declaration.  Here is plan B.
	if( typeof( exdays ) === 'undefined' ) exdays = 3650;

	var exdate = new Date();
	exdate.setDate( exdate.getDate() + exdays );
	var c_value = escape(value) + ( ( exdays == null ) ? '' : '; expires = ' + exdate.toUTCString() );
	document.cookie = c_name + '=' + c_value;
}

// Got a problem here because "false" in the cookie is coming back as being "set" and it should not
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
		setCookie( 'chart.daily.fromDate', '', -1 );
		setCookie( 'chart.daily.toDate', '', -1 );

		document.getElementById('chart.daily.setpoint').className = '';
		document.getElementById('chart.daily.showHeat').className = '';
		document.getElementById('chart.daily.showCool').className = '';
		document.getElementById('chart.daily.showFan').className = '';
		document.getElementById('chart.daily.fromDate').className = '';
		document.getElementById('chart.daily.toDate').className = '';
	}

	if( chart == 1 )
	{	// Clear cookies that remember history settings
		setCookie( 'chart.history.toDate', '', -1 );

		document.getElementById('chart.history.toDate').className = '';
	}
}

/**
	* Either set up a countdown timer that both updates this clock AND triggers the chart update when hitting 0
	* or set up a second timer that is a countdown clock and hope it stays in sync with the update routine.
	*/
function showRefreshTime()
{
	var today = new Date();
	var h = '' + today.getHours();
	var m = '' + today.getMinutes();
	//var s = today.getSeconds();
	if( h < 10 ) h = '0' + h;
	if( m < 10 ) m = '0' + m;

	document.getElementById( 'daily_update' ).innerHTML = 'Countdown to refresh: ' + h + ':' + m;
}

function doLogout()
{
	alert( 'Not implemented yet.' );
}

var xmlDoc;
function processStatus()
{
	if( xmlDoc.readyState != 4 ) return ;

	//document.getElementById( 'status' ).innerHTML = xmlDoc.responseText;

	// For testing make it look like 3 thermostats
	document.getElementById( 'status' ).innerHTML = xmlDoc.responseText +'<br>'+
																									'The data is manually triplicated to simulate multiple stats in a single location.<br>' +
																									xmlDoc.responseText +'<br>'+
																									xmlDoc.responseText;

}

function updateStatus()
{
	// Need to add the Wheels icon to the sprite map and set relative position in the thermo.css file
	document.getElementById( 'status' ).innerHTML = "<p class='status'><img src='images/img_trans.gif' width='1' height='1' class='wheels' />Looking up present conditions. (This may take some time)</p>";
	
	if( typeof window.ActiveXObject != 'undefined' )
	{
		xmlDoc = new ActiveXObject( 'Microsoft.XMLHTTP' );
		xmlDoc.onreadystatechange = process ;
	}
	else
	{
		xmlDoc = new XMLHttpRequest();
		xmlDoc.onload = processStatus;
	}
	// Replace use of session ID here with thermo.seed and a pseudorandom generator.  Send both the prng and the iteration number
	// On server side, check that iteration number matches what it should be (each one used once and that the prng is right (because seed is stored there too)
	//var session_id = getCookie( 'thermo.session' );
	
	xmlDoc.open( 'GET', 'get_instant_status.php', true );
	xmlDoc.send( null );
}

var xmlDoc2;
function processForecast()
{
	if( xmlDoc2.readyState != 4 ) return ;

	document.getElementById( 'forecast' ).innerHTML = xmlDoc2.responseText;
}

function updateForecast()
{
	// Need to add the Wheels icon to the sprite map and set relative position in the thermo.css file
	document.getElementById( 'forecast' ).innerHTML = "<p class='status'><img src='images/img_trans.gif' width='1' height='1' class='wheels' />Looking up the forecast. (This may take some time)</p>";
	
	if( typeof window.ActiveXObject != 'undefined' )
	{
		xmlDoc2 = new ActiveXObject( 'Microsoft.XMLHTTP' );
		xmlDoc2.onreadystatechange = process ;
	}
	else
	{
		xmlDoc2 = new XMLHttpRequest();
		//xmlDoc.onload = processForecast;
		xmlDoc2.onload = function(){return processForecast()};	// Can use arguments in here if I wrap in an anonymous function
	}
	// Replace use of session ID here with thermo.seed and a pseudorandom generator.  Send both the prng and the iteration number
	// On server side, check that iteration number matches what it should be (each one used once and that the prng is right (because seed is stored there too)
	//var session_id = getCookie( 'thermo.session' );
	
	xmlDoc2.open( 'GET', 'get_instant_forecast.php', true );
	xmlDoc2.send( null );
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
