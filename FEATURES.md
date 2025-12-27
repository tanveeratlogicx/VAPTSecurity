# VAPT Security Plugin Features

This document provides detailed information about each feature of the VAPT Security plugin, including what the settings mean and how to test them.

## Table of Contents
1. [WP-Cron Protection](#wp-cron-protection)
2. [Rate Limiting on Form Submission](#rate-limiting-on-form-submission)
3. [Input Validation](#input-validation)
4. [Security Logging](#security-logging)
5. [Server Hardening](#server-hardening)
6. [Domain Control (Superadmin)](#domain-control-superadmin)
7. [Testing the Features](#testing-the-features)
8. [Configuration File](#configuration-file)

## WP-Cron Protection

### Feature Description
Protects against Denial of Service (DoS) attacks targeting the WordPress cron system by implementing rate limiting on wp-cron.php access.

### Settings
- **Disable WP-Cron**: Disables the default WordPress cron system. When enabled, you should set up system-level cron jobs to trigger wp-cron.php periodically.
- **Enable Cron Rate Limiting**: Enables rate limiting specifically for wp-cron.php requests.
- **Max Cron Requests per Hour**: Maximum number of wp-cron.php requests allowed from a single IP address per hour.

### How It Works
1. Monitors all requests to wp-cron.php
2. Tracks request frequency per IP address
3. Blocks IPs that exceed the configured limit
4. Automatically unblocks IPs after a period of time

### Testing WP-Cron Protection
1. Access `/wp-cron.php` repeatedly in a browser or with curl
2. After reaching the limit, requests should be blocked with a 429 response
3. Check the Security Logging tab to see blocked requests
4. Test with whitelisted IPs to verify they are not blocked

## Rate Limiting on Form Submission

### Feature Description
Prevents abuse of form submission endpoints by limiting the number of requests from a single IP address within a specified time window.

### Settings
- **Max Requests per Minute**: Maximum number of form submissions allowed from a single IP address per minute.
- **Rate Limit Window (minutes)**: Time window in minutes for rate limiting calculations.

### How It Works
1. Tracks all form submission attempts by IP address
2. Maintains a timestamp record of recent requests
3. Blocks requests when the limit is exceeded within the time window
4. Automatically unblocks after the time window expires
5. Blocks persistent violators permanently

### Testing Rate Limiting
1. Submit a form multiple times in quick succession
2. After reaching the limit, subsequent submissions should be blocked
3. Wait for the time window to expire and try again
4. Check the Statistics tab to see request tracking

## Input Validation

### Feature Description
Provides multiple levels of input sanitization to prevent cross-site scripting (XSS) and other injection attacks.

### Settings
- **Require Valid Email?**: Enforces email validation on all form submissions.
- **Sanitization Level**: 
  - **Basic**: Minimal sanitization, allows most characters
  - **Standard**: Balanced approach, removes most harmful content
  - **Strict**: Maximum security, removes all but essential characters

### How It Works
1. Validates all incoming form data against defined schemas
2. Applies different levels of sanitization based on settings
3. Rejects submissions that fail validation
4. Logs validation failures for security monitoring

### Testing Input Validation
1. Submit forms with various types of input:
   - Normal text
   - HTML/script tags
   - Special characters
   - SQL injection attempts
2. Observe how different sanitization levels handle each input type
3. Check logs for validation failures

## Security Logging

### Feature Description
Maintains detailed logs of security events for monitoring and analysis.

### Settings
- **Enable Security Logging**: Turns on/off security event logging.

### Logged Events
- Form submission attempts
- Blocked form submissions (rate limiting)
- Invalid nonce submissions
- Validation errors
- Failed CAPTCHA attempts
- Successful form submissions
- IP blocking events

### How It Works
1. Records security-relevant events with timestamps
2. Stores event details including IP addresses and user agents
3. Provides statistics dashboard in the admin interface
4. Automatically cleans up old logs to preserve performance

### Testing Security Logging
1. Perform various actions that trigger security events
2. Check the Security Logging tab to see recorded events
3. Review the Statistics dashboard for event analysis

## Server Hardening

### Feature Description
Provides a suite of one-click hardening toggles to secure WordPress at the server and application level.

### Modules
- **XML-RPC Protection**: Disables the XML-RPC API.
- **User Enumeration Protection**: Prevents scanning for valid usernames.
- **File Editor Disabled**: Disables the built-in theme/plugin editor.
- **Hide WP Version**: Removes version information from public source code.
- **Security Headers**: Adds HSTS, X-Frame-Options, and more.
- **REST API Restriction**: Limits REST access to authenticated users.

## Domain Control (Superadmin)

### Feature Description
A centralized management interface for the Superadmin to control plugin behavior across different client domains.

### Capabilities
- **Build Generator**: Create customized plugin ZIP files for clients.
- **White-labeling**: Specify custom Plugin Name and Author Name for generated builds.
- **Domain Locking**: Enforce plugin usage on a specific domain pattern.
- **License Management**: Track and manage client licenses and renewals.
- **OTP Verification**: Secure, passwordless access for the Superadmin.

## Testing the Features

### Test URLs and Methods

#### 1. WP-Cron Testing
- **URL**: `/wp-cron.php`
- **Method**: Access this URL repeatedly in a browser or with curl
- **Expected Result**: After reaching the limit, requests should be blocked

#### 2. Form Submission Testing
- **Method**: Create a test form that submits to the plugin's AJAX handler
- **AJAX Action**: `vapt_form_submit`
- **Nonce**: `wp_create_nonce( 'vapt_form_action' )`
- **Fields**: `name`, `email`, `message`, `captcha` (optional)

#### 3. Rate Limit Testing Script
You can create a simple test script to simulate multiple requests:

```php
// test-rate-limit.php
<?php
for ($i = 0; $i < 20; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://yoursite.com/wp-admin/admin-ajax.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'action' => 'vapt_form_submit',
        'nonce' => wp_create_nonce('vapt_form_action'),
        'name' => 'Test User ' . $i,
        'email' => 'test' . $i . '@example.com',
        'message' => 'Test message number ' . $i
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    echo "Request $i: " . $response . "\n";
    sleep(1); // Wait 1 second between requests
}
?>
```

## Configuration File

The plugin supports a configuration file to enable/disable features and define test URLs. A sample configuration file is included with the plugin.

### Configuration File Loading

The plugin automatically loads a configuration file named `vapt-config.php` if it exists in the WordPress root directory. This allows you to customize plugin behavior without modifying the core plugin files.

### Using the Configuration File

1. Copy `vapt-config-sample.php` to `vapt-config.php` in your WordPress root directory
2. Customize the settings as needed
3. The plugin will automatically detect and use these settings
4. Restart your web server if necessary

### Configuration Options

1. **Feature Control**: Enable/disable specific security features
2. **Custom URLs**: Define custom test URLs for your environment
3. **Whitelisting**: Protect trusted IPs from accidental blocking
4. **Custom Messages**: Personalize user-facing error messages
5. **Debugging**: Enable detailed logging for troubleshooting

### Sample Configuration File

```php
<?php
// Feature Enable/Disable Configuration
define('VAPT_FEATURE_WP_CRON_PROTECTION', true);
define('VAPT_FEATURE_RATE_LIMITING', true);
define('VAPT_FEATURE_INPUT_VALIDATION', true);
define('VAPT_FEATURE_SECURITY_LOGGING', true);

// Test URLs Configuration
define('VAPT_TEST_WP_CRON_URL', '/wp-cron.php');
define('VAPT_TEST_FORM_SUBMISSION_URL', '/wp-admin/admin-ajax.php');

// Whitelisted IPs (these IPs will never be blocked)
define('VAPT_WHITELISTED_IPS', [
    '127.0.0.1',
    '::1',
    // Add your trusted IPs here
]);

// Custom Messages
define('VAPT_RATE_LIMIT_MESSAGE', 'Too many requests. Please try again later.');
define('VAPT_INVALID_NONCE_MESSAGE', 'Invalid request. Please refresh the page and try again.');

// Debug Mode (only enable for testing)
define('VAPT_DEBUG_MODE', false);
?>
```

### Testing with Configuration File

With the configuration file in place, you can:

1. Temporarily disable features for testing
2. Modify rate limits for testing purposes
3. Add your development IP to the whitelist
4. Enable debug mode for detailed logging

Remember to restore production settings after testing!