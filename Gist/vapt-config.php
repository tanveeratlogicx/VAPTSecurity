<?php
/**
 * VAPT Security Plugin Configuration
 *
 * This file allows you to customize plugin behavior and define test URLs.
 * This file should be located in the plugin directory.
 */

// Feature Enable/Disable Configuration
if (!defined('VAPT_FEATURE_WP_CRON_PROTECTION')) {
    define('VAPT_FEATURE_WP_CRON_PROTECTION', true);
}
if (!defined('VAPT_FEATURE_RATE_LIMITING')) {
    define('VAPT_FEATURE_RATE_LIMITING', true);
}
if (!defined('VAPT_FEATURE_INPUT_VALIDATION')) {
    define('VAPT_FEATURE_INPUT_VALIDATION', true);
}
if (!defined('VAPT_FEATURE_SECURITY_LOGGING')) {
    define('VAPT_FEATURE_SECURITY_LOGGING', true);
}

// Test URLs Configuration
if (!defined('VAPT_TEST_WP_CRON_URL')) {
    define('VAPT_TEST_WP_CRON_URL', '/wp-cron.php');
}
if (!defined('VAPT_TEST_FORM_SUBMISSION_URL')) {
    define('VAPT_TEST_FORM_SUBMISSION_URL', '/wp-admin/admin-ajax.php');
}

// Feature Info Display
if (!defined('VAPT_SHOW_FEATURE_INFO')) {
    define('VAPT_SHOW_FEATURE_INFO', true);
}

// Advanced Settings
if (!defined('VAPT_CLEANUP_INTERVAL')) {
    define('VAPT_CLEANUP_INTERVAL', 3600); // 1 hour in seconds
}
if (!defined('VAPT_LOG_RETENTION_DAYS')) {
    define('VAPT_LOG_RETENTION_DAYS', 30);
}

// Whitelisted IPs (these IPs will never be blocked)
if (!defined('VAPT_WHITELISTED_IPS')) {
    define('VAPT_WHITELISTED_IPS', [
        '127.0.0.1',
        '::1',
        // Add your trusted IPs here
    ]);
}

// Custom Messages
if (!defined('VAPT_RATE_LIMIT_MESSAGE')) {
    define('VAPT_RATE_LIMIT_MESSAGE', 'Too many requests. Please try again later.');
}
if (!defined('VAPT_INVALID_NONCE_MESSAGE')) {
    define('VAPT_INVALID_NONCE_MESSAGE', 'Invalid request. Please refresh the page and try again.');
}

// Debug Mode (only enable for testing)
if (!defined('VAPT_DEBUG_MODE')) {
    define('VAPT_DEBUG_MODE', false);
}