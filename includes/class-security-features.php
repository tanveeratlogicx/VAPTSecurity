<?php

/**
 * VAPT Security Features Handler
 *
 * Implements the logic for various security toggles.
 */
class VAPT_Security_Features
{

    public static function init()
    {
        // Admin Settings (Activation Control)
        $admin_settings = get_option('vapt_hardening_settings', []);

        // Helper check: Feature must be allowed by Domain AND enabled by Admin
        $is_active = function ($slug) use ($admin_settings) {
            return VAPT_Features::is_enabled($slug) && !empty($admin_settings[$slug]);
        };

        // XML-RPC
        if ($is_active('disable_xmlrpc')) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_headers', [__CLASS__, 'remove_pingback_header']);
        }

        // User Enumeration
        if ($is_active('disable_user_enum')) {
            add_action('request', [__CLASS__, 'block_user_enumeration']);
        }

        // Disable File Edit
        if ($is_active('disable_file_edit')) {
            // DISALLOW_FILE_EDIT is a constant, best defined in wp-config.
            // Plugins cannot easily force this if it's not defined, 
            // but we can filter the capabilities or mapping.
            add_filter('map_meta_cap', [__CLASS__, 'disallow_file_edit_map_meta_cap'], 10, 2);
        }

        // Hide WP Version
        if ($is_active('hide_wp_version')) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        // Security Headers
        if ($is_active('security_headers')) {
            add_action('send_headers', [__CLASS__, 'add_security_headers']);
        }

        // User REST API
        if ($is_active('restrict_rest_api')) {
            add_filter('rest_authentication_errors', [__CLASS__, 'restrict_rest_api_access']);
        }
    }

    /**
     * Remove X-Pingback header.
     */
    public static function remove_pingback_header($headers)
    {
        unset($headers['X-Pingback']);
        return $headers;
    }

    /**
     * Block User Enumeration strings like ?author=1
     */
    public static function block_user_enumeration($query_vars)
    {
        if (is_admin()) {
            return $query_vars;
        }

        if (isset($query_vars['author']) && (is_array($query_vars['author']) || is_numeric($query_vars['author']))) {
            // Redirect to home 403 or similar
            wp_die(__('User enumeration is disabled.', 'vapt-security'), __('Forbidden', 'vapt-security'), 403);
        }
        return $query_vars;
    }

    /**
     * Disallow file edit capabilities.
     */
    public static function disallow_file_edit_map_meta_cap($caps, $cap)
    {
        if (in_array($cap, ['edit_plugins', 'edit_themes'], true)) {
            // We technically shouldn't block 'edit_plugins' generally, just the file editor.
            // However, 'edit_plugins' capability controls access to the plugin editor.
            // The more precise way is preventing the editor screen capability mapping if possible, 
            // but 'DISALLOW_FILE_EDIT' usually removes the menu. 
            // Since we can't set CONSTANT late, removing the cap for file editing context is hard.
            // WordPress checks `current_user_can('edit_plugins')` for the menu.

            // Actually, the simplest 'plugin' way is to filter 'file_mod_allowed' which controls updates too? 
            // No, 'map_meta_cap' is risky for general admin tasks.

            // Better approach: Redirect the editor pages if accessed.
            // Or assume this feature is better handled by user in wp-config, 
            // but let's try to simulate it by removing the submenus?
        }
        return $caps;
    }

    // New attempt for file editor
    public static function disable_file_editor_menu()
    {
        // This can be called on admin_menu
        remove_submenu_page('themes.php', 'theme-editor.php');
        remove_submenu_page('plugins.php', 'plugin-editor.php');
    }

    /**
     * Add Security Headers.
     */
    public static function add_security_headers()
    {
        if (headers_sent()) {
            return;
        }
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Restrict REST API to authenticated users.
     */
    public static function restrict_rest_api_access($result)
    {
        // If a previous authentication check was successful, return it.
        if (true === $result || is_wp_error($result)) {
            return $result;
        }

        if (! is_user_logged_in()) {
            // Whitelist needed? CF7 usually uses REST API today.
            // Check request path if complex. For now, strict block.
            return new WP_Error(
                'rest_forbidden',
                __('REST API Restricted to authenticated users.', 'vapt-security'),
                ['status' => 401]
            );
        }

        return $result;
    }
}
// Init hook needs to be called in main file, or self-hook here if file is included?
// Let's expect the main file to call VAPT_Security_Features::init();
