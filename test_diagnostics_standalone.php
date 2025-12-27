<?php
// Standalone diagnostic script - NO WP LOAD

// Define paths relative to this script
// Script is in plugins/VAPTSecurity/
$base_dir = dirname(dirname(dirname(__DIR__))); // app/public
$upload_dir = $base_dir . '/wp-content/uploads';
$data_file = $upload_dir . '/vapt_cron_data.json';
$debug_log = $base_dir . '/wp-content/debug.log';

echo "--- Standalone Diagnostics ---\n";
echo "Base Dir: $base_dir\n";
echo "Upload Dir: $upload_dir\n";
echo "Data File: $data_file\n";

// 1. Check Data File
if (file_exists($data_file)) {
  echo "File EXISTS.\n";
  // Check Permissions
  echo "Permissions: " . substr(sprintf('%o', fileperms($data_file)), -4) . "\n";
  echo "Readable: " . (is_readable($data_file) ? 'Yes' : 'No') . "\n";
  echo "Writable: " . (is_writable($data_file) ? 'Yes' : 'No') . "\n";

  // Check Content
  $content = file_get_contents($data_file);
  echo "Content Size: " . strlen($content) . " bytes\n";
  echo "Content Preview: " . substr($content, 0, 100) . "...\n";

  $json = json_decode($content, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON ERROR: " . json_last_error_msg() . "\n";
  } else {
    echo "JSON VALID. Keys: " . count($json) . "\n";
    print_r($json);
  }
} else {
  echo "File DOES NOT EXIST.\n";
  echo "Upload Dir Writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "\n";
}

// 2. Check Debug Log
echo "\n--- Debug Log ---\n";
if (file_exists($debug_log)) {
  echo "Debug Log EXISTS.\n";
  $lines = file($debug_log);
  $last = array_slice($lines, -20);
  foreach ($last as $l) {
    echo $l;
  }
} else {
  echo "Debug Log NOT FOUND at $debug_log\n";
}
