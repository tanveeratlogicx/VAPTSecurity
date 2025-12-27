<?php
// Load WordPress environment
// Root is 4 levels up: plugins -> wp-content -> public (app/public) -> wp-load.php

$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (!file_exists($wp_load_path)) {
  // Try explicit path if relative fails (fallback for some setups)
  $wp_load_path = __DIR__ . '/../../../wp-load.php';
}

if (!file_exists($wp_load_path)) {
  echo "Error: wp-load.php not found at $wp_load_path\n";
  echo "Current Dir: " . __DIR__ . "\n";
  exit(1);
}

define('WP_USE_THEMES', false);
require_once $wp_load_path;

echo "Loaded WordPress.\n";

if (!class_exists('VAPT_Rate_Limiter')) {
  echo "Error: VAPT_Rate_Limiter class not found.\n";
  exit(1);
}

// Ensure Rate Limiter feature is enabled configuration
$opts = VAPT_Security::instance()->get_config();
echo "Current Config: " . print_r($opts, true) . "\n";

$limiter = new VAPT_Rate_Limiter();
$ip = $limiter->get_current_ip();
echo "Current IP: $ip\n";
echo "Whitelisted: " . (in_array($ip, VAPT_WHITELISTED_IPS) ? 'Yes' : 'No') . "\n";

// Reset first to ensure clean state
echo "Resetting data for IP...\n";
$limiter->reset_ip_data($ip);

echo "Starting 50 requests simulation...\n";

$blocked_count = 0;
$allowed_count = 0;

for ($i = 0; $i < 50; $i++) {
  $allowed = $limiter->allow_cron_request();
  if ($allowed) {
    $allowed_count++;
  } else {
    $blocked_count++;
  }
}

echo "Simulation Complete.\n";
echo "Allowed: $allowed_count\n";
echo "Blocked: $blocked_count\n";

if ($blocked_count > 0) {
  echo "SUCCESS: Rate limiter is working.\n";
} else {
  echo "FAILURE: No requests were blocked.\n";

  // Check file diagnostics
  $upload_dir = wp_upload_dir();
  $data_file = $upload_dir['basedir'] . '/vapt_cron_data.json';
  echo "Data File: $data_file\n";
  if (file_exists($data_file)) {
    echo "File exists.\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($data_file)), -4) . "\n";
    echo "Content: " . file_get_contents($data_file) . "\n";
  } else {
    echo "File does not exist.\n";
    if (is_writable($upload_dir['basedir'])) {
      echo "Upload dir is writable.\n";
    } else {
      echo "Upload dir is NOT writable.\n";
    }
  }
}
