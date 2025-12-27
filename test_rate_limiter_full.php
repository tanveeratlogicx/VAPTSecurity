<?php
// Load WordPress
// Define WP_USE_THEMES false to minimize overhead
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';
header('Content-Type: text/plain');

if (!class_exists('VAPT_Rate_Limiter')) {
  die("VAPT_Rate_Limiter class not found.");
}

// Reset for clean slate
$limiter = new VAPT_Rate_Limiter();
$ip = $limiter->get_current_ip();
echo "Testing Rate Limiter for IP: " . $ip . "\n";

$limiter->reset_ip_data($ip);
echo "Reset data for IP.\n\n";

// Get Config
$config = VAPT_Security::instance()->get_config();
echo "Config Dump:\n";
print_r($config);

$limit_form = isset($config['rate_limit_max']) ? (int)$config['rate_limit_max'] : 10;
$limit_cron = isset($config['cron_rate_limit']) ? (int)$config['cron_rate_limit'] : 60; // 60 is implicit default in class

echo "Form Limit: $limit_form\n";
echo "Cron Limit: $limit_cron\n";
echo "--------------------------------------------------\n";

// TEST 1: Form Rate Limit
echo "TEST 1: Form Rate Limit Simulation\n";
$blocked = 0;
$allowed = 0;
$simulate = $limit_form + 5;

for ($i = 0; $i < $simulate; $i++) {
  $limiter = new VAPT_Rate_Limiter(); // Re-instantiate to mimic fresh request logic if needed
  if ($limiter->allow_request()) {
    $allowed++;
    echo "Request Only $i: Allowed\n";
  } else {
    $blocked++;
    echo "Request Only $i: BLOCKED\n";
  }
}
echo "Result: Allowed: $allowed, Blocked: $blocked\n";
if ($blocked > 0) {
  echo "SUCCESS: Form Rate Limit Triggered.\n";
} else {
  echo "FAILURE: Form Rate Limit DID NOT Trigger.\n";
}
echo "--------------------------------------------------\n";


// TEST 2: Cron Rate Limit
echo "TEST 2: Cron Rate Limit Simulation\n";
$blocked_cron = 0;
$allowed_cron = 0;
$simulate_cron = $limit_cron + 5;

// Reset cron data specifically
$limiter->reset_ip_data($ip);

for ($i = 0; $i < $simulate_cron; $i++) {
  $limiter = new VAPT_Rate_Limiter();
  if ($limiter->allow_cron_request()) {
    $allowed_cron++;
    // echo "Cron $i: Allowed\n";
  } else {
    $blocked_cron++;
    echo "Cron $i: BLOCKED\n";
  }
}

echo "Result: Allowed: $allowed_cron, Blocked: $blocked_cron\n";
if ($blocked_cron > 0) {
  echo "SUCCESS: Cron Rate Limit Triggered.\n";
} else {
  echo "FAILURE: Cron Rate Limit DID NOT Trigger.\n";
}
