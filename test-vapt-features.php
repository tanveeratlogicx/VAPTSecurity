<?php
/**
 * VAPT Security Plugin - Test Script
 * 
 * This script demonstrates how to test the VAPT Security plugin features.
 * 
 * USAGE:
 * 1. Place this file in your WordPress root directory
 * 2. Access it via browser: http://yoursite.com/test-vapt-features.php
 * 3. Follow the instructions to test each feature
 * 
 * WARNING: This is for testing purposes only. Remove this file after testing.
 */

// Ensure we're running in WordPress context
if (!defined('ABSPATH')) {
    require_once('wp-load.php');
}

// Check if user is logged in (security measure)
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. You must be logged in as administrator to use this test script.');
}

// Test functions
function test_rate_limiting() {
    echo "<h3>Testing Rate Limiting</h3>";
    echo "<p>To test rate limiting:</p>";
    echo "<ol>";
    echo "<li>Create a simple HTML form that submits to the plugin's AJAX handler</li>";
    echo "<li>Submit the form multiple times in quick succession</li>";
    echo "<li>After reaching the limit, subsequent submissions should be blocked</li>";
    echo "<li>Check the plugin's Statistics tab to see request tracking</li>";
    echo "</ol>";
    
    // Example form
    echo "<h4>Sample Test Form:</h4>";
    echo "<form method='post' action='" . admin_url('admin-ajax.php') . "'>";
    echo "<input type='hidden' name='action' value='vapt_form_submit'>";
    echo "<input type='hidden' name='nonce' value='" . wp_create_nonce('vapt_form_action') . "'>";
    echo "<p>Name: <input type='text' name='name' value='Test User'></p>";
    echo "<p>Email: <input type='email' name='email' value='test@example.com'></p>";
    echo "<p>Message: <textarea name='message'>Test message</textarea></p>";
    echo "<p><input type='submit' value='Submit Test Form'></p>";
    echo "</form>";
}

function test_wp_cron_protection() {
    echo "<h3>Testing WP-Cron Protection</h3>";
    echo "<p>To test WP-Cron protection:</p>";
    echo "<ol>";
    echo "<li>Access your site's wp-cron.php URL repeatedly in a browser</li>";
    echo "<li>Example: http://yoursite.com/wp-cron.php</li>";
    echo "<li>After reaching the limit, requests should be blocked with a 429 error</li>";
    echo "<li>Check the plugin's logs to see blocked requests</li>";
    echo "</ol>";
}

function test_input_validation() {
    echo "<h3>Testing Input Validation</h3>";
    echo "<p>To test input validation:</p>";
    echo "<ol>";
    echo "<li>Submit forms with various types of input through the test form above</li>";
    echo "<li>Try HTML/script tags, special characters, and SQL injection attempts</li>";
    echo "<li>Observe how different sanitization levels handle each input type</li>";
    echo "<li>Check logs for validation failures</li>";
    echo "</ol>";
}

function test_security_logging() {
    echo "<h3>Testing Security Logging</h3>";
    echo "<p>To test security logging:</p>";
    echo "<ol>";
    echo "<li>Perform various actions that trigger security events</li>";
    echo "<li>Check the Security Logging tab in the plugin settings to see recorded events</li>";
    echo "<li>Review the Statistics dashboard for event analysis</li>";
    echo "</ol>";
}

// Display test interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>VAPT Security Plugin - Test Features</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2 { color: #0073aa; }
        h3 { color: #23282d; }
        ol { margin-left: 20px; }
        .notice { background: #fff8e5; border: 1px solid #ffb900; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>VAPT Security Plugin - Feature Testing</h2>
    
    <div class="notice">
        <strong>WARNING:</strong> This is a test script for development purposes only. 
        Remove this file from your server after testing for security reasons.
    </div>
    
    <?php
    test_rate_limiting();
    test_wp_cron_protection();
    test_input_validation();
    test_security_logging();
    ?>
    
    <h3>Additional Testing Tips</h3>
    <ul>
        <li>Use browser developer tools to monitor AJAX requests and responses</li>
        <li>Check server logs for any errors</li>
        <li>Test with different user roles and permissions</li>
        <li>Verify that whitelisted IPs are not blocked</li>
        <li>Test with the configuration file settings enabled/disabled</li>
    </ul>
    
    <p><a href="<?php echo admin_url('options-general.php?page=vapt-security'); ?>">← Back to VAPT Security Settings</a></p>
</body>
</html>
<?php
exit;
?>