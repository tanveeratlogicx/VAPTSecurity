<?php
/**
 * Plugin Name: VAPT Security
 * Plugin URI:  https://github.com/your‑username/vapt-security
 * Description: A lightweight WordPress plugin that protects against DoS via wp‑cron, enforces strict input validation, and throttles form submissions.
 * Version:     1.0.0
 * Author:      Tanveer Atlogicx
 * Author URI:  https://github.com/tanveeratlogicx
 * License:     GPL‑2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VAPT_Security
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin class.
 */
final class VAPT_Security {

    /**
     * Path to the plugin file.
     *
     * @var string
     */
    const FILE = __FILE__;

    /**
     * Singleton instance.
     *
     * @var VAPT_Security
     */
    private static $instance;

    /**
     * Return the singleton instance.
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
     * Construct the plugin.
     */
    private function __construct() {
        /* ------------------------------------------------------------------ */
        /* 1. Load dependencies                                               */
        /* ------------------------------------------------------------------ */
        $this->includes();

        /* ------------------------------------------------------------------ */
        /* 2. Cron handling                                                   */
        /* ------------------------------------------------------------------ */
        VAPT_Cron::instance();

        /* ------------------------------------------------------------------ */
        /* 3. Register admin page & settings                                  */
        /* ------------------------------------------------------------------ */
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        /* ------------------------------------------------------------------ */
        /* 4. AJAX handlers (used by the contact form)                       */
        /* ------------------------------------------------------------------ */
        add_action( 'wp_ajax_nopriv_vapt_form_submit', [ $this, 'handle_form_submission' ] );
        add_action( 'wp_ajax_vapt_form_submit', [ $this, 'handle_form_submission' ] );
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/cron.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/rate-limiter.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/input-validator.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/captcha.php';
    }

    /* ------------------------------------------------------------------ */
    /* Admin Page & Settings                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Register the settings page under Settings → VAPT Security.
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

    /**
     * Enqueue assets that are only needed on the plugin settings page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_vapt-security' !== $hook ) {
            return;
        }

        // jQuery UI Tabs is part of core WP.
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_style( 'jquery-ui', includes_url( 'css/jquery-ui.css' ) );

        // Custom CSS for the tabs.
        wp_add_inline_style(
            'jquery-ui',
            '#vapt-security-tabs .ui-tabs-panel { padding:20px; }'
        );
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
        /* Rate Limiter tab                                                  */
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
        /* Input Validation tab                                            */
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
        /* WP‑Cron Protection tab                                         */
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

    /**
     * Sanitize the options array.
     *
     * @param array $input Raw input.
     *
     * @return array Sanitized values.
     */
    public function sanitize_options( $input ) {
        $sanitized = [];

        $sanitized['enable_cron']      = isset( $input['enable_cron'] ) ? 1 : 0;
        $sanitized['rate_limit_max']   = absint( $input['rate_limit_max'] );
        $sanitized['validation_email'] = isset( $input['validation_email'] ) ? 1 : 0;
        $sanitized['cron_protection']  = isset( $input['cron_protection'] ) ? 1 : 0;

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
            <?php esc_html_e( 'Enable WP‑Cron Scheduling (use system cron instead)', 'vapt-security' ); ?>
        </label>
        <?php
    }

    public function render_rate_limit_max_cb() {
        $opts = get_option( 'vapt_security_options', [] );
        $val  = isset( $opts['rate_limit_max'] ) ? absint( $opts['rate_limit_max'] ) : 10;
        ?>
        <input type="number" name="vapt_security_options[rate_limit_max]" value="<?php echo esc_attr( $val ); ?>" min="1" max="1000" />
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
        <?php
    }

    public function render_cron_protection_cb() {
        $opts   = get_option( 'vapt_security_options', [] );
        $checked = isset( $opts['cron_protection'] ) ? checked( 1, $opts['cron_protection'], false ) : '';
        ?>
        <label>
            <input type="checkbox" name="vapt_security_options[cron_protection]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e( 'Enable rate‑limiting on custom cron endpoints', 'vapt-security' ); ?>
        </label>
        <?php
    }

    /* ------------------------------------------------------------------ */
    /* AJAX form handling                                               */
    /* ------------------------------------------------------------------ */

    public function handle_form_submission() {
        // 1. Rate limiting
        if ( ! VAPT_Rate_Limiter::instance()->allow_request() ) {
            wp_send_json_error(
                [
                    'message' => __( 'Too many requests. Please try again later.', 'vapt-security' ),
                ],
                429
            );
        }

        // 2. Nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'vapt_form_action' ) ) {
            wp_send_json_error(
                [
                    'message' => __( 'Invalid nonce.', 'vapt-security' ),
                ],
                400
            );
        }

        // 3. Input validation
        $validator = new VAPT_Input_Validator();
        $schema    = [
            'name'    => [ 'required' => true,  'type' => 'string', 'max' => 50 ],
            'email'   => [ 'required' => true,  'type' => 'email',  'max' => 100 ],
            'message' => [ 'required' => true,  'type' => 'string', 'max' => 500 ],
            'captcha' => [ 'required' => false, 'type' => 'string', 'max' => 10  ],
        ];
        $data = $validator->validate( $_POST, $schema );

        if ( is_wp_error( $data ) ) {
            wp_send_json_error( [ 'message' => $data->get_error_message() ], 400 );
        }

        // 4. Optional CAPTCHA check
        if ( ! empty( $data['captcha'] ) && ! VAPT_Captcha::instance()->verify( $data['captcha'] ) ) {
            wp_send_json_error(
                [
                    'message' => __( 'CAPTCHA verification failed.', 'vapt-security' ),
                ],
                400
            );
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

        wp_send_json_success( [ 'message' => __( 'Your message was sent successfully.', 'vapt-security' ) ] );
    }
}

/* Kick it off. */
VAPT_Security::instance();
