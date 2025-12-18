<?php
/**
 * Plugin structure tests
 *
 * @package VAPT_Security_Qoder
 */

// Test that all required files exist
$required_files = [
    'vapt-security-qoder.php',
    'includes/class-rate-limiter.php',
    'includes/class-input-validator.php',
    'includes/class-security-logger.php',
    'includes/class-captcha-handler.php',
    'templates/admin-settings.php',
    'assets/admin.css',
    'README.txt',
    'LICENSE',
    'uninstall.php'
];

foreach ($required_files as $file) {
    $path = dirname(__FILE__) . '/../' . $file;
    if (!file_exists($path)) {
        echo "Missing file: " . $file . "\n";
    }
}

echo "Plugin structure test completed.\n";