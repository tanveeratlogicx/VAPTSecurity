<?php
/**
 * VAPT Security Plugin Configuration Sample
 * 
 * Copy this file to 'vapt-config.php' and customize the settings below.
 * This file should be located in the plugin directory.
 */

// Feature Enable/Disable Configuration
// Set any of these to false to completely disable the feature
// define('VAPT_FEATURE_WP_CRON_PROTECTION', true);
// define('VAPT_FEATURE_RATE_LIMITING', true);
// define('VAPT_FEATURE_INPUT_VALIDATION', true);
// define('VAPT_FEATURE_SECURITY_LOGGING', true);

// Test URLs Configuration
// These URLs are used for testing the features
// define('VAPT_TEST_WP_CRON_URL', '/wp-cron.php');
// define('VAPT_TEST_FORM_SUBMISSION_URL', '/wp-admin/admin-ajax.php');

// Feature Info Display
// Set to false to hide feature descriptions in the admin interface
// define('VAPT_SHOW_FEATURE_INFO', true);

// Test URL Display
// Set to false to hide test URLs in the admin interface
// define('VAPT_SHOW_TEST_URLS', true);

// Advanced Settings
// define('VAPT_CLEANUP_INTERVAL', 3600); // 1 hour in seconds
// define('VAPT_LOG_RETENTION_DAYS', 30);

// Whitelisted IPs (these IPs will never be blocked)
// define('VAPT_WHITELISTED_IPS', [
//     '127.0.0.1',
//     '::1',
//     // Add your trusted IPs here
// ]);

// Custom Messages
// define('VAPT_RATE_LIMIT_MESSAGE', 'Too many requests. Please try again later.');
// define('VAPT_INVALID_NONCE_MESSAGE', 'Invalid request. Please refresh the page and try again.');

// Debug Mode (only enable for testing)
// define('VAPT_DEBUG_MODE', false);
?>