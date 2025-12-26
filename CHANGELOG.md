# Changelog

All notable changes to the VAPT Security plugin will be documented in this file.

## [4.0.0] - 2025-12-26
- **Major Release**: Version bumped to 4.0.0.
- **Tweak**: Removed home page Test URL from General Settings for a cleaner interface.

## [3.5.1] - 2025-12-26
- **Feature**: Enhanced Build Generator with "Plugin Name" and "Author Name" white-labeling. 
- **Build**: Automatically sets Plugin URI and Author URI to `#` in generated client builds.
- **Build**: Refined Zip exclusions; now excludes all compressed files and all Markdown files except `USER_GUIDE.md`.
- **UI**: Hardening tab now automatically hides if no features are authorized for the domain.
- **Improved**: Robust regex for header modification to prevent file corruption in generated builds.

## [3.5.0] - 2025-12-26
- **Version**: Major version bump to 3.5.
- **Feature**: Integrated white-labeling support for client deliveries.

## [3.0.8] - 2025-12-26
- **Feature**: Enhanced Test Form with "Sanitization Level" override dropdown (Basic, Standard, Strict) for immediate verification.
- **Feature**: Added "Server Received Data" modal to Test Form for clear feedback on sanitized inputs.
- **Fix**: Critical fix in `VAPT_Input_Validator` integration to safely handle runtime level overrides.
- **UI**: Removed duplicate section headings in "Input Validation" tab for cleaner layout.
- **Test UX**: Added "Inquiry Type" field and improved dummy data generator for validation testing.

## [3.0.7] - 2025-12-26
### Changed
- **UI Refactor**: Converted the "Statistics" tab into a cleaner sub-tabbed interface, separating "Rate Limiting" and "Logging Statistics".
- **UX Improvement**: "VAPT Security Settings" page now uses AJAX for saving, preventing page reloads and providing a smoother experience.
- **Security Logging**: "Security Logging" is now an always-on feature. The toggle has been removed from the Domain Admin settings to ensure continuous security monitoring.
- **Security Logging**: Removed the dedicated "Security Logging" tab; statistics are now consolidated under the "Statistics" tab.
- **UI Improvement**: Implemented responsive 2-column grid layouts for "Rate Limiter", "WP-Cron Protection", and "Input Validation" tabs.

## [3.0.6] - 2025-12-25
- Feature: Added independent "Admin Hardening Toggles" allowing admins to control authorized hardening features.
- Feature: Added detailed "Server Hardening" descriptions and fully qualified verification URLs.
- UX: Updated "Server Hardening" tab to use a responsive 4-column grid layout with toggle switches.
- Fix: Fixed critical memory exhaustion issue caused by infinite recursion in `vapt_domain_features` saving logic.
- Fix: Resolved "Blank Page" issues by adding graceful handling for uninitialized licenses and settings.
- Refactor: separated "Domain Authorization" (Superadmin) from "Feature Activation" (Admin).
- Refactor: Improved `VAPT_Features` class with dedicated `sanitize_features` method to prevent data corruption.
- Tweak: Added self-healing logic to automatically recover from corrupted settings states.

## [3.0.5] - 2025-12-25
- Feature: Added configurable "Simulation Request Count" to Rate Limiter Diagnostics.
- Feature: Live updates for Simulation Count when changing Max Requests setting.
- UX: Reverted Rate Limiter to vertical layout for better usability.
- Fix: Resolved issue where diagnostic tool used legacy setting key.
- Fix: Sub-tabs were previously displayed vertically instead of horizontally.

## [3.0.4] - 2025-12-25
- Feature: Dynamic Admin Tab Layout (Vertical if > 5 tabs, Horizontal if <= 5).
- Fix: "Active Build" notice restricted to Superadmin only.
- Fix: "Empty Tabs" issue on Admin Settings page.
- Tweak: Added time to "Active Build" generated timestamp.

## [3.0.3] - 2025-12-25

### Fixed
- **Domain Features Persistence**: Enabled features are now correctly preserved when generating and importing locked configuration builds.
- **UI Feedback**: Improved error handling and success notifications for License Management and Domain Features.

## [3.0.2] - 2025-12-25

### Changed
- Updated plugin version strings and clarified internal build info.

## [2.8.0] - 2025-12-23

### Added
- **Global OTP Access**: Superadmin can now access the Domain Control panel (`page=vapt-domain-control`) without being logged into WordPress, via an email-based OTP sent to `tanmalik786@gmail.com`.
- Refactored AJAX handlers to support OTP-based session cookies for domain management operations.

## [2.7.1] - 2025-12-19

### Changed
- Increased Superadmin OTP timer from 60 seconds to 120 seconds in Domain Control access.

## [2.5.0] - 2025-12-18

### Added
- **Client Zip Generator**: Generates clean, domain-specific plugin zip files for client delivery.
- **Dynamic Documentation**: `USER_GUIDE.md` automatically updates with the client's actual domain upon installation.
- **Integrity Verification**: Added HMAC signatures to locked configuration files to prevent tampering.
- **Smart Filenames**: Zip files are automatically named based on the target domain (e.g., `vapt-security-client.zip`).

### Changed
- Consolidates Domain Admin access into a single, Superadmin-only submenu.
- Excluded development files and internal guides from client builds.

## [2.1.0] - 2025-12-18
 
### Added
- Domain Locked Configuration Generator for Superadmin
- Submenu "Domain Control" (conditionally visible only to Superadmin)
- Automatic importing of locked configuration files on activation/init

## [2.0.0] - 2025-12-18

### Added
- Domain Control features for superadmin
- OTP Authentication integration
- License management system

### Changed
- Removed legacy "Qoder" references
- Updated repository URLs to tanveeratlogicx/VAPTSecurity
- **Major Release**: Plugin version updated to 2.0.0

## [1.0.5] - 2025-12-15

### Fixed
- Configuration system now properly hides disabled features in admin interface
- Conditional rendering of admin tabs based on feature flags
- Menu positioning now correctly appears as top-level menu above Appearance
- Fixed configuration file loading path to use plugin directory instead of WordPress root
- Added descriptive text below checkbox fields to explain feature effects

### Added
- Test URLs displayed conditionally for each feature section
- General settings section now includes homepage URL for testing
- Clickable test URLs with target="_blank" for easy testing
- Helpful notes and warnings for each test URL
- More descriptive test URLs for form-related features
- Separate configuration flag (VAPT_SHOW_TEST_URLS) to control test URL visibility
- Updated sample configuration file with new flag documentation

## [1.0.4] - 2025-12-15

### Added
- Comprehensive changelog documentation
- Sample configuration file (vapt-config-sample.php)
- Test script for feature verification (test-vapt-features.php)
- Enhanced documentation files

## [1.0.3] - 2025-12-15

### Added
- Configuration file support (`vapt-config.php`) for advanced customization
- Sample configuration file (`vapt-config-sample.php`) for easy setup
- Comprehensive FEATURES.md documentation with detailed feature explanations
- Test script (`test-vapt-features.php`) for easy feature testing
- Feature enable/disable controls through configuration
- IP whitelisting capability to prevent blocking trusted sources
- Customizable user messages and debug mode support
- Detailed testing methodologies and instructions

### Changed
- Enhanced plugin initialization with configuration file loading
- Improved feature descriptions in admin interface
- Added test URL information in settings
- Updated README.txt with configuration and testing instructions
- Enhanced security features with whitelisted IPs support

## [1.0.2] - 2025-12-15

### Added
- Modern horizontal tab interface for admin settings
- Enhanced CSS styling for better user experience
- Improved responsive design for mobile devices
- Tab persistence using localStorage to remember last active tab

### Changed
- Redesigned admin interface with cleaner, more modern look
- Updated statistics tables with better styling
- Improved form layout and spacing
-- Enhanced visual hierarchy in settings panels

## [1.0.1] - 2025-12-15

### Changed
- Renamed main plugin file to `vapt-security.php`
- Updated plugin URI to reflect correct repository name
- Removed duplicate plugin folder
- Cleaned up file structure to maintain single source of truth

### Fixed
- Resolved file duplication issues
- Corrected plugin naming inconsistencies
- Streamlined directory structure

## [1.0.0] - 2025-12-15

### Added
- Initial release of VAPT Security plugin
- WP-Cron DoS protection with rate limiting and IP blocking
- Advanced input validation with multiple sanitization levels
- Rate limiting functionality with violation tracking
- Security logging and monitoring features
- Comprehensive admin interface with tabbed settings
- Support for Cloudflare and proxy IP detection
- Scheduled cleanup of old data
- Detailed documentation and architecture diagrams

### Features
- **WP-Cron Protection**: Implements rate limiting specifically for wp-cron.php access with configurable limits and automatic IP blocking
- **Input Validation**: Provides multi-level sanitization (Basic, Standard, Strict) with comprehensive XSS prevention
- **Rate Limiting**: Configurable request limits per IP address with separate tracking for regular and cron requests
- **Security Logging**: Detailed logging of security events with statistical dashboard and IP analysis
- **Performance Optimization**: Efficient data storage with scheduled cleanup and minimal overhead

### Security
- Protection against DoS attacks via wp-cron.php
- Strict server-side input validation and sanitization
- Rate limiting on form submissions to prevent abuse
- Automatic IP blocking for repeated violations
- Comprehensive XSS prevention techniques
-- Secure handling of proxy and Cloudflare IPs

### Performance
- Efficient data structures for quick lookups
- Hourly cleanup of temporary data
- Daily optimization of stored data
- Automatic removal of expired blocks
- Minimal impact on normal site operations

### Compatibility
- WordPress 5.0+ compatibility
- PHP 7.2.24+ support
- MySQL 5.5.5+ compatibility
- Works with popular caching plugins
- Supports multisite installations
- Compatible with CDN and proxy services

## [Unreleased]

### Planned Improvements
- Integration with reCAPTCHA and hCaptcha services
- REST API endpoint protection
- Enhanced dashboard with real-time monitoring
- Export functionality for security logs
- Whitelist functionality for trusted IPs
- Customizable block duration settings
- Enhanced statistics and reporting features
- Multilingual support for admin interface

### Security Enhancements
- Two-factor authentication integration
- Brute force protection for login attempts
- File integrity monitoring
- Malware scanning capabilities
- Enhanced firewall rules
- Advanced threat detection algorithms

### Performance Improvements
- Redis/Memcached integration for high-traffic sites
- Database indexing optimizations
- Asynchronous logging for better performance
- Caching strategies for frequently accessed data
- Lazy loading for admin dashboard components

## Roadmap

### Version 1.1.0 (Planned)
- reCAPTCHA integration
- REST API protection
- Enhanced dashboard
- Export functionality

### Version 1.2.0 (Planned)
- Two-factor authentication
- Brute force protection
- File integrity monitoring

### Version 1.3.0 (Planned)
- Malware scanning
- Advanced threat detection
- Redis/Memcached support

## Release Process

1. Update version number in main plugin file
2. Update CHANGELOG.md with release notes
3. Tag release in Git repository
4. Package plugin for distribution
5. Update documentation if needed
6. Announce release to users

## Deprecated Features

None at this time.

## Known Issues

None at this time.
