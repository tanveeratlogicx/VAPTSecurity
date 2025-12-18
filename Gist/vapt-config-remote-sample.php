<?php
/**
 * VAPT Security Plugin Remote Configuration
 *
 * This is a sample of what a remote configuration file might look like
 * when hosted on a remote server or GitHub Gist.
 *
 * @package VAPT_Security
 */

// Premium feature configuration
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

// Premium rate limits (higher limits for premium users)
if (!defined('VAPT_DEFAULT_MAX_REQUESTS_PER_MINUTE')) {
    define('VAPT_DEFAULT_MAX_REQUESTS_PER_MINUTE', 100);
}
if (!defined('VAPT_DEFAULT_MAX_CRON_REQUESTS_PER_HOUR')) {
    define('VAPT_DEFAULT_MAX_CRON_REQUESTS_PER_HOUR', 600);
}

// Premium security settings
if (!defined('VAPT_SECURITY_LEVEL')) {
    define('VAPT_SECURITY_LEVEL', 'premium');
}
if (!defined('VAPT_ENABLE_ADVANCED_PROTECTION')) {
    define('VAPT_ENABLE_ADVANCED_PROTECTION', true);
}
if (!defined('VAPT_ENABLE_IP_REPUTATION_CHECK')) {
    define('VAPT_ENABLE_IP_REPUTATION_CHECK', true);
}
if (!defined('VAPT_ENABLE_BEHAVIORAL_ANALYSIS')) {
    define('VAPT_ENABLE_BEHAVIORAL_ANALYSIS', true);
}

// Premium logging and monitoring
if (!defined('VAPT_LOG_LEVEL')) {
    define('VAPT_LOG_LEVEL', 'detailed');
}
if (!defined('VAPT_ENABLE_AUDIT_TRAIL')) {
    define('VAPT_ENABLE_AUDIT_TRAIL', true);
}
if (!defined('VAPT_ENABLE_REALTIME_ALERTS')) {
    define('VAPT_ENABLE_REALTIME_ALERTS', true);
}

// Premium support features
if (!defined('VAPT_ENABLE_PRIORITY_SUPPORT')) {
    define('VAPT_ENABLE_PRIORITY_SUPPORT', true);
}
if (!defined('VAPT_SUPPORT_RESPONSE_TIME_HOURS')) {
    define('VAPT_SUPPORT_RESPONSE_TIME_HOURS', 2);
}

// Premium update settings
if (!defined('VAPT_PREMIUM_UPDATE_CHANNEL')) {
    define('VAPT_PREMIUM_UPDATE_CHANNEL', 'stable');
}