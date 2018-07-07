<?php
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
?>

<style type='text/css'>
div.details
{
  text-align: left;
}
div.electric_chart
{
  height: 500px;
  width: 750px;
  position: relative;
  left: 100px;
  font-size: 90%;
}
#chartdiv {
  width : 100%;
  height  : 500px;
}
</style>

<script type='text/javascript'>
var chart = {};

function update(){
  var submitURL = 'electric_dl2.php';
  $.ajax({
     url:     submitURL
    ,timeout: 6000
  }).done( function( data ){
debugger;
    chart.dataProvider = $.parseJSON( data );
    chart.validateData();
  }).fail( function( jqXHR, textStatus ){
    console.log( 'Error ' + textStatus );
  });
}


$( document ).ready( function(){
  var blankData = [];

  chart = AmCharts.makeChart( 'chartdiv', {
    'type':                   'serial',
    'theme':                  'light',
    'marginRight':            140,
    'marginLeft':             140,
    'autoMarginOffset':       20,
    'mouseWheelZoomEnabled':  true,
    'dataDateFormat':         'YYYY-MM-DD',
    'valueAxes':              [{
                                  'id':               'v1',
                                  'axisAlpha':        0,
                                  'position':         'left',
                                  'ignoreAxisWidth':  true
                              }],
    'balloon': {
        'borderThickness': 1,
        'shadowAlpha': 0
    },
    'graphs': [{
        'id': 'g1'
        ,type: 'column'
        ,'balloon':{
           'drop':              true
          ,'adjustBorderColor': false
          ,'color':             '#ffffff'
        }
        ,'bullet': 'round'
        ,'bulletBorderAlpha': 1
        ,'bulletColor': '#FFFFFF'
        ,'bulletSize': 5
        ,'hideBulletsCount': 1
        ,'lineThickness': 2
        ,'title': 'red line'
        ,'useLineColorForBulletBorder': true
        ,'valueField': 'vl'
        ,'balloonText': '<span style="font-size:18px;">[[vl]]</span>'
    }],
    'chartScrollbar': {
         'graph':                    'g1'
        ,'oppositeAxis':             false
        ,'offset':                   30
        ,'scrollbarHeight':          80
        ,'backgroundAlpha':          0
        ,'selectedBackgroundAlpha':  0.1
        ,'selectedBackgroundColor':  '#888888'
        ,'graphFillAlpha':           0
        ,'graphLineAlpha':           0.5
        ,'selectedGraphFillAlpha':   0
        ,'selectedGraphLineAlpha':   1
        ,'autoGridCount':            true
        ,'color':                    '#AAAAAA'
    },
/*    'chartCursor': {
        'pan':                      true,
        'valueLineEnabled':         true,
        'valueLineBalloonEnabled':  true,
        'cursorAlpha':              1,
        'cursorColor':              '#258cbb',
        'limitToGraph':             'g1',
        'valueLineAlpha':           0.2,
        'valueZoomable':            true
    }, */
/*    'valueScrollbar':{
      'oppositeAxis':     false,
      'offset':           50,
      'scrollbarHeight':  10
    }, */
    'categoryField': 'date',
    'categoryAxis': {
        'parseDates':       true,
        'dashLength':       1,
        'minorGridEnabled': true
    },
    'export': {
        'enabled': false
    },
    'dataProvider': blankData
  });

  update();
});

</script>

Electric usage
<div>
  <div id='chartdiv'></div>
</div>

<br /><br /><br /><br />
<hr />
<br /><br /><br /><br />

<?php
  require_once( 'standard_page_foot.php' );
?>