<?php
/**
 * VAPT Security Plugin Configuration - SAMPLE FILE
 *
 * This is a sample configuration file. To use it:
 * 1. Copy this file to vapt-config.php in your WordPress root directory
 * 2. Customize the settings as needed
 * 3. The plugin will automatically detect and use these settings
 */

// Feature Enable/Disable Configuration
// Set to false to disable specific features
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
// Customize these URLs for your testing environment
if (!defined('VAPT_TEST_WP_CRON_URL')) {
    define('VAPT_TEST_WP_CRON_URL', '/wp-cron.php');
}
if (!defined('VAPT_TEST_FORM_SUBMISSION_URL')) {
    define('VAPT_TEST_FORM_SUBMISSION_URL', '/wp-admin/admin-ajax.php');
}

// Feature Info Display
// Set to false to hide feature descriptions in admin interface
if (!defined('VAPT_SHOW_FEATURE_INFO')) {
    define('VAPT_SHOW_FEATURE_INFO', true);
}

// Advanced Settings
// Adjust timing intervals and retention periods
if (!defined('VAPT_CLEANUP_INTERVAL')) {
    define('VAPT_CLEANUP_INTERVAL', 3600); // 1 hour in seconds
}
if (!defined('VAPT_LOG_RETENTION_DAYS')) {
    define('VAPT_LOG_RETENTION_DAYS', 30);
}

// Whitelisted IPs (these IPs will never be blocked)
// Add your trusted IPs to this array
if (!defined('VAPT_WHITELISTED_IPS')) {
    define('VAPT_WHITELISTED_IPS', [
        '127.0.0.1',
        '::1',
        // Add your trusted IPs here
        // '192.168.1.100',
        // '10.0.0.50'
    ]);
}

// Custom Messages
// Customize user-facing error messages
if (!defined('VAPT_RATE_LIMIT_MESSAGE')) {
    define('VAPT_RATE_LIMIT_MESSAGE', 'Too many requests. Please try again later.');
}
if (!defined('VAPT_INVALID_NONCE_MESSAGE')) {
    define('VAPT_INVALID_NONCE_MESSAGE', 'Invalid request. Please refresh the page and try again.');
}

// Debug Mode (only enable for testing)
// Set to true to enable detailed logging (not recommended for production)
if (!defined('VAPT_DEBUG_MODE')) {
    define('VAPT_DEBUG_MODE', false);
}

// Custom Rate Limits
// Override default rate limits (uncomment to use)
// if (!defined('VAPT_DEFAULT_MAX_REQUESTS_PER_MINUTE')) {
//     define('VAPT_DEFAULT_MAX_REQUESTS_PER_MINUTE', 10);
// }
// if (!defined('VAPT_DEFAULT_MAX_CRON_REQUESTS_PER_HOUR')) {
//     define('VAPT_DEFAULT_MAX_CRON_REQUESTS_PER_HOUR', 60);
// }