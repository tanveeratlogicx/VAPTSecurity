<?php
/**
 * Test script to verify configuration loading
 */

// Load WordPress environment
require_once '../../../wp-load.php';

// Check if constants are defined
echo "VAPT_FEATURE_WP_CRON_PROTECTION: " . (defined('VAPT_FEATURE_WP_CRON_PROTECTION') ? (VAPT_FEATURE_WP_CRON_PROTECTION ? 'true' : 'false') : 'not defined') . "\n";
echo "VAPT_FEATURE_INPUT_VALIDATION: " . (defined('VAPT_FEATURE_INPUT_VALIDATION') ? (VAPT_FEATURE_INPUT_VALIDATION ? 'true' : 'false') : 'not defined') . "\n";
echo "VAPT_FEATURE_RATE_LIMITING: " . (defined('VAPT_FEATURE_RATE_LIMITING') ? (VAPT_FEATURE_RATE_LIMITING ? 'true' : 'false') : 'not defined') . "\n";
echo "VAPT_FEATURE_SECURITY_LOGGING: " . (defined('VAPT_FEATURE_SECURITY_LOGGING') ? (VAPT_FEATURE_SECURITY_LOGGING ? 'true' : 'false') : 'not defined') . "\n";