<?php

  // A collection of functions to --
  // 1. Look up a CDI file in the directory structure
  // events/net/evid/us/cdi_zip.xml. If it exists,
  // read and process it.
  // 2. Compute intensity from raw output.
  // 3. Create the "thank you" and response summary message.

  function eventid() {

    if (isset($_POST['eventid'])) return $_POST['eventid'];
    if (!isset($_POST['code'])) return 'unknown';

    $evid = $_POST['code'];
    $net = $_POST['network'];
    if ($evid=='') return 'unknown';
    if ($net == 'Unknown' and $evid == 'Event') return 'unknown';
    if ($evid == 'unknown') return 'unknown';
    return $net . $evid;
  }


  function _rom($ii) {
    if ($ii <= 1) return 'I';
    if ($ii < 2.5) return 'II';

    $ii = (int)($ii+0.5);
    if ($ii>12) $ii = 12;

    $ROM = array( '','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');

    $rom = $ROM[$ii];
    return $rom;
  }

  // functions for intensity computation

  function compute_intensity() {
    $CDI_INPUTS = array(
      'felt' => 5,
      'fldExperience_shaking'  => 1,
      'fldExperience_reaction' => 1,
      'fldExperience_stand'    => 2,
      'fldEffects_shelved'     => 5,
      'fldEffects_pictures'   => 2,
      'fldEffects_furniture' => 3,
      'damage' => 5,
    );

    // compute special values from felt/otherfelt and d_text

    // bug in felt value on form
    if ($_POST['fldSituation_felt'] == '2 no')
      $_POST['fldSituation_felt'] = '0 no';
    $_POST['felt'] = other2felt();
    $_POST['damage'] = dtext2damage();

    $cws = 0;
    foreach ($CDI_INPUTS as $key => $weight) {
      if (!isset($_POST[$key])) continue;

      $val = $_POST[$key];
      $c = stripos($val,' ');
      if ($c) $val = substr($val,0,$c);

      $cws += $val * $weight;
    }

    if ($cws <= 0) return 1;
    $cdi = sprintf("%1.1f",(3.3996 * log($cws) - 4.3781));
    if ($cdi < 1) return 1;
    if ($cdi < 2) return 2;
    return $cdi;
  }

  function other2felt() {
    $other = isset($_POST['fldSituation_others']) ? $_POST['fldSituation_others'] : null;
    $felt = $_POST['fldSituation_felt'];

    if ($other == 3) { return 0.72; }
    if ($other > 3)  { return 1.0; }

    return $felt;
  }

  function dtext2damage() {
    $D_LABEL = array (
      3 => array( '_move', '_chim', '_found', '_collapse',
                     '_porch', '_majormodernchim', '_tiltedwall' ),
      2 => array( '_wall', '_pipe', '_win', '_brokenwindows',
                     '_majoroldchim', '_masonryfell' ),
      1 => array( '_crackwallmany', '_crackwall','_crackfloor',
                     '_crackchim', '_tilesfell' ),
      0.75 => array( '_crackwallfew' ),
      0.5 => array( '_crackmin', '_crackwindows' )
    );

    if (array_key_exists('d_text[]',$_POST)) {
      // d_text is an array
      $text = $_POST['d_text[]'];
    }
    elseif (array_key_exists('d_text',$_POST)) {
      // d_text might be array or string
      $text = $_POST['d_text'];
      if (!is_array($text)) {
        // check for either a space, or a comma delimiter
        if (strpos($text, ' ')) {
          $text = explode(' ', $text);
        } else {
          $text = explode(',',$text);
        }
      }
    }
    else {
      return 0;
    }
    if (in_array('none',$text)) return 0;

    foreach($D_LABEL as $dam => $vals) {
      foreach($vals as $val) {
        if (in_array($val,$text)) {
          return $dam; }
      }
    }
    return 0;
  }

  //
  //
  // These functions are for future or not-yet-implemented functionality
  //
  //


  function lookup_cdi_file() {
    $evid = $_POST['code'];
    $net = $_POST['network'];
    if (is_null($evid) or $evid == 'unknown') return;

    $file = '';
    if ($loc = $_POST['ciim_zip']) {
      $file = "events/$net/$evid/us/cdi_zip.xml";
    }
    elseif ($loc = get_lookup_location()) {
      $file = "events/$net/$evid/us/cdi_zip.xml";
    }
//    print "Looking for file $file.<br>\n";
    if (is_null($file) or !file_exists($file)) {
      return;
    }

    // Now start parsing XML file

    $xml = new XMLReader();
    $xml->open($file);

    // Advance to correct location

    while ($xml->read()) {
      if ($xml->name != 'location' or
          $xml->nodeType != 1) continue;

      $name = $xml->getAttribute('name');
      if ($name == $loc) break;
    }

    // Now extract cdi and nresp values

    $cdi = null;
    $nresp = null;
    $result = null;

    while($xml->read()) {

      $name = $xml->name;
      $type = $xml->nodeType;

      if ($name == 'cdi' and $type == 1) {
        $xml->read();
        $cdi = $xml->value;
      }

      if ($name == 'nresp' and $type == 1) {
        $xml->read();
        $nresp = $xml->value;
      }

      if ($name == 'location' and $type == 15) break;
    }

    $xml->close();

    if ($cdi and $nresp) $result = array(
      'cdi'     => $cdi,
      'rom_cdi' => _rom($cdi),
      'nresp'   => $nresp);
    return($result);
  }


  function get_lookup_location() {
    $city = _strip_code($_POST['ciim_city']);
    $region = _strip_code($_POST['ciim_region']);
    $country = _strip_code($_POST['ciim_country']);

    if ($city and $region and $country)
    return "$city::$region::$country";
  }


  function _strip_code($string) {
    $c = stripos($string,' ');
    if (!$c) return;

    return substr($string,$c+1);
  }

  // generate error response in html or json
  function generateError ($format, $basename) {
    $email = 'gs-haz_dev_team_group@usgs.gov';
    $subject = urlencode('Error Processing DYFI Form ' . $basename);
    $message = "Something went wrong, please " .
        "<a href=\"mailto:${email}?subject=${subject}\">contact us</a> to report this " .
        "problem!";

    header("HTTP/1.1 500 Internal Server Error");

    if ($format && $format === 'json') {
      $error = [
        'error' => [
          'code' => 500,
          'message' => $message
        ]
      ];
      header('Content-Type: application/json');
      echo json_encode($error);
    } else {
      // return a 500 server error
      echo '<p class="alert warning">' . $message . '</p>';
    }
  }

?>
