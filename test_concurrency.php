<?php
// Test Concurrency for VAPT Security Cron Rate Limiter
// Usage: php test_concurrency.php

// URL to test (from user screenshot/context)
$url = 'http://wptest.local/wp-cron.php?doing_wp_cron=' . time() . '&vapt_test=1';
$count = 30; // 30 requests

echo "Testing URL: $url\n";
echo "Sending $count concurrent requests...\n";

$mh = curl_multi_init();
$handles = [];

for ($i = 0; $i < $count; $i++) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_NOBODY, false);
  // Short timeout to return quickly
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);

  curl_multi_add_handle($mh, $ch);
  $handles[$i] = $ch;
}

// Execute
$running = null;
do {
  curl_multi_exec($mh, $running);
} while ($running);

// Analyze results
$http_200 = 0;
$http_429 = 0;
$errors = 0;

foreach ($handles as $ch) {
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($code == 200) {
    $http_200++;
  } elseif ($code == 429) {
    $http_429++;
  } else {
    $errors++;
    echo "Error Code: $code\n";
  }
  curl_multi_remove_handle($mh, $ch);
  curl_close($ch);
}

curl_multi_close($mh);

echo "Results:\n";
echo "Allowed (200): $http_200\n";
echo "Blocked (429): $http_429\n";
echo "Errors: $errors\n";

if ($http_429 > 0) {
  echo "SUCCESS: Rate limiting triggered.\n";
} else {
  echo "FAILURE: No blocking detected.\n";
}
