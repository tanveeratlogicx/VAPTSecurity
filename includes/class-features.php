<?php

/**
 * VAPT Feature Manager
 *
 * Manages domain-level features enabled/disabled by the Superadmin.
 */
class VAPT_Features
{

    const OPTION_NAME = 'vapt_domain_features';

    // Defined features and their defaults
    private static $defined_features = [
        // Core Modules (Tabs) - Enabled by default
        'rate_limiting'    => true,
        'input_validation' => true,
        'cron_protection'  => true,
        // 'security_logging' => true, // Removed: Always enabled

        // Hardening Toggles - Disabled by default (Must be authorized by Superadmin)
        'disable_xmlrpc'   => false,
        'disable_user_enum' => false,
        'disable_file_edit' => false,
        'hide_wp_version'  => false,
        'security_headers' => false,
        'restrict_rest_api' => false,
    ];

    /**
     * Get all defined features.
     *
     * @return array
     */
    public static function get_defined_features()
    {
        return self::$defined_features;
    }

    /**
     * Get active features for the domain.
     *
     * @return array Map of feature_slug => bool
     */
    public static function get_active_features()
    {
        $stored = get_option(self::OPTION_NAME);
        if (! is_array($stored)) {
            // If not set yet, return defaults
            return self::$defined_features;
        }
        // Merge with defaults to ensure all keys exist
        return array_merge(self::$defined_features, $stored);
    }

    /**
     * Check if a specific feature is enabled.
     *
     * @param string $slug Feature slug.
     * @return bool
     */
    public static function is_enabled($slug)
    {
        if ($slug === 'security_logging') {
            return true;
        }
        $active = self::get_active_features();
        return ! empty($active[$slug]);
    }

    /**
     * Sanitize features array.
     *
     * @param array $features Raw input.
     * @return array Sanitized input.
     */
    public static function sanitize_features($features)
    {
        $valid = [];
        foreach (self::$defined_features as $slug => $default) {
            if (isset($features[$slug])) {
                $valid[$slug] = (bool) $features[$slug];
            } else {
                $valid[$slug] = false;
            }
        }
        return $valid;
    }

    /**
     * Update active features.
     *
     * @param array $features Map of slug => bool.
     * @return bool
     */
    public static function update_features($features)
    {
        $valid = self::sanitize_features($features);
        return update_option(self::OPTION_NAME, $valid);
    }
}
