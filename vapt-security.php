<?php
/**
 * Plugin Name: VAPT Security
 * Plugin URI:  https://github.com/your‑username/vapt-security
 * Description: A lightweight plugin that mitigates DoS via wp‑cron, enforces strict input validation,
 *              and implements rate limiting on form submissions. Compatible with WP 6.5+.
 * Version:     1.1.0
 * Author:      Tanveer Atlogicx
 * Author URI:  https://github.com/tanveeratlogicx
 * License:     GPL‑2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VAPT_Security
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Main Plugin Class
 */
final class VAPT_Security {

    /**
     * Singleton instance
     *
     * @var VAPT_Security
     */
    private static $instance;

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Initialize the plugin
     */
    private function __construct() {
        // Load dependencies
        $this->includes();

        // Disable WP‑Cron
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedule' ] );
        add_action( 'init', [ $this, 'disable_wp_cron' ] );

        // Register custom cron job
        add_action( 'vapt_security_daily', [ $this, 'run_daily_task' ] );

        // Attach rate limiter to form submissions
        add_action( 'wp_ajax_nopriv_vapt_form_submit', [ $this, 'handle_form_submission' ] );
        add_action( 'wp_ajax_vapt_form_submit', [ $this, 'handle_form_submission' ] );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Returns the singleton instance
     *
     * @return VAPT_Security
     */
    public static function instance(): VAPT_Security {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/cron.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/rate-limiter.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/input-validator.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/captcha.php';
    }

    /**
     * Disable the default WP‑Cron by adding a query‑string filter and removing the hook.
     */
    public function disable_wp_cron() {
        if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
            return; // Already disabled via wp‑config.php
        }
        // Force disable via filter
        add_filter( 'pre_option_cron', '__return_false' );
    }

    /**
     * Add a custom cron schedule (daily) and register the event
     */
    public function add_cron_schedule( array $schedules ) {
        if ( ! wp_next_scheduled( 'vapt_security_daily' ) ) {
            wp_schedule_event( time(), 'daily', 'vapt_security_daily' );
        }
        return $schedules;
    }

    /**
     * The task that runs daily.
     */
    public function run_daily_task() {
        // Example: clean up stale rate‑limit entries
        VAPT_Rate_Limiter::instance()->clean_old_entries();
    }

    /**
     * Handle form submissions via AJAX
     */
    public function handle_form_submission() {
        // 1. Rate limit
        $rl = VAPT_Rate_Limiter::instance();
        if ( ! $rl->allow_request() ) {
            wp_send_json_error( [ 'message' => __( 'Too many requests. Please try again later.', 'vapt-security' ) ], 429 );
        }

        // 2. Verify nonce
        if ( ! isset( $_POST['vapt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['vapt_nonce'] ), 'vapt_form_action' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'vapt-security' ) ], 400 );
        }

        // 3. Input validation
        $validator = new VAPT_Input_Validator();
        $fields    = [
            'name'     => [
                'required' => true,
                'type'     => 'string',
                'max'      => 50,
            ],
            'email'    => [
                'required' => true,
                'type'     => 'email',
                'max'      => 100,
            ],
            'message'  => [
                'required' => true,
                'type'     => 'string',
                'max'      => 500,
            ],
            // Optional CAPTCHA field
            'captcha' => [
                'required' => false,
                'type'     => 'string',
                'max'      => 10,
            ],
        ];

        $sanitized = $validator->validate( $_POST, $fields );
        if ( is_wp_error( $sanitized ) ) {
            wp_send_json_error( [ 'message' => $sanitized->get_error_message() ], 400 );
        }

        // 4. Optional CAPTCHA check (if you enable it)
        if ( ! empty( $sanitized['captcha'] ) && ! VAPT_Captcha::instance()->verify( $sanitized['captcha'] ) ) {
            wp_send_json_error( [ 'message' => __( 'CAPTCHA verification failed.', 'vapt-security' ) ], 400 );
        }

        // 5. Process the form (e.g., send email)
        $to      = get_option( 'admin_email' );
        $subject = sprintf( __( 'New message from %s', 'vapt-security' ), $sanitized['name'] );
        $body    = sprintf(
            __( "Name: %s\nEmail: %s\n\nMessage:\n%s", 'vapt-security' ),
            $sanitized['name'],
            $sanitized['email'],
            $sanitized['message']
        );

        wp_mail( $to, $subject, $body );

        wp_send_json_success( [ 'message' => __( 'Your message was sent successfully.', 'vapt-security' ) ] );
    }

    /**
     * Enqueue plugin assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'vapt-security-style',
            plugins_url( 'assets/css/vapt-security.css', __FILE__ ),
            [],
            self::VERSION
        );
        wp_enqueue_script(
            'vapt-security-script',
            plugins_url( 'assets/js/vapt-security.js', __FILE__ ),
            [ 'jquery' ],
            self::VERSION,
            true
        );

        // Localize script for AJAX and nonce
        wp_localize_script(
            'vapt-security-script',
            'VAPT_SECURITY',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'vapt_form_action' ),
            ]
        );
    }
}

// Initialize the plugin
VAPT_Security::instance();

/**
 * Enqueue admin assets for the settings page.
 */
public function enqueue_admin_assets( $hook ) {
    // Only load on our plugin's settings page
    if ( strpos( $hook, 'settings_page_vapt-security' ) === false ) {
        return;
    }

    // jQuery UI Tabs is part of the core
    wp_enqueue_script( 'jquery-ui-tabs' );
    wp_enqueue_style( 'jquery-ui', includes_url( 'css/jquery-ui.css' ) );

    // Custom CSS to hide/show tab panels
    wp_add_inline_style(
        'jquery-ui',
        '#vapt-security-tabs .ui-tabs-panel { padding: 20px; }'
    );
}
add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

/**
 * Hook into admin_menu to add the settings page.
 */
public function register_admin_menu() {
    add_options_page(
        __( 'VAPT Security', 'vapt-security' ),
        __( 'VAPT Security', 'vapt-security' ),
        'manage_options',
        'vapt-security',
        [ $this, 'render_settings_page' ]
    );
}
add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

/**
 * Register settings, sections, and fields.
 */
public function register_settings() {
    // The options group that will be used by settings_fields()
    register_setting( 'vapt_security_options_group', 'vapt_security_options', [
        'sanitize_callback' => [ $this, 'sanitize_options' ],
    ] );

    /* ------------------------------------------------------------------ */
    /* General tab */
    /* ------------------------------------------------------------------ */
    add_settings_section(
        'vapt_security_general',
        __( 'General Settings', 'vapt-security' ),
        null,
        'vapt_security_general'
    );

    add_settings_field(
        'enable_cron',
        __( 'Enable WP‑Cron Scheduling', 'vapt-security' ),
        [ $this, 'render_enable_cron_cb' ],
        'vapt_security_general',
        'vapt_security_general'
    );

    /* ------------------------------------------------------------------ */
    /* Rate Limiter tab */
    /* ------------------------------------------------------------------ */
    add_settings_section(
        'vapt_security_rate_limiter',
        __( 'Rate Limiter', 'vapt-security' ),
        null,
        'vapt_security_rate_limiter'
    );

    add_settings_field(
        'rate_limit_max',
        __( 'Max Requests per Minute', 'vapt-security' ),
        [ $this, 'render_rate_limit_max_cb' ],
        'vapt_security_rate_limiter',
        'vapt_security_rate_limiter'
    );

    /* ------------------------------------------------------------------ */
    /* Input Validation tab */
    /* ------------------------------------------------------------------ */
    add_settings_section(
        'vapt_security_validation',
        __( 'Input Validation', 'vapt-security' ),
        null,
        'vapt_security_validation'
    );

    add_settings_field(
        'validation_email',
        __( 'Require Valid Email?', 'vapt-security' ),
        [ $this, 'render_validation_email_cb' ],
        'vapt_security_validation',
        'vapt_security_validation'
    );

    /* ------------------------------------------------------------------ */
    /* WP‑Cron Protection tab */
    /* ------------------------------------------------------------------ */
    add_settings_section(
        'vapt_security_cron',
        __( 'WP‑Cron Protection', 'vapt-security' ),
        null,
        'vapt_security_cron'
    );

    add_settings_field(
        'cron_protection',
        __( 'Enable Cron Rate Limiting', 'vapt-security' ),
        [ $this, 'render_cron_protection_cb' ],
        'vapt_security_cron',
        'vapt_security_cron'
    );
}
add_action( 'admin_init', [ $this, 'register_settings' ] );
