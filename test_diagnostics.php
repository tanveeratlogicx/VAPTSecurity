<?php
// Load WordPress environment
// Adjusted path logic for typical plugin structure
// Attempt to find wp-load.php by traversing up
$path = __DIR__;
for ($i = 0; $i < 5; $i++) {
  if (file_exists($path . '/wp-load.php')) {
    require_once $path . '/wp-load.php';
    break;
  }
  $path = dirname($path);
}

if (!defined('ABSPATH')) {
  die("Error: Could not load WordPress environment.\n");
}

echo "Loaded WordPress.\n";

if (!class_exists('VAPT_Rate_Limiter')) {
  die("Error: VAPT_Rate_Limiter class not found.\n");
}

// 1. Diagnostics: File Permissions
$upload_dir = wp_upload_dir();
$data_file = $upload_dir['basedir'] . '/vapt_cron_data.json';
echo "Data File: $data_file\n";

if (file_exists($data_file)) {
  echo "File exists.\n";
  echo "Permissions: " . substr(sprintf('%o', fileperms($data_file)), -4) . "\n";
  echo "Readable: " . (is_readable($data_file) ? 'Yes' : 'No') . "\n";
  echo "Writable: " . (is_writable($data_file) ? 'Yes' : 'No') . "\n";

  // Check content validity
  $content = file_get_contents($data_file);
  echo "Content Length: " . strlen($content) . "\n";
  $json = json_decode($content, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
  } else {
    echo "JSON Valid. Data count: " . count($json) . "\n";
  }
} else {
  echo "File does not exist.\n";
  echo "Upload Dir Writable: " . (is_writable($upload_dir['basedir']) ? 'Yes' : 'No') . "\n";
}

// 2. Simulation Test
echo "\n--- Simulation Test ---\n";
// Force config for test
$limiter = new VAPT_Rate_Limiter();
$ip = $limiter->get_current_ip();
echo "Simulation IP: $ip\n";

// Reset first
$limiter->reset_ip_data($ip);
echo "Data reset for IP.\n";

$blocked_count = 0;
$allowed_count = 0;

// Manually verify logic by checking class method return
for ($i = 0; $i < 30; $i++) {
  $result = $limiter->allow_cron_request();
  if ($result) {
    $allowed_count++;
  } else {
    $blocked_count++;
  }
}

echo "Simulation Results (Limit determined by settings):\n";
echo "Allowed: $allowed_count\n";
echo "Blocked: $blocked_count\n";

// 3. Check for specific error logs
echo "\n--- Error Log Check ---\n";
$log_path = dirname($wp_load_path) . '/wp-content/debug.log'; // assumption
if (file_exists($log_path)) {
  echo "debug.log found.\n";
  // Grep last 10 lines
  $lines = file($log_path);
  $last_lines = array_slice($lines, -10);
  echo "Last 10 lines:\n";
  foreach ($last_lines as $line) {
    echo $line;
  }
} else {
  echo "debug.log not found at $log_path\n";
}
