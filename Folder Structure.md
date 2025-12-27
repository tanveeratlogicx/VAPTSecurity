# VAPTSecurity Plugin - Directory Structure

This document outlines the file and folder structure of the VAPTSecurity plugin.

```
VAPTSecurity/
├── assets/                  # Static assets (CSS, JS)
│   ├── css/
│   │   └── vapt-security.css
│   ├── js/
│   │   └── vapt-security.js
│   └── admin.css            # Styles specific to the admin interface
├── bin/                     # Executable scripts for maintenance
│   ├── version-bump.bat     # Windows batch script for version bumping
│   └── version-bump.sh      # Shell script for version bumping
├── includes/                # Core plugin logic and classes
│   ├── captcha.php
│   ├── class-captcha-handler.php
│   ├── class-encryption.php
│   ├── class-features.php
│   ├── class-input-validator.php
│   ├── class-license.php
│   ├── class-otp.php
│   ├── class-rate-limiter.php
│   ├── class-security-logger.php
│   ├── cron.php
│   ├── input-validator.php
│   └── rate-limiter.php
├── templates/               # View files for admin pages and forms
│   ├── admin-domain-control.php
│   ├── admin-otp-settings.php
│   ├── admin-settings.php
│   └── form-template.php
├── tests/                   # Verification and test scripts
│   ├── test-plugin-structure.php
│   ├── test-rate-limiter.php
│   ├── verification_auth.php
│   ├── verification_phase2.php
│   └── verification_phase3.php
├── .gitignore
├── ARCHITECTURE.md          # Architectural overview of the plugin
├── CHANGELOG.md             # History of changes and updates
├── DOCUMENTATION.md         # General documentation
├── FEATURES.md              # Detailed list of plugin features
├── Folder Structure.md      # This file
├── LICENSE
├── README.md                # GitHub Readme
├── README.txt               # WordPress Plugin Repo Readme
├── SUPERADMIN_GUIDE.md      # Guide for Superadmin functions
├── USER_GUIDE.md            # Guide for end-users
├── VERSION_CONTROL.md       # Version control policies
├── composer.json            # Composer dependencies
├── index.php                # Silence is golden
├── package.json             # NPM dependencies
├── prompt.txt
├── test-config.php
├── test-integrations-mock.php # Mock tests for form integrations
├── test-vapt-features.php
├── uninstall.php            # Uninstallation logic
├── vapt-config-sample.php   # Sample configuration file
├── vapt-config.php          # Active configuration file
├── vapt-locked-config.php   # Locked domain configuration
└── vapt-security.php        # Main plugin file (v4.1.1)
```
