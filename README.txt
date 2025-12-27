=== VAPT Security ===
Contributors: tanveeratlogicx
Tags: security, vapt, dos protection, input validation, rate limiting
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 4.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress security plugin that protects against common VAPT issues including DoS attacks via wp-cron, lack of input validation, and rate limiting on form submissions.

== Description ==

VAPT Security is a robust security plugin designed to address critical VAPT (Vulnerability Assessment and Penetration Testing) issues in WordPress sites. The plugin provides multi-layered protection against common security threats:

### Key Features:

1. **WP-Cron DoS Protection**
   - Rate limiting specifically for wp-cron.php access
   - Automatic IP blocking for abusive requests
   - Configurable limits to prevent server overload

2. **Advanced Input Validation**
   - Multi-level sanitization (Basic, Standard, Strict)
   - Comprehensive XSS prevention
   - Email, URL, and custom field validation
   - Regex pattern matching support

3. **Rate Limiting & Throttling**
   - Configurable request limits per IP address
   - Separate limits for regular requests and cron access
   - Automatic IP blocking for abuse patterns
   - Violation tracking and monitoring

4. **Security Logging & Monitoring**
   - Detailed logging of security events
   - Statistics dashboard for monitoring
   - Top IP addresses tracking
   - Event type categorization

5. **Performance Optimized**
   - Efficient data storage and cleanup
   - Minimal performance impact on normal operations
   - Scheduled maintenance tasks

6. **Domain Control Features**
   - Hidden Domain Control Page for Superadmin
   - OTP Authentication integration
   - License management

### Configuration File Support

The plugin supports a configuration file (`vapt-config.php`) that allows you to:
- Enable/disable specific features
- Define custom test URLs
- Whitelist trusted IP addresses
- Customize user-facing messages
- Enable debug mode for troubleshooting

### Testing Tools

The plugin includes comprehensive testing tools:
- Detailed FEATURES.md documentation with testing instructions
- Sample test script (`test-vapt-features.php`) for easy feature testing
- Configuration file samples for different environments

### Why Choose VAPT Security?

- **Comprehensive Protection**: Addresses multiple VAPT issues in one plugin
- **Easy Configuration**: Intuitive settings panel with sensible defaults
- **Performance Focused**: Designed to minimize impact on site performance
- **Fully Documented**: Extensive documentation and code comments
- **Regular Updates**: Active development and security updates

== Installation ==

1. Upload the `vapt-security` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Settings → VAPT Security to configure options
4. (Optional) Create a `vapt-config.php` file in your WordPress root directory for advanced configuration
5. (Development) Use `test-vapt-features.php` to test plugin functionality

== Frequently Asked Questions ==

= How does this protect against wp-cron DoS attacks? =

The plugin intercepts requests to wp-cron.php and applies rate limiting. IPs that exceed the configured threshold are temporarily blocked.

= What level of input validation should I use? =

- **Basic**: Minimal sanitization, allows most characters
- **Standard**: Balanced approach, removes most harmful content
- **Strict**: Maximum security, removes all but essential characters

= How often is log data cleaned up? =

Security logs and rate limiting data are automatically cleaned up:
- Hourly cleanup of temporary data
- Daily cleanup of older records
- Logs older than 30 days are automatically removed

= How do I test the security features? =

Detailed testing instructions are available in the FEATURES.md file included with the plugin. You can also use the test-vapt-features.php script for easy testing, or refer to the Test URL information shown in the plugin settings.

= Can I customize the plugin behavior? =

Yes, you can create a `vapt-config.php` file in your WordPress root directory to customize feature behavior, test URLs, whitelisted IPs, and more.

== Screenshots ==

1. Main settings panel with tabbed interface
2. Rate limiting configuration options
3. Input validation settings
4. WP-Cron protection controls
5. Security logging and statistics dashboard

== Changelog ==
 
= 4.1.1 =
* Feature: Added "Cron Rate Limit Test" diagnostic tool to demonstrate throttling.
* Improvement: Redesigned WP-Cron Protection tab layout for better ergonomics.
* Improvement: Enhanced H3 section headers with distinct backgrounds and icons across all tabs.
* Improvement: Reordered settings tabs (Input Validation now precedes Rate Limiter).
* Improvement: Enhanced Server IP detection to favor IPv4.

= 4.0.6 =
* Fix: Adopted ini_set approach for sendmail_from to resolve delivery failures in LocalWP.
* Fix: Matched working manual test signature (3-argument mail() call).

= 4.0.5 =
* Fix: Added mandatory From header to direct mail() calls to satisfy PHP requirements.
* Version: Reliability update for local environments.

= 4.0.4 =
* Fix: Switched to naked mail() calls for OTP to match successful manual tests.
* Enhancement: Bypassed wp_mail filtering to ensure delivery in LocalWP environments.

= 4.0.3 =
* Fix: Simplified email headers to improve OTP delivery in local environments.
* Version: Periodic version bump.

= 4.0.2 =
* Fix: Resolved undefined variable warning in Domain Control access interception.

= 4.0.1 =
* Security: Implemented obfuscation for Superadmin identifiers and management URLs.
* Security: Consolidated identity verification logic into a secure reconstruction helper.
* Docs: Synchronized all project guides and architectural documentation.

= 4.0.0 =
* Major Release: Version bumped to 4.0.0.
* Tweak: Removed home page Test URL from General Settings for a cleaner interface.

= 3.5.1 =
* Feature: Enhanced Build Generator with "Plugin Name" and "Author Name" white-labeling. 
* Build: Automatically sets Plugin URI and Author URI to # in generated client builds.
* Build: Refined Zip exclusions; now excludes all compressed files and all Markdown files except USER_GUIDE.md.
* UI: Hardening tab now automatically hides if no features are authorized for the domain.
* Improved: Robust regex for header modification to prevent file corruption in generated builds.

= 3.5.0 =
* Version: Major version bump to 3.5.
* Feature: Integrated white-labeling support for client deliveries.

= 3.0.8 =
* Feature: Enhanced Test Form with Sanitization Level override dropdown.
* Feature: Added "Server Received Data" modal to Test Form.
* Fix: Critical fix in VAPT_Input_Validator integration.
* UI: Removed duplicate section headings in Input Validation tab.

= 2.1.0 =
* Added Domain Locked Configuration Generator
* Exposed Domain Control page as conditional submenu for Superadmin
* Enhanced configuration portability

= 2.0.0 =
* Major release with Domain Control features
* Added OTP authentication for superadmin
* Removed legacy "Qoder" references
* UI enhancements and bug fixes
* Updated repository URLs

= 1.0.3 =
* Added configuration file support with vapt-config-sample.php
* Created comprehensive FEATURES.md documentation
* Added test-vapt-features.php testing script
* Enhanced plugin with feature enable/disable controls
* Added IP whitelisting and customizable messages
* Improved admin interface with feature descriptions

= 1.0.2 =
* Improved admin interface with modern horizontal tabs
* Enhanced CSS styling and responsive design
* Added tab persistence using localStorage
* Added configuration file support
* Improved feature documentation

= 1.0.1 =
* Renamed main plugin file to vapt-security.php
* Updated plugin URI and naming consistency
* Cleaned up file structure and removed duplicates

= 1.0.0 =
* Initial release of VAPT Security plugin
* WP-Cron DoS protection implementation
* Advanced input validation with multiple sanitization levels
* Rate limiting with IP blocking capabilities
* Security logging and monitoring features
* Comprehensive admin interface

== Upgrade Notice ==

= 1.0.3 =
Added comprehensive configuration file support, documentation, and testing tools

= 1.0.2 =
Added configuration file support and improved admin interface

= 1.0.1 =
Fixed naming inconsistencies and file structure issues

= 1.0.0 =
Initial release of VAPT Security plugin