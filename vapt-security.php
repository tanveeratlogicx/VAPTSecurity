<?php
/**
 * Plugin Name: VAPT Security
 * Plugin URI:  https://github.com/your‑username/vapt-security
 * Description: A comprehensive WordPress plugin that protects against DoS via wp‑cron, enforces strict input validation, and throttles form submissions.
 * Version:     1.0.5
 * Author:      Security Qoder
 * Author URI:  https://github.com/your‑username
 * License:     GPL‑2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VAPT_Security
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load configuration file if it exists
$config_file = plugin_dir_path( __FILE__ ) . 'vapt-config.php';
if ( file_exists( $config_file ) ) {
    require_once $config_file;
}

// Define constants if not already defined in config
if ( ! defined( 'VAPT_FEATURE_WP_CRON_PROTECTION' ) ) {
    define( 'VAPT_FEATURE_WP_CRON_PROTECTION', true );
}
if ( ! defined( 'VAPT_FEATURE_RATE_LIMITING' ) ) {
    define( 'VAPT_FEATURE_RATE_LIMITING', true );
}
if ( ! defined( 'VAPT_FEATURE_INPUT_VALIDATION' ) ) {
    define( 'VAPT_FEATURE_INPUT_VALIDATION', true );
}
if ( ! defined( 'VAPT_FEATURE_SECURITY_LOGGING' ) ) {
    define( 'VAPT_FEATURE_SECURITY_LOGGING', true );
}
if ( ! defined( 'VAPT_TEST_WP_CRON_URL' ) ) {
    define( 'VAPT_TEST_WP_CRON_URL', '/wp-cron.php' );
}
if ( ! defined( 'VAPT_TEST_FORM_SUBMISSION_URL' ) ) {
    define( 'VAPT_TEST_FORM_SUBMISSION_URL', '/wp-admin/admin-ajax.php' );
}
if ( ! defined( 'VAPT_SHOW_FEATURE_INFO' ) ) {
    define( 'VAPT_SHOW_FEATURE_INFO', true );
}
if ( ! defined( 'VAPT_SHOW_TEST_URLS' ) ) {
    define( 'VAPT_SHOW_TEST_URLS', true );
}
if ( ! defined( 'VAPT_CLEANUP_INTERVAL' ) ) {
    define( 'VAPT_CLEANUP_INTERVAL', 3600 ); // 1 hour
}
if ( ! defined( 'VAPT_LOG_RETENTION_DAYS' ) ) {
    define( 'VAPT_LOG_RETENTION_DAYS', 30 );
}
if ( ! defined( 'VAPT_WHITELISTED_IPS' ) ) {
    define( 'VAPT_WHITELISTED_IPS', [ '127.0.0.1', '::1' ] );
}
if ( ! defined( 'VAPT_RATE_LIMIT_MESSAGE' ) ) {
    define( 'VAPT_RATE_LIMIT_MESSAGE', 'Too many requests. Please try again later.' );
}
if ( ! defined( 'VAPT_INVALID_NONCE_MESSAGE' ) ) {
    define( 'VAPT_INVALID_NONCE_MESSAGE', 'Invalid request. Please refresh the page and try again.' );
}
if ( ! defined( 'VAPT_DEBUG_MODE' ) ) {
    define( 'VAPT_DEBUG_MODE', false );
}

// Autoload classes.
spl_autoload_register(
    function ( $class ) {
        $prefix = 'VAPT_';

        if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
            return;
        }

        $file = plugin_dir_path( __FILE__ ) . 'includes/class-' . strtolower( str_replace( '_', '-', substr( $class, strlen( $prefix ) ) ) ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    }
);

/**
 * Main plugin class.
 */
final class VAPT_Security {

    /**
     * Instance of the class.
     *
     * @var VAPT_Security
     */
    private static $instance;

    /**
     * Get instance of the class.
     *
     * @return VAPT_Security
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof VAPT_Security ) ) {
            self::$instance = new VAPT_Security();
            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    private function init() {
        // Hook into WordPress.
        add_action( 'init', [ $this, 'protect_wp_cron' ], 1 );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_nopriv_vapt_form_submit', [ $this, 'handle_form_submission' ] );
        add_action( 'wp_ajax_vapt_form_submit', [ $this, 'handle_form_submission' ] );
        add_action( 'init', [ $this, 'initialize_security_logging' ] );
        add_action( 'vapt_cleanup_event', [ $this, 'cleanup_old_data' ] );
        
        register_activation_hook( __FILE__, [ $this, 'activate_plugin' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate_plugin' ] );
    }

    /**
     * Protect wp-cron.php from DoS attacks.
     */
    public function protect_wp_cron() {
        // Only apply protection if feature is enabled
        if ( ! VAPT_FEATURE_WP_CRON_PROTECTION ) {
            return;
        }

        // Check if we're accessing wp-cron.php
        if ( strpos( $_SERVER['REQUEST_URI'] ?? '', 'wp-cron.php' ) !== false ) {
            $rate_limiter = new VAPT_Rate_Limiter();
            
            // Check if IP is whitelisted
            $current_ip = $rate_limiter->get_current_ip();
            if ( in_array( $current_ip, VAPT_WHITELISTED_IPS ) ) {
                return; // Allow whitelisted IPs
            }
            
            // Apply rate limiting for cron requests
            if ( ! $rate_limiter->allow_cron_request() ) {
                // Log the blocked request if logging is enabled
                if ( VAPT_FEATURE_SECURITY_LOGGING ) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event( 'blocked_cron_request', [
                        'ip' => $current_ip,
                        'reason' => 'rate_limit_exceeded'
                    ]);
                }
                
                // Send 429 Too Many Requests response
                http_response_code( 429 );
                wp_die( esc_html__( VAPT_RATE_LIMIT_MESSAGE, 'vapt-security' ), '', [ 'response' => 429 ] );
            }
        }

        // Disable default WP-Cron if option is enabled
        $opts = get_option( 'vapt_security_options', [] );
        if ( isset( $opts['enable_cron'] ) && $opts['enable_cron'] ) {
            define( 'DISABLE_WP_CRON', true );
        }
    }

    /**
     * Register the admin menu.
     */
    public function register_admin_menu() {
        // Add top-level menu page above Appearance
        add_menu_page(
            __( 'VAPT Security', 'vapt-security' ),
            __( 'VAPT Security', 'vapt-security' ),
            'manage_options',
            'vapt-security',
            [ $this, 'render_settings_page' ],
            'dashicons-shield',
            65 // Position above Appearance (60) but below Plugins (65)
        );
    }

    /**
     * Enqueue assets that are only needed on the plugin settings page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_vapt-security' !== $hook ) {
            return;
        }

        // jQuery UI Tabs is part of core WP.
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_style( 'jquery-ui', includes_url( 'css/jquery-ui.css' ) );

        // Custom CSS for the plugin.
        wp_enqueue_style( 'vapt-security-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', [], '1.0.5' );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-settings.php';
    }

    /**
     * Register settings, sections, and fields.
     */
    public function register_settings() {
        register_setting(
            'vapt_security_options_group',
            'vapt_security_options',
            [ $this, 'sanitize_options' ]
        );

        /* ------------------------------------------------------------------ */
        /* General tab                                                        */
        /* ------------------------------------------------------------------ */
        add_settings_section(
            'vapt_security_general',
            __( 'General Settings', 'vapt-security' ),
            function() {
                if ( VAPT_SHOW_FEATURE_INFO ) {
                    echo '<p>' . esc_html__( 'General settings for the VAPT Security plugin.', 'vapt-security' ) . '</p>';
                }
                if ( VAPT_SHOW_TEST_URLS ) {
                    echo '<p><strong>' . esc_html__( 'Test URL:', 'vapt-security' ) . '</strong> <a href="' . esc_url( home_url( '/' ) ) . '" target="_blank">' . esc_url( home_url( '/' ) ) . '</a></p>';
                }
            },
            'vapt_security_general'
        );

        add_settings_field(
            'enable_cron',
            __( 'Disable WP‑Cron', 'vapt-security' ),
            [ $this, 'render_enable_cron_cb' ],
            'vapt_security_general',
            'vapt_security_general'
        );

        /* ------------------------------------------------------------------ */
        /* Rate Limiter tab                                                  */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if ( VAPT_FEATURE_RATE_LIMITING ) {
            add_settings_section(
                'vapt_security_rate_limiter',
                __( 'Rate Limiter', 'vapt-security' ),
                function() {
                    if ( VAPT_SHOW_FEATURE_INFO ) {
                        echo '<p>' . esc_html__( 'Controls the rate limiting for form submissions to prevent abuse.', 'vapt-security' ) . '</p>';
                    }
                    if ( VAPT_SHOW_TEST_URLS ) {
                        echo '<p><strong>' . esc_html__( 'Test URL:', 'vapt-security' ) . '</strong> <a href="' . esc_url( home_url( '/test-form/' ) ) . '" target="_blank">' . esc_url( home_url( '/test-form/' ) ) . '</a></p>';
                        echo '<p class="description">' . esc_html__( 'Note: Create a test form on this page to verify rate limiting functionality.', 'vapt-security' ) . '</p>';
                    }
                },
                'vapt_security_rate_limiter'
            );

            add_settings_field(
                'rate_limit_max',
                __( 'Max Requests per Minute', 'vapt-security' ),
                [ $this, 'render_rate_limit_max_cb' ],
                'vapt_security_rate_limiter',
                'vapt_security_rate_limiter'
            );

            add_settings_field(
                'rate_limit_window',
                __( 'Rate Limit Window (minutes)', 'vapt-security' ),
                [ $this, 'render_rate_limit_window_cb' ],
                'vapt_security_rate_limiter',
                'vapt_security_rate_limiter'
            );
        }

        /* ------------------------------------------------------------------ */
        /* Input Validation tab                                            */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if ( VAPT_FEATURE_INPUT_VALIDATION ) {
            add_settings_section(
                'vapt_security_validation',
                __( 'Input Validation', 'vapt-security' ),
                function() {
                    if ( VAPT_SHOW_FEATURE_INFO ) {
                        echo '<p>' . esc_html__( 'Validates and sanitizes user input to prevent XSS and injection attacks.', 'vapt-security' ) . '</p>';
                    }
                    if ( VAPT_SHOW_TEST_URLS ) {
                        echo '<p><strong>' . esc_html__( 'Test URL:', 'vapt-security' ) . '</strong> <a href="' . esc_url( home_url( '/test-form/' ) ) . '" target="_blank">' . esc_url( home_url( '/test-form/' ) ) . '</a></p>';
                        echo '<p class="description">' . esc_html__( 'Note: Create a test form on this page to verify input validation functionality.', 'vapt-security' ) . '</p>';
                    }
                },
                'vapt_security_validation'
            );

            add_settings_field(
                'validation_email',
                __( 'Require Valid Email?', 'vapt-security' ),
                [ $this, 'render_validation_email_cb' ],
                'vapt_security_validation',
                'vapt_security_validation'
            );

            add_settings_field(
                'validation_sanitization_level',
                __( 'Sanitization Level', 'vapt-security' ),
                [ $this, 'render_sanitization_level_cb' ],
                'vapt_security_validation',
                'vapt_security_validation'
            );
        }

        /* ------------------------------------------------------------------ */
        /* WP‑Cron Protection tab                                         */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if ( VAPT_FEATURE_WP_CRON_PROTECTION ) {
            add_settings_section(
                'vapt_security_cron',
                __( 'WP‑Cron Protection', 'vapt-security' ),
                function() {
                    if ( VAPT_SHOW_FEATURE_INFO ) {
                        echo '<p>' . esc_html__( 'Protects against DoS attacks targeting the WordPress cron system.', 'vapt-security' ) . '</p>';
                    }
                    if ( VAPT_SHOW_TEST_URLS ) {
                        echo '<p><strong>' . esc_html__( 'Test URL:', 'vapt-security' ) . '</strong> <a href="' . esc_url( home_url( VAPT_TEST_WP_CRON_URL ) ) . '" target="_blank">' . esc_url( home_url( VAPT_TEST_WP_CRON_URL ) ) . '</a></p>';
                        echo '<p class="description">' . esc_html__( 'Warning: Visiting this URL may trigger rate limiting if enabled.', 'vapt-security' ) . '</p>';
                    }
                },
                'vapt_security_cron'
            );

            add_settings_field(
                'cron_protection',
                __( 'Enable Cron Rate Limiting', 'vapt-security' ),
                [ $this, 'render_cron_protection_cb' ],
                'vapt_security_cron',
                'vapt_security_cron'
            );

            add_settings_field(
                'cron_rate_limit',
                __( 'Max Cron Requests per Hour', 'vapt-security' ),
                [ $this, 'render_cron_rate_limit_cb' ],
                'vapt_security_cron',
                'vapt_security_cron'
            );
        }

        /* ------------------------------------------------------------------ */
        /* Security Logging tab                                           */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if ( VAPT_FEATURE_SECURITY_LOGGING ) {
            add_settings_section(
                'vapt_security_logging',
                __( 'Security Logging', 'vapt-security' ),
                function() {
                    if ( VAPT_SHOW_FEATURE_INFO ) {
                        echo '<p>' . esc_html__( 'Logs security events for monitoring and analysis.', 'vapt-security' ) . '</p>';
                    }
                    if ( VAPT_SHOW_TEST_URLS ) {
                        echo '<p><strong>' . esc_html__( 'Test URL:', 'vapt-security' ) . '</strong> <a href="' . esc_url( home_url( '/test-form/' ) ) . '" target="_blank">' . esc_url( home_url( '/test-form/' ) ) . '</a></p>';
                        echo '<p class="description">' . esc_html__( 'Note: Create a test form on this page to verify logging functionality.', 'vapt-security' ) . '</p>';
                    }
                },
                'vapt_security_logging'
            );

            add_settings_field(
                'enable_logging',
                __( 'Enable Security Logging', 'vapt-security' ),
                [ $this, 'render_enable_logging_cb' ],
                'vapt_security_logging',
                'vapt_security_logging'
            );
        }
    }

    /**
     * Sanitize the options array.
     *
     * @param array $input Raw input.
     *
     * @return array Sanitized values.
     */
    public function sanitize_options( $input ) {
        $sanitized = [];

        $sanitized['enable_cron']             = isset( $input['enable_cron'] ) ? 1 : 0;
        $sanitized['rate_limit_max']          = isset( $input['rate_limit_max'] ) ? absint( $input['rate_limit_max'] ) : 10;
        $sanitized['rate_limit_window']       = isset( $input['rate_limit_window'] ) ? absint( $input['rate_limit_window'] ) : 1;
        $sanitized['validation_email']        = isset( $input['validation_email'] ) ? 1 : 0;
        $sanitized['validation_sanitization_level'] = isset( $input['validation_sanitization_level'] ) ? sanitize_text_field( $input['validation_sanitization_level'] ) : 'standard';
        $sanitized['cron_protection']         = isset( $input['cron_protection'] ) ? 1 : 0;
        $sanitized['cron_rate_limit']         = isset( $input['cron_rate_limit'] ) ? absint( $input['cron_rate_limit'] ) : 60;
        $sanitized['enable_logging']          = isset( $input['enable_logging'] ) ? 1 : 0;

        return $sanitized;
    }

    /* ------------------------------------------------------------------ */
    /* Render callbacks for the settings fields                         */
    /* ------------------------------------------------------------------ */

    public function render_enable_cron_cb() {
        $opts   = get_option( 'vapt_security_options', [] );
        $checked = isset( $opts['enable_cron'] ) ? checked( 1, $opts['enable_cron'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="vapt_security_options[enable_cron]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e( 'Disable WP‑Cron (recommended for production sites)', 'vapt-security' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Prevents abuse of the WordPress cron system by disabling the default behavior and requiring manual cron setup.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_rate_limit_max_cb() {
        $opts = get_option( 'vapt_security_options', [] );
        $val  = isset( $opts['rate_limit_max'] ) ? absint( $opts['rate_limit_max'] ) : 10;
        ?>
        <input type="number" name="vapt_security_options[rate_limit_max]" value="<?php echo esc_attr( $val ); ?>" min="1" max="1000" />
        <p class="description"><?php esc_html_e( 'Maximum form submissions allowed per minute per IP address.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_rate_limit_window_cb() {
        $opts = get_option( 'vapt_security_options', [] );
        $val  = isset( $opts['rate_limit_window'] ) ? absint( $opts['rate_limit_window'] ) : 1;
        ?>
        <input type="number" name="vapt_security_options[rate_limit_window]" value="<?php echo esc_attr( $val ); ?>" min="1" max="60" />
        <p class="description"><?php esc_html_e( 'Time window in minutes for rate limiting.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_validation_email_cb() {
        $opts   = get_option( 'vapt_security_options', [] );
        $checked = isset( $opts['validation_email'] ) ? checked( 1, $opts['validation_email'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="vapt_security_options[validation_email]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e( 'Require a valid email address for all forms', 'vapt-security' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Enforces email validation on all form submissions to prevent spam and invalid data.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_sanitization_level_cb() {
        $opts = get_option( 'vapt_security_options', [] );
        $val  = isset( $opts['validation_sanitization_level'] ) ? sanitize_text_field( $opts['validation_sanitization_level'] ) : 'standard';
        ?>
        <select name="vapt_security_options[validation_sanitization_level]">
            <option value="basic" <?php selected( $val, 'basic' ); ?>><?php esc_html_e( 'Basic', 'vapt-security' ); ?></option>
            <option value="standard" <?php selected( $val, 'standard' ); ?>><?php esc_html_e( 'Standard', 'vapt-security' ); ?></option>
            <option value="strict" <?php selected( $val, 'strict' ); ?>><?php esc_html_e( 'Strict', 'vapt-security' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Higher levels provide more security but may block legitimate input.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_cron_protection_cb() {
        $opts   = get_option( 'vapt_security_options', [] );
        $checked = isset( $opts['cron_protection'] ) ? checked( 1, $opts['cron_protection'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="vapt_security_options[cron_protection]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e( 'Enable rate‑limiting on wp-cron endpoints', 'vapt-security' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Protects against DoS attacks by limiting requests to wp-cron.php.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_cron_rate_limit_cb() {
        $opts = get_option( 'vapt_security_options', [] );
        $val  = isset( $opts['cron_rate_limit'] ) ? absint( $opts['cron_rate_limit'] ) : 60;
        ?>
        <input type="number" name="vapt_security_options[cron_rate_limit]" value="<?php echo esc_attr( $val ); ?>" min="1" max="1000" />
        <p class="description"><?php esc_html_e( 'Maximum cron requests allowed per hour.', 'vapt-security' ); ?></p>
        <?php
    }

    public function render_enable_logging_cb() {
        $opts   = get_option( 'vapt_security_options', [] );
        $checked = isset( $opts['enable_logging'] ) ? checked( 1, $opts['enable_logging'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="vapt_security_options[enable_logging]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e( 'Enable security event logging', 'vapt-security' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Log security events for monitoring and analysis.', 'vapt-security' ); ?></p>
        <?php
    }

    /**
     * Initialize security logging
     */
    public function initialize_security_logging() {
        // Logging is initialized on demand when needed
    }

    /**
     * Handle plugin activation
     */
    public function activate_plugin() {
        // Schedule cleanup event
        if ( ! wp_next_scheduled( 'vapt_cleanup_event' ) ) {
            wp_schedule_event( time(), 'hourly', 'vapt_cleanup_event' );
        }
    }

    /**
     * Handle plugin deactivation
     */
    public function deactivate_plugin() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'vapt_cleanup_event' );
    }

    /**
     * Cleanup old data
     */
    public function cleanup_old_data() {
        $rate_limiter = new VAPT_Rate_Limiter();
        $rate_limiter->clean_old_entries();
        
        $logger = new VAPT_Security_Logger();
        $logger->cleanup_old_logs();
    }

    /* ------------------------------------------------------------------ */
    /* AJAX form handling                                               */
    /* ------------------------------------------------------------------ */

    public function handle_form_submission() {
        // Only process if feature is enabled
        if ( ! VAPT_FEATURE_RATE_LIMITING && ! VAPT_FEATURE_INPUT_VALIDATION ) {
            wp_send_json_error( [ 'message' => __( 'Form processing is disabled.', 'vapt-security' ) ], 400 );
            return;
        }

        // Log the form submission attempt if logging is enabled
        if ( VAPT_FEATURE_SECURITY_LOGGING ) {
            $logger = new VAPT_Security_Logger();
            $rate_limiter = new VAPT_Rate_Limiter();
            $logger->log_event( 'form_submission_attempt', [
                'ip' => $rate_limiter->get_current_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        }

        // 1. Rate limiting (if enabled)
        if ( VAPT_FEATURE_RATE_LIMITING ) {
            $rate_limiter = new VAPT_Rate_Limiter();
            
            // Check if IP is whitelisted
            $current_ip = $rate_limiter->get_current_ip();
            if ( ! in_array( $current_ip, VAPT_WHITELISTED_IPS ) && ! $rate_limiter->allow_request() ) {
                // Log the blocked request if logging is enabled
                if ( VAPT_FEATURE_SECURITY_LOGGING ) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event( 'blocked_form_submission', [
                        'ip' => $current_ip,
                        'reason' => 'rate_limit_exceeded'
                    ]);
                }
                
                wp_send_json_error(
                    [
                        'message' => __( VAPT_RATE_LIMIT_MESSAGE, 'vapt-security' ),
                    ],
                    429
                );
            }
        }

        // 2. Nonce verification
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'vapt_form_action' ) ) {
            // Log the invalid nonce if logging is enabled
            if ( VAPT_FEATURE_SECURITY_LOGGING ) {
                $logger = new VAPT_Security_Logger();
                $logger->log_event( 'invalid_nonce', [
                    'ip' => $rate_limiter->get_current_ip()
                ]);
            }
            
            wp_send_json_error(
                [
                    'message' => __( VAPT_INVALID_NONCE_MESSAGE, 'vapt-security' ),
                ],
                400
            );
        }

        // 3. Input validation (if enabled)
        if ( VAPT_FEATURE_INPUT_VALIDATION ) {
            $validator = new VAPT_Input_Validator();
            $schema    = [
                'name'    => [ 'required' => true,  'type' => 'string', 'max' => 50 ],
                'email'   => [ 'required' => true,  'type' => 'email',  'max' => 100 ],
                'message' => [ 'required' => true,  'type' => 'string', 'max' => 500 ],
                'captcha' => [ 'required' => false, 'type' => 'string', 'max' => 10  ],
            ];
            $data = $validator->validate( $_POST, $schema );

            if ( is_wp_error( $data ) ) {
                // Log the validation error if logging is enabled
                if ( VAPT_FEATURE_SECURITY_LOGGING ) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event( 'validation_error', [
                        'ip' => $rate_limiter->get_current_ip(),
                        'error' => $data->get_error_message()
                    ]);
                }
                
                wp_send_json_error( [ 'message' => $data->get_error_message() ], 400 );
            }
        } else {
            // Basic sanitization if validation is disabled
            $data = [
                'name'    => sanitize_text_field( $_POST['name'] ?? '' ),
                'email'   => sanitize_email( $_POST['email'] ?? '' ),
                'message' => sanitize_textarea_field( $_POST['message'] ?? '' ),
                'captcha' => sanitize_text_field( $_POST['captcha'] ?? '' ),
            ];
        }

        // 4. Optional CAPTCHA check
        if ( ! empty( $data['captcha'] ) ) {
            $captcha = new VAPT_Captcha();
            if ( ! $captcha->verify( $data['captcha'] ) ) {
                // Log the failed CAPTCHA if logging is enabled
                if ( VAPT_FEATURE_SECURITY_LOGGING ) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event( 'failed_captcha', [
                        'ip' => $rate_limiter->get_current_ip()
                    ]);
                }
                
                wp_send_json_error(
                    [
                        'message' => __( 'CAPTCHA verification failed.', 'vapt-security' ),
                    ],
                    400
                );
            }
        }

        // 5. Process the form (e.g., send an email)
        $to      = get_option( 'admin_email' );
        $subject = sprintf(
            __( 'New message from %s', 'vapt-security' ),
            $data['name']
        );
        $body    = sprintf(
            __( "Name: %s\nEmail: %s\n\nMessage:\n%s", 'vapt-security' ),
            $data['name'],
            $data['email'],
            $data['message']
        );

        wp_mail( $to, $subject, $body );

        // Log successful submission if logging is enabled
        if ( VAPT_FEATURE_SECURITY_LOGGING ) {
            $logger = new VAPT_Security_Logger();
            $logger->log_event( 'successful_form_submission', [
                'ip' => $rate_limiter->get_current_ip()
            ]);
        }

        wp_send_json_success( [ 'message' => __( 'Your message was sent successfully.', 'vapt-security' ) ] );
    }
}

/* Kick it off. */
VAPT_Security::instance();