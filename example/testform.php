<?php

if (!isset($TEMPLATE)) {
  // template functions
  include_once 'functions.inc.php';

  // defines the $CONFIG hash of configuration variables
  include_once '../conf/config.inc.php';

  $TITLE = 'DYFI Responses Example Index';
  $HEAD = '<link rel="stylesheet" href="css/index.css"/>';
  $FOOT = '<script src="js/index.js"></script>';

  include 'template.inc.php';
}

$ini = parse_ini_file('../src/conf/config.ini');
$RESPONSE = $ini['TEST_RESPONSE_URL']; 
$formlink = "<form action = '$RESPONSE' method='POST'>\n";
?>

<div id="application">
  <noscript>
    <a href="https://www.google.com/search?q=javascript">
      This page requires javascript.
    </a>
  </noscript>
</div>

<p>Test blank form:
<?php echo $formlink; ?>
<input type="submit" name="ciim_report" value="Submit Form">
</form>
</p>

<p>Test partial form:
<?php echo $formlink; ?>
<input type="hidden" name="ciim_mapLat" value="33.5001">
<input type="hidden" name="ciim_mapLon" value="-116.2301">
<input type="hidden" name="form_version" value="1.3">
<input type="hidden" name="fldSituation_felt" value="1_felt">
<input type="hidden" name="fldEffects_shelved" value="1 few_toppled_or_fell">
<input type="submit" name="ciim_report" value="Submit Form">
</form></p>

<p>Test fully completed form:
<?php echo $formlink; ?>
<input type="hidden" name="ciim_mapLat" value="33.5001">
<input type="hidden" name="ciim_mapLon" value="-116.2301">
<input type="hidden" name="ciim_mapConfidence" value="4">
<input type="hidden" name="code" value="testcode001">
<input type="hidden" name="network" value="us">
<input type="hidden" name="dyficode" value="ustestcode001">
<input type="hidden" name="ciim_time" value="5 mins ago">
<input type="hidden" name="form_version" value="1.3">
<input type="hidden" name="fldSituation_felt" value="1_felt">
<input type="hidden" name="fldSituation_situation" value="inside">
<input type="hidden" name="fldSituation_sleep" value="no">
<input type="hidden" name="fldSituation_others" value="3">
<input type="hidden" name="fldExperience_shaking" value="4">
<input type="hidden" name="fldExperience_reaction" value="3">
<input type="hidden" name="fldExperience_response" value="ran_outside">
<input type="hidden" name="fldExperience_stand" value="1">
<input type="hidden" name="fldEffects_shelved" value="1 few_toppled_or_fell">
<input type="hidden" name="d_text" value="_tiltedwall">
<input type="hidden" name="fldEffects_furniture" value="1">
<input type="hidden" name="fldContact_comments" value="a test comment">
<input type="submit" name="ciim_report" value="Submit Form">
</form></p>


