<?php
  $section = 'xyzzy';
  require_once( 'session.php' );
  require_once( 'standard_page_top.php' );
?>

<!-- These styles are applied to the W3C HTML button on the About tab only and do not need to be part of the .css file -->
<style type='text/css'>
a > div.caveat
{
  display: none;
  text-align: left;
}
a:hover > div.caveat
{
  display: block;
  position: absolute;
  top: 60px;
  left: 100px;
  right: 100px;
  border: 3px double;
  padding: 0px 50px 0px 10px;
  z-index: 100;
  color: #000000;
  background-color: #DCDCDC;
  border-radius: 25px;
}
img.caveat
{
  position: relative;
  width: 19px;
  height: 19px;
  top: 3px;
}
</style>

<p>Source code for this project can be found on <a target='_blank' href='https://github.com/ThermoMan/3M-50-Thermostat-Tracking'><span style="display: inline; position: relative; top: 8px;"><svg height="28" version="1.1" viewBox="0 0 16 16" width="28"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0 0 16 8c0-4.42-3.58-8-8-8z"></path></svg></span> GitHub</a>
<p>
<br><br>The project originated on Windows Home Server v1 running <a target='_blank' href='http://www.apachefriends.org/en/xampp.html'>xampp</a>. Migrated to a 'real host' to solve issues with Windows Scheduler.
<br>I used <a target='_blank' href='http://www.winscp.net'>WinSCP</a> to connect and edited the code using <a target='_blank' href='http://www.textpad.com'>TextPad</a>.
<p>
<p>This project also uses code from the following external projects
<ul style='list-style-type: circle; margin-left: 20px;'>
  <li style='margin-top: 11px;'><a target='_blank' href='http://www.pchart.net/'>pChart</a>.</li>
  <li style='margin-top: 11px;'><a target='_blank' href='http://www.customicondesign.com//'>Free for non-commercial use icons from Custom Icon Designs</a>. These icons are in the package <a target='_blank' href='http://www.veryicon.com/icons/system/mini-1/'>Mini 1 Icons</a>.</li>
  <li >The external temperatures and forecast come from <a target='_blank' href='http://www.wunderground.com/weather/api/'><img style='position:relative; top:10px; height:31px; border:0;' src='http://icons.wxug.com/logos/PNG/wundergroundLogo_4c_horz.png' alt='Weather Underground Logo'></a></li>
</ul>
<br><p>This project is based on the <a target='_blank' href='http://www.radiothermostat.com/filtrete/products/3M-50/'>Filtrete 3M Radio Thermostat</a>.
<br><br><br><br>
<div style='text-align: center;'>
  <a target='_blank' href='http://validator.w3.org/check?uri=referer'><img style='border:0;width:88px;height:31px;' src='images/valid-html5.png' alt='Valid HTML 5'/><div class='caveat'><!-- ANY whitespace between the start of the anchor and the start of the div adds an underscore to the page -->
    <br>
    <ul>
      <li>The first warning '<b><img class='caveat' src='images/w3c_info.png' alt='Info'>Using experimental feature: HTML5 Conformance Checker.</b>' is provisional until the HTML5 specification is complete.</li>
      <li>The 2 reported errors '<b><img class='caveat' src='images/w3c_error.png' alt='Error'>Attribute size not allowed on element input at this point.</b>' reported on use of the attribute "size" where input type="date" are incorrect because the HTML 5 validator is provisional until the specification is complete.</li>
      <li>The 2 other reported warnings '<b><img class='caveat' src='images/w3c_warning.png' alt='Warning'>The date input type is so far supported properly only by Opera. Please be sure to test your page in Opera.</b>' may also be read to include Chrome.</li>
<!--      <li>The final warning '<b><img class='caveat' src='images/w3c_warning.png' alt='Warning'>The scoped attribute on the style element is not supported by browsers yet. It would probably be better to wait for implementations.'</b> complains if the style is not scoped and differently when it is. The style that it is complaining about is local only to this very message and therefore should <i>not</i> be global.</li> -->
    </ul>
    <br>
  </div></a> <!-- ANY whitespace between the end of the div and the end of the anchor adds an underscore to the page -->
  <a target='_blank' href='http://jigsaw.w3.org/css-validator/check/referer'><img style='border:0;width:88px;height:31px;' src='http://jigsaw.w3.org/css-validator/images/vcss' alt='Valid CSS!'/></a>
  <br><br><br>The HTML5 components are tested to work in Chrome, Safari (Mac), Android 4.0.4 default browser. They do not work (manually type in the date) in Firefox.  I've not tested the functionality in IE.  The HTML validator suggests that the HTML 5 components may also work in Opera.
</div>

<?php
  require_once( 'standard_page_foot.php' );
?>