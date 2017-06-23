<?php

include_once 'inc/response.inc.php';

// defines the $CONFIG hash of configuration variables
include_once '../conf/config.inc.php';

if (!isset($ini)) {

	// Process the ini filefile for directory locations and 
	// credentials to the ArcGISOnline server.

	$ini = parse_ini_file('../conf/config.ini');
	$data_dir = $ini['WRITE_DIR'];
	$log_dir = "$data_dir/log";
        
	$rawData = file_get_contents("php://input");
	file_put_contents("$log_dir/raw.input",$rawData);
}

if (!isset($TEMPLATE)) {
  $TITLE = 'DYFI Questionnaire Result v{{VERSION}}';
  $NAVIGATION = true;

  include 'template.inc.php';
}

// This script must do 4 things:
// 1. Write to a file
// 2. Lookup intensity, numresp for this event and location (removed)
// 3. Compute intensity for this response
// 4. Output summary HTML 

// Firstly validate the form

$savefile = 1;
if (!isset($_POST['fldSituation_felt'])) {
	$savefile = 0;
	print '<p class="alert warning">' .
	'Required entries were not provided!' .
	' Please re-submit the form after answering all required questions.' .
	'</p>';
}

// Fix PHP not parsing multiple checkboxes
$_POST['d_text[]'] = $_POST['d_text'];
unset($_POST['d_text']); // dont repeat the $_POST variable

// Main loop

$server = $ini['SERVER_SHORTNAME'];
$backends = explode(',',$ini['BACKEND_SERVERS']);

$eventid = eventid();
$windowtype = param('windowtype');
$form_version = param('form_version', '1.1');
$language = param('language', 'en');

// Do we need to do a geocode?
$mapAddress = param('ciim_mapAddress', 'null');
if ($mapAddress != 'null') {
	include_once('../lib/geocode.php');
	try {
		$client_id = $ini['ARCGIS_CLIENT_ID'];
	        $client_secret = $ini['ARCGIS_CLIENT_SECRET'];
		geocode($client_id,$client_secret);
	} catch (Exception $ex) {
		// Oh well, we tried...
	}
}

// Write to file

$time = time();
$count = getmypid();

// Build $post from $_POST rather than php://input because we may have
// modified $_POST d_text
$post = array();

if (is_array($_POST)) {
	foreach ($_POST as $k=>$v) {
		if (is_array($v)) {
			$post[] = "$k=" . implode('&' . $k .'=',$v);
		} else {
			$post[] = "$k=$v";
		}
	}
}

$post = str_replace(' ', '+', implode('&', $post));
$raw = 'timestamp=' . $time . '&server=' . $server . '&' . $post;
file_put_contents($log_dir . '/latest_entry.post',$raw);

if ($savefile) {
	$basename = sprintf("entry.%s.%s.%s.%s",
		$server,$eventid,$time,$count);

	$out_template = $data_dir . "/incoming.%s/" . $basename;
	foreach ($backends as $backend) {
		$dest = sprintf($out_template,$backend);
		file_put_contents($dest, $raw);
	}
	file_put_contents($data_dir . "/backup/" . $basename,$raw);
} 

// Get language translation

$translate_file = 'inc/labels.' . $language . '.inc.php';
if (!file_exists($translate_file)) {
	$translate_file = 'inc/labels.en.inc.php';
}
include_once($translate_file);

if (!isset($TEMPLATE)) {
    include 'minimal.inc.php';
  }

$data = $_POST;
$data['eventid'] = $eventid;
$cdi = _rom(compute_intensity());
if (isset($cdi)) {
	$data['your_cdi'] = $cdi;
}

// Lookup of other entries is disabled for now
// if (isset($lookup)) {
//	$data['all_cdi'] = $lookup['rom_cdi'];
//	$data['nresp'] = $lookup['nresp'];
//}

if ($savefile) {
	echo '<p class="alert success">' . $T['THANKS_LABEL'] . '</p>';
}
echo '<dl>';

$OUTPUT = array (
	'eventid' => $T['EVENTID_LABEL'],
	'your_cdi' => $T['ESTIMATED_II_LABEL'],
	'all_cdi' => $T['COMMUNITY_II_LABEL'],
	'nresp' => $T['NRESP_LABEL'],
	'ciim_zip' => $T['ZIPCODE_LABEL'],
	'ciim_city' => $T['CITY_LABEL'],
	'ciim_region' => $T['REGION_LABEL'],
	'ciim_country' => $T['COUNTRY_LABEL'],
	'fldContact_name' => $T['NAME_LABEL'],
	'fldContact_email' => $T['EMAIL_LABEL'],
	'fldContact_phone' => $T['PHONE_LABEL'],
	'ciim_address' => $T['ADDRESS_LABEL'],
	'ciim_time' => $T['EVENTTIME_LABEL'],
	'ciim_mapLat' => 'Latitude',
	'ciim_mapLon' => 'Longitude',

	'filename' => "Output",
	'form_version' => "Form version",
); 

foreach($OUTPUT as $key => $desc) {
	if (!array_key_exists($key, $data)) {
		continue;
	}

	$val = $data[$key];
	if (!$val) {
		continue;
	}

	if ($key == 'ciim_city' || $key == 'ciim_region' || $key == 'ciim_country') {
		$val = _strip_code($val);
		if (!$val) {
			continue;
		}
	}

	// Loop over results and append the rows
	echo '<dt>' . $desc . '</dt>';
	echo '<dd>' . htmlspecialchars($val) . '</dd>';
}

echo '</dl>';

if ($form_version < 1.2) {

	if ($windowtype == 'enabled') {
		echo '<p><a href="javascript:window.close()">' . $T['CLOSE_LABEL'] . '</a></p>';
	} else {
		if (isset($eventid) and $eventid != '' and $eventid <> 'unknown') {
            echo '<p><a href="https://earthquake.usgs.gov/earthquakes/eventpage/' . $eventid . '#dyfi">' 
					. $T['BACK_EVENT_LABEL'] 
					. '</a></p>';
		} else {
			echo '<p><a href="https://earthquake.usgs.gov/data/dyfi/">' . $T['BACK_HOMEPAGE_LABEL'] . '</a></p>';
		}
	}

}

?>
