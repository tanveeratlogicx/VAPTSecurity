# Licensing Implementation Guide

This document outlines how to implement a licensing/authorization system for the VAPT Security plugin using remote configuration files.

## Overview

The licensing system works by:
1. Checking for a valid license key
2. Fetching configuration files from a remote server/Gist
3. Applying authorized features based on the license tier
4. Validating the license periodically

## Implementation Steps

### 1. License Key Management

Create a license key system in the plugin:

```php
// Add to main plugin file
function vapt_validate_license($license_key) {
    $api_url = 'https://your-license-server.com/validate';
    $response = wp_remote_post($api_url, array(
        'body' => array(
            'license_key' => $license_key,
            'site_url' => home_url()
        )
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $result = json_decode(wp_remote_retrieve_body($response), true);
    return isset($result['valid']) && $result['valid'];
}
```

### 2. Remote Configuration Fetching

Modify the configuration loading to fetch from remote source:

```php
// Updated configuration loading
function vapt_load_remote_config() {
    $license_key = get_option('vapt_license_key');
    
    // Only fetch if license is valid
    if (vapt_validate_license($license_key)) {
        $remote_config_url = 'https://gist.githubusercontent.com/your-gist-url/vapt-config.php';
        $response = wp_remote_get($remote_config_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $config_content = wp_remote_retrieve_body($response);
            // Validate and apply configuration
            return eval('?>' . $config_content);
        }
    }
    
    // Fall back to local config
    $local_config = plugin_dir_path(__FILE__) . 'vapt-config.php';
    if (file_exists($local_config)) {
        require_once $local_config;
    }
}
```

### 3. Feature Authorization

Implement feature authorization based on license tier:

```php
// Add license tier checking
function vapt_is_feature_authorized($feature) {
    $license_data = get_option('vapt_license_data');
    
    if (!$license_data) {
        return false;
    }
    
    $tier = $license_data['tier'];
    $authorized_features = array(
        'basic' => array('VAPT_FEATURE_INPUT_VALIDATION'),
        'premium' => array('VAPT_FEATURE_INPUT_VALIDATION', 'VAPT_FEATURE_RATE_LIMITING'),
        'enterprise' => array(
            'VAPT_FEATURE_INPUT_VALIDATION', 
            'VAPT_FEATURE_RATE_LIMITING',
            'VAPT_FEATURE_WP_CRON_PROTECTION',
            'VAPT_FEATURE_SECURITY_LOGGING'
        )
    );
    
    return in_array($feature, $authorized_features[$tier]);
}
```

### 4. Periodic License Validation

Schedule periodic license validation:

```php
// Add to plugin initialization
add_action('init', 'vapt_schedule_license_check');

function vapt_schedule_license_check() {
    if (!wp_next_scheduled('vapt_validate_license_event')) {
        wp_schedule_event(time(), 'daily', 'vapt_validate_license_event');
    }
}

add_action('vapt_validate_license_event', 'vapt_perform_license_check');

function vapt_perform_license_check() {
    $license_key = get_option('vapt_license_key');
    if (!vapt_validate_license($license_key)) {
        // Deactivate premium features
        update_option('vapt_license_valid', false);
    } else {
        update_option('vapt_license_valid', true);
    }
}
```

## Security Considerations

1. **HTTPS Only**: Always use HTTPS for communication with license servers
2. **Obfuscation**: Obfuscate license validation code to prevent tampering
3. **Server-Side Validation**: Perform critical validation on your servers, not client-side
4. **Rate Limiting**: Implement rate limiting on license validation requests
5. **Checksums**: Use checksums to verify configuration file integrity

## Fallback Mechanism

Always implement a fallback mechanism:

```php
// Fallback to basic features if license validation fails
if (!get_option('vapt_license_valid')) {
    // Disable premium features
    define('VAPT_FEATURE_RATE_LIMITING', false);
    define('VAPT_FEATURE_WP_CRON_PROTECTION', false);
    define('VAPT_FEATURE_SECURITY_LOGGING', false);
}
```

## Gist Integration

To integrate with Gist:

1. Create a secret Gist for premium configurations
2. Use GitHub API tokens for access control
3. Implement token rotation for security
4. Monitor Gist access logs for suspicious activity

Example Gist URL structure:
```
https://gist.githubusercontent.com/{username}/{gist_id}/raw/{commit_hash}/{filename}
```

## License Management Dashboard

Consider creating a dashboard for license management:

1. License key generation
2. Usage statistics
3. Feature activation/deactivation
4. Customer support integration