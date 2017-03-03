<?php

// Assumes this file is in [reponame]/src/lib/
$REPO_DIR = dirname(dirname(__DIR__)); 

// This data structure allows for simple configuration prompts
$PROMPTS = array(
  // 'key' => array(
  //  'prompt' => String,  // Prompt to request value from user
  //  'default' => String, // Value to use if input is empty
  //  'secure' => Boolean  // True if input should not be echo'd to console
  // )

  'MOUNT_PATH' => array(
    'prompt' => 'URL Path for application',
    'default' => '/mount/path',
    'secure' => false
  ),
  'SERVER_SHORTNAME' => array(
    'prompt' => 'Short name for this acquisition server (e.g. dev)',
    'default' => 'dev',
    'secure' => false
  ),
  'WRITE_DIR' => array(
    'prompt' => 'directory to write incoming and log directories',
    'default' => $REPO_DIR . '/test/data',
    'secure' => false
  ),
  'ARCGIS_CLIENT_ID' => array(
    'prompt' => 'ArcGIS Client ID for on-the-fly geocoding of responses',
    'default' => 'my_id',
    'secure' => false
  ),
  'ARCGIS_CLIENT_SECRET' => array(
    'prompt' => 'ArcGIS Secret Password',
    'default' => 'password',
    'secure' => true
  ),
  'BACKEND_SERVERS' => array(
    'prompt' => 'Comma-delimited list of backend servers',
    'default' => 'backendserver1.gov,backendserver2.gov',
    'secure' => false
  ),
  'TEST_RESPONSE_URL' => array(
    'prompt' => 'Destination for testing response.php',
    'default' => 'http://localhost:8880/response.php',
    'secure' => false
  ),
);


if (!function_exists('configure')) {
  function configure ($prompt, $default = null, $secure = false) {

    echo $prompt;
    if ($default != null) {
      echo ' [' . $default . ']';
    }
    echo ': ';

    if (NON_INTERACTIVE) {
      // non-interactive
      echo '(Non-interactive, using default)' . PHP_EOL;
      return $default;
    }

    if ($secure) {
      system('stty -echo');
      $answer = trim(fgets(STDIN));
      system('stty echo');
      echo "\n";
    } else {
      $answer = trim(fgets(STDIN));
    }

    if ($answer == '') {
      $answer = $default;
    }

    return $answer;
  }
}

// This script should only be included by the pre-install.php script. The
// calling script is responsible for defining the $CONFIG_FILE_INI and calling
// date_default_timezone_set prior to including this configuration script.

$CONFIG = array();
if (file_exists($CONFIG_FILE_INI)) {
  $answer = configure('A previous configuration exists. ' .
      'Would you like to use it as defaults?', 'Y|n', false);

  if (strtoupper(substr($answer, 0, 1)) == 'Y') {
    $CONFIG = parse_ini_file($CONFIG_FILE_INI);
    print_r($CONFIG);
  }

  $answer = configure('Would you like to save the old configuration file?',
      'Y|n', false);

  if (strtoupper(substr($answer, 0, 1)) == 'Y') {
    $BAK_CONFIG_FILE = $CONFIG_FILE_INI . '.' . date('YmdHis');
    rename($CONFIG_FILE_INI, $BAK_CONFIG_FILE);
    echo 'Old configuration saved to file: ' . basename($BAK_CONFIG_FILE) .
        "\n";
  }
}


// write config
$FP_CONFIG = fopen($CONFIG_FILE_INI, 'w');
fwrite($FP_CONFIG, ';; auto generated: ' . date('r') . "\n\n");
foreach ($PROMPTS as $key => $item) {
  $default = null;
  if (isset($CONFIG[$key])) {
    $default = $CONFIG[$key];
  } else if (isset($item['default'])) {
    $default = $item['default'];
  }

  $CONFIG[$key] = $default;

  fwrite($FP_CONFIG, $key . ' = "' .
      configure($item['prompt'], $default, isset($item['secure']) ? $item['secure'] : false) .
      "\"\n");
}

// Do any custom prompting here

function createdir ($dir) {
  if (!is_dir($dir)) {
    echo "Creating directory $dir\n";
    mkdir($dir, 0777, true);
    chmod($dir,0777);
  }
}

$datadir = $CONFIG['WRITE_DIR'];
createdir($datadir);
createdir("$datadir/incoming");
createdir("$datadir/log");

$servers = explode(',',$CONFIG['BACKEND_SERVERS']);

foreach ($servers as $server) {
  $dir = "$datadir/incoming.$server";
  createdir($dir);
}

createdir("$datadir/incoming.backup");

// Close the file
fclose($FP_CONFIG);
