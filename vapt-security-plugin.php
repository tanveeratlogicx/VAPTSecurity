<?php
/**
 * Plugin Name: VAPT Security Plugin
 * Plugin URI: https://github.com/tanveeratlogicx/VAPTSecurity
 * Description: Comprehensive WordPress security plugin addressing VAPT issues: DOS Attack prevention via wp-cron.php, strict input validation, and rate limiting on form submissions.
 * Version: 1.0.0
 * Author: Tanveer at LogicX
 * Author URI: https://github.com/tanveeratlogicx
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: vapt-security
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * 
 * @package VAPT_Security
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VAPT_SECURITY_VERSION', '1.0.0');
define('VAPT_SECURITY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VAPT_SECURITY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VAPT_SECURITY_PLUGIN_FILE', __FILE__);

/**
 * Main VAPT Security Plugin Class
 * 
 * Implements comprehensive security measures:
 * 1. WP-Cron DOS protection with rate limiting
 * 2. Input validation and sanitization framework
 * 3. Form submission rate limiting with CAPTCHA support
 */
class VAPT_Security_Plugin {
    
    /**
     * Singleton instance
     * @var VAPT_Security_Plugin
     */
    private static $instance = null;
    
    /**
     * Rate limit storage
     * @var array
     */
    private $rate_limits = array();
    
    /**
     * Get singleton instance
     * 
     * @return VAPT_Security_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize plugin
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation and deactivation
        register_activation_hook(VAPT_SECURITY_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(VAPT_SECURITY_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Admin menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // WP-Cron DOS Protection
        add_action('init', array($this, 'protect_wp_cron'), 1);
        
        // Form submission rate limiting
        add_action('init', array($this, 'init_rate_limiting'));
        
        // Input validation hooks
        add_filter('pre_comment_content', array($this, 'validate_comment_input'), 10, 1);
        add_filter('pre_user_login', array($this, 'validate_user_login'), 10, 1);
        add_filter('pre_user_email', array($this, 'validate_email_input'), 10, 1);
        
        // Additional security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once VAPT_SECURITY_PLUGIN_DIR . 'includes/class-cron-protection.php';
        require_once VAPT_SECURITY_PLUGIN_DIR . 'includes/class-input-validator.php';
        require_once VAPT_SECURITY_PLUGIN_DIR . 'includes/class-rate-limiter.php';
        require_once VAPT_SECURITY_PLUGIN_DIR . 'includes/class-security-logger.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables for logging
        $this->create_security_tables();
        
        // Set default options
        $defaults = array(
            'vapt_cron_rate_limit' => 60,
            'vapt_cron_max_requests' => 10,
            'vapt_disable_wp_cron' => false,
            'vapt_form_rate_limit' => 300,
            'vapt_form_max_requests' => 5,
            'vapt_enable_captcha' => false,
            'vapt_captcha_site_key' => '',
            'vapt_captcha_secret_key' => '',
            'vapt_input_validation_level' => 'strict',
            'vapt_block_suspicious_ips' => true,
            'vapt_log_security_events' => true,
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vapt_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vapt_%'");
        
        flush_rewrite_rules();
    }
    
    /**
     * Create security logging tables
     */
    private function create_security_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vapt_security_log';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_address varchar(45) NOT NULL,
            event_type varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            details text,
            user_agent text,
            request_uri text,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address),
            KEY event_type (event_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('VAPT Security', 'vapt-security'),
            __('VAPT Security', 'vapt-security'),
            'manage_options',
            'vapt-security',
            array($this, 'render_admin_page'),
            'dashicons-shield',
            100
        );
        
        add_submenu_page(
            'vapt-security',
            __('Security Logs', 'vapt-security'),
            __('Security Logs', 'vapt-security'),
            'manage_options',
            'vapt-security-logs',
            array($this, 'render_logs_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // WP-Cron Protection Settings
        register_setting('vapt_security_cron', 'vapt_cron_rate_limit', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 60
        ));
        
        register_setting('vapt_security_cron', 'vapt_cron_max_requests', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ));
        
        register_setting('vapt_security_cron', 'vapt_disable_wp_cron', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        // Form Rate Limiting Settings
        register_setting('vapt_security_forms', 'vapt_form_rate_limit', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 300
        ));
        
        register_setting('vapt_security_forms', 'vapt_form_max_requests', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 5
        ));
        
        register_setting('vapt_security_forms', 'vapt_enable_captcha', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        register_setting('vapt_security_forms', 'vapt_captcha_site_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('vapt_security_forms', 'vapt_captcha_secret_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        // Input Validation Settings
        register_setting('vapt_security_validation', 'vapt_input_validation_level', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'strict'
        ));
        
        // General Security Settings
        register_setting('vapt_security_general', 'vapt_block_suspicious_ips', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        register_setting('vapt_security_general', 'vapt_log_security_events', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
    }
    
    /**
     * WP-Cron DOS Protection
     * Implements rate limiting to prevent DOS attacks via wp-cron.php
     */
    public function protect_wp_cron() {
        // Only protect wp-cron.php requests
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }
        
        $cron_protection = new VAPT_Cron_Protection();
        $cron_protection->enforce_rate_limit();
    }
    
    /**
     * Initialize rate limiting for forms
     */
    public function init_rate_limiting() {
        $rate_limiter = new VAPT_Rate_Limiter();
        
        // Hook into comment submission
        add_action('pre_comment_on_post', array($rate_limiter, 'check_comment_rate_limit'));
        
        // Hook into login attempts
        add_filter('authenticate', array($rate_limiter, 'check_login_rate_limit'), 30, 3);
        
        // Hook into registration
        add_filter('registration_errors', array($rate_limiter, 'check_registration_rate_limit'), 10, 3);
        
        // Hook into contact forms (popular plugins)
        add_action('wpcf7_before_send_mail', array($rate_limiter, 'check_cf7_rate_limit'));
        add_filter('gform_validation', array($rate_limiter, 'check_gravity_forms_rate_limit'));
    }
    
    /**
     * Validate comment input
     * 
     * @param string $content Comment content
     * @return string Sanitized content
     */
    public function validate_comment_input($content) {
        $validator = new VAPT_Input_Validator();
        return $validator->validate_comment($content);
    }
    
    /**
     * Validate user login input
     * 
     * @param string $login User login
     * @return string Sanitized login
     */
    public function validate_user_login($login) {
        $validator = new VAPT_Input_Validator();
        return $validator->validate_username($login);
    }
    
    /**
     * Validate email input
     * 
     * @param string $email Email address
     * @return string Validated email
     */
    public function validate_email_input($email) {
        $validator = new VAPT_Input_Validator();
        return $validator->validate_email($email);
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (!is_admin()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vapt-security') === false) {
            return;
        }
        
        wp_enqueue_style(
            'vapt-security-admin',
            VAPT_SECURITY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            VAPT_SECURITY_VERSION
        );
        
        wp_enqueue_script(
            'vapt-security-admin',
            VAPT_SECURITY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            VAPT_SECURITY_VERSION,
            true
        );
    }
    
    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        require_once VAPT_SECURITY_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    /**
     * Render security logs page
     */
    public function render_logs_page() {
        require_once VAPT_SECURITY_PLUGIN_DIR . 'admin/views/logs-page.php';
    }
}

// Initialize the plugin
function vapt_security_init() {
    return VAPT_Security_Plugin::get_instance();
}

// Start the plugin
vapt_security_init();
