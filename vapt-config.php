<?php
/**
 * VAPT Security Plugin Configuration
 *
 * @package VAPT_Security
 */

// Feature Enable/Disable Configuration
// Set to false to disable specific security features
if (!defined('VAPT_FEATURE_WP_CRON_PROTECTION')) {
    define('VAPT_FEATURE_WP_CRON_PROTECTION', false);
}
if (!defined('VAPT_FEATURE_RATE_LIMITING')) {
    define('VAPT_FEATURE_RATE_LIMITING', false);
}
if (!defined('VAPT_FEATURE_INPUT_VALIDATION')) {
    define('VAPT_FEATURE_INPUT_VALIDATION', false);
}
if (!defined('VAPT_FEATURE_SECURITY_LOGGING')) {
    define('VAPT_FEATURE_SECURITY_LOGGING', true);
}

// Test URLs Configuration
// URLs used for testing each security feature
if (!defined('VAPT_TEST_WP_CRON_URL')) {
    define('VAPT_TEST_WP_CRON_URL', '/wp-cron.php');
}
if (!defined('VAPT_TEST_FORM_SUBMISSION_URL')) {
    define('VAPT_TEST_FORM_SUBMISSION_URL', '/wp-admin/admin-ajax.php');
}

// Feature Info Display
// Controls whether to show feature descriptions and test URLs
if (!defined('VAPT_SHOW_FEATURE_INFO')) {
    define('VAPT_SHOW_FEATURE_INFO', true);
}
if (!defined('VAPT_SHOW_TEST_URLS')) {
    define('VAPT_SHOW_TEST_URLS', true);
}

// Cleanup Settings
// Interval for cleaning up old rate limit data and logs
if (!defined('VAPT_CLEANUP_INTERVAL')) {
    define('VAPT_CLEANUP_INTERVAL', 3600); // 1 hour
}
if (!defined('VAPT_LOG_RETENTION_DAYS')) {
    define('VAPT_LOG_RETENTION_DAYS', 30);
}

// Whitelisted IPs
// IPs that are exempt from rate limiting
if (!defined('VAPT_WHITELISTED_IPS')) {
    define('VAPT_WHITELISTED_IPS', [
        '127.0.0.1',
        '::1'
    ]);
}

// Custom Messages
// Customize messages shown to users
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