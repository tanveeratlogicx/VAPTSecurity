# VAPT Security Plugin Documentation

## Overview

VAPT Security is a comprehensive WordPress security plugin designed to address critical VAPT (Vulnerability Assessment and Penetration Testing) issues. The plugin provides multi-layered protection against common security threats including DoS attacks via wp-cron, lack of input validation, and inadequate rate limiting on form submissions.

## Features

### 1. WP-Cron DoS Protection

The plugin implements robust protection against Denial of Service attacks targeting WordPress's wp-cron.php endpoint:

- **Rate Limiting**: Configurable limits on wp-cron requests per IP address
- **IP Blocking**: Automatic blocking of IPs that exceed request thresholds
- **Proxy Support**: Proper handling of Cloudflare and other proxy services
- **Monitoring**: Detailed logging of cron access attempts

### 2. Advanced Input Validation

Comprehensive input sanitization and validation to prevent injection attacks:

- **Multi-level Sanitization**: Three levels of input cleaning (Basic, Standard, Strict)
- **XSS Prevention**: Advanced cross-site scripting attack prevention
- **Data Type Handling**: Specialized validation for emails, URLs, integers, and strings
- **Custom Patterns**: Support for regex-based validation rules

### 3. Rate Limiting & Throttling

Server-side request limiting to prevent abuse:

- **Configurable Limits**: Adjustable request thresholds and time windows
- **Separate Tracking**: Different limits for regular vs. cron requests
- **Violation Monitoring**: Track abusive IPs and block repeat offenders
- **Automatic Cleanup**: Regular removal of old tracking data

### 4. Security Logging & Monitoring

Comprehensive event tracking and analysis:

- **Event Categorization**: Classification of security events by type
- **Statistical Dashboard**: Visual representation of security metrics
- **IP Analysis**: Identification of problematic IP addresses
- **Retention Policy**: Automatic cleanup of old log data

### 5. Domain Control (Superadmin)

Foundational controls for management and distribution:

- **Build Generator**: Securely package client versions of the plugin.
- **White-labeling**: Dynamic rebranding of plugin and author names.
- **Domain Locking**: Enforce plugin authorization via signed config files.
- **OTP Access**: Secure, passwordless Superadmin login system.

## Installation

### Prerequisites

- WordPress 6.0 or higher
- PHP 8.0 or higher
- MySQL 5.5.5 or higher

### Installation Steps

1. Download the plugin files
2. Upload the `vapt-security` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin panel
4. Configure settings via Settings → VAPT Security

## Configuration

### General Settings

- **Disable WP-Cron**: Recommended for production sites to prevent abuse
- **Sanitization Level**: Choose between Basic, Standard, or Strict input cleaning

### Rate Limiter Settings

- **Max Requests per Minute**: Number of requests allowed per IP per minute
- **Rate Limit Window**: Time window for rate limiting calculations

### Input Validation Settings

- **Require Valid Email**: Enforce email validation on all forms
- **Sanitization Level**: Control how strictly input is sanitized

### WP-Cron Protection Settings

- **Enable Cron Rate Limiting**: Toggle protection for wp-cron.php
- **Max Cron Requests per Hour**: Maximum cron requests allowed per IP per hour

### Security Logging Settings

- **Enable Security Logging**: Turn on detailed event logging
- **Log Retention**: Automatic cleanup of logs older than 30 days

## Technical Implementation

### Architecture

The plugin follows a modular architecture with the following components:

1. **Main Plugin Class**: Coordinates all security features
2. **Rate Limiter**: Implements request throttling
3. **Input Validator**: Provides data sanitization
4. **Security Logger**: Handles event tracking
5. **CAPTCHA Handler**: Manages challenge-response verification

### Data Storage

All plugin data is stored efficiently in the WordPress options table:

- `vapt_rate_limit`: Tracks regular request patterns
- `vapt_cron_rate_limit`: Tracks cron request patterns
- `vapt_blocked_ips`: List of currently blocked IPs
- `vapt_security_logs`: Security event logs
- `vapt_ip_violations`: IP violation counters

### Performance Considerations

- **Efficient Algorithms**: Optimized data structures for quick lookups
- **Scheduled Cleanup**: Regular maintenance to prevent data bloat
- **Minimal Overhead**: Lightweight processing for normal requests
- **Caching**: Internal caching to reduce database queries

## Best Practices

### WP-Cron Security

1. Disable default WP-Cron for production sites
2. Implement strict rate limiting for direct cron access
3. Monitor cron access patterns for anomalies
4. Use system-level cron jobs for better reliability

### Input Validation

1. Always validate on the server side
2. Use appropriate sanitization levels for your use case
3. Implement allow-lists for known good values
4. Regularly review and update validation rules

### Rate Limiting

1. Set appropriate limits based on normal usage patterns
2. Monitor violation reports to identify potential attacks
3. Regularly review blocked IP addresses
4. Adjust limits during high-traffic periods

## Troubleshooting

### Common Issues

1. **Forms not submitting**: Check rate limiting settings
2. **Legitimate requests blocked**: Review IP blocking list
3. **Performance impact**: Lower sanitization level or adjust limits
4. **Logging not working**: Verify logging is enabled in settings

### Debugging

1. Check WordPress debug log for errors
2. Review security logs for blocked requests
3. Verify plugin settings are correctly configured
4. Test with default settings to isolate issues

## API Reference

### Main Plugin Class

```php
VAPT_Security::instance()
```

Returns the singleton instance of the main plugin class.

### Rate Limiter

```php
$rate_limiter = new VAPT_Rate_Limiter();
$rate_limiter->allow_request(); // Check if request is allowed
$rate_limiter->allow_cron_request(); // Check if cron request is allowed
```

### Input Validator

```php
$validator = new VAPT_Input_Validator();
$schema = [
    'field_name' => ['required' => true, 'type' => 'string', 'max' => 50]
];
$data = $validator->validate($_POST, $schema);
```

### Security Logger

```php
$logger = new VAPT_Security_Logger();
$logger->log_event('event_type', ['additional' => 'data']);
```

## Contributing

### Development Guidelines

1. Follow WordPress coding standards
2. Use proper documentation for all functions
3. Test thoroughly before submitting changes
4. Maintain backward compatibility

### Reporting Issues

1. Check existing issues before creating new ones
2. Provide detailed reproduction steps
3. Include WordPress and plugin version information
4. Attach relevant error logs if available

## License

This plugin is licensed under the GPLv2 or later license, the same as WordPress itself.

## Support

For support, please open an issue on the GitHub repository or contact the plugin author.