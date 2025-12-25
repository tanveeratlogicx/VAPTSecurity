<?php

/**
 * Plugin Name: VAPT Security
 * Plugin URI:  https://github.com/tanveeratlogicx/vapt-security
 * Description: A comprehensive WordPress plugin that protects against DoS via wp‑cron, enforces strict input validation, and throttles form submissions.
 * Version:     3.0.5
 * Author:      Tanveer Malik
 * Author URI:  https://github.com/tanveeratlogicx
 * License:     GPL‑2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package VAPT_Security
 */

if (!defined("VAPT_VERSION")) {
    define("VAPT_VERSION", "3.0.5");
}

// If this file is called directly, abort.
if (!defined("WPINC")) {
    die();
}

// Load Domain Features
$vapt_domain_features = get_option("vapt_domain_features", []);

// Helper to get feature state (Default: true)
$vapt_is_cron_active = isset($vapt_domain_features["cron_protection"])
    ? (bool) $vapt_domain_features["cron_protection"]
    : true;
$vapt_is_rate_active = isset($vapt_domain_features["rate_limiting"])
    ? (bool) $vapt_domain_features["rate_limiting"]
    : true;
$vapt_is_input_active = isset($vapt_domain_features["input_validation"])
    ? (bool) $vapt_domain_features["input_validation"]
    : true;
$vapt_is_logging_active = isset($vapt_domain_features["security_logging"])
    ? (bool) $vapt_domain_features["security_logging"]
    : true;

// Define constants if not already defined in config
if (!defined("VAPT_FEATURE_WP_CRON_PROTECTION")) {
    define("VAPT_FEATURE_WP_CRON_PROTECTION", $vapt_is_cron_active);
}
if (!defined("VAPT_FEATURE_RATE_LIMITING")) {
    define("VAPT_FEATURE_RATE_LIMITING", $vapt_is_rate_active);
}
if (!defined("VAPT_FEATURE_INPUT_VALIDATION")) {
    define("VAPT_FEATURE_INPUT_VALIDATION", $vapt_is_input_active);
}
if (!defined("VAPT_FEATURE_SECURITY_LOGGING")) {
    define("VAPT_FEATURE_SECURITY_LOGGING", $vapt_is_logging_active);
}

// Load configuration file if it exists
$config_file = plugin_dir_path(__FILE__) . "vapt-config.php";
if (file_exists($config_file)) {
    require_once $config_file;
}

if (!defined("VAPT_TEST_WP_CRON_URL")) {
    define("VAPT_TEST_WP_CRON_URL", "/wp-cron.php");
}
if (!defined("VAPT_TEST_FORM_SUBMISSION_URL")) {
    define("VAPT_TEST_FORM_SUBMISSION_URL", "/wp-admin/admin-ajax.php");
}
if (!defined("VAPT_SHOW_FEATURE_INFO")) {
    define("VAPT_SHOW_FEATURE_INFO", true);
}
if (!defined("VAPT_SHOW_TEST_URLS")) {
    define("VAPT_SHOW_TEST_URLS", true);
}
if (!defined("VAPT_CLEANUP_INTERVAL")) {
    define("VAPT_CLEANUP_INTERVAL", 3600); // 1 hour
}
if (!defined("VAPT_LOG_RETENTION_DAYS")) {
    define("VAPT_LOG_RETENTION_DAYS", 30);
}
if (!defined("VAPT_WHITELISTED_IPS")) {
    define("VAPT_WHITELISTED_IPS", ["127.0.0.1", "::1"]);
}
if (!defined("VAPT_RATE_LIMIT_MESSAGE")) {
    define(
        "VAPT_RATE_LIMIT_MESSAGE",
        "Too many requests. Please try again later.",
    );
}
if (!defined("VAPT_INVALID_NONCE_MESSAGE")) {
    define(
        "VAPT_INVALID_NONCE_MESSAGE",
        "Invalid request. Please refresh the page and try again.",
    );
}
if (!defined("VAPT_DEBUG_MODE")) {
    define("VAPT_DEBUG_MODE", false);
}

// Autoload classes.
spl_autoload_register(function ($class) {
    $prefix = "VAPT_";

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $file =
        plugin_dir_path(__FILE__) .
        "includes/class-" .
        strtolower(str_replace("_", "-", substr($class, strlen($prefix)))) .
        ".php";

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Main plugin class.
 */
final class VAPT_Security
{
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
    public static function instance()
    {
        if (
            !isset(self::$instance) &&
            !(self::$instance instanceof VAPT_Security)
        ) {
            self::$instance = new VAPT_Security();
            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    private function init()
    {
        // Hook into WordPress.
        add_action("init", [$this, "protect_wp_cron"], 1);
        add_action("init", [$this, "intercept_domain_control_access"], 5); // Run early to intercept
        add_action("admin_menu", [$this, "register_admin_menu"]);
        add_action("admin_init", [$this, "register_settings"]);
        add_action("admin_enqueue_scripts", [$this, "enqueue_admin_assets"]);
        add_action("wp_ajax_nopriv_vapt_form_submit", [
            $this,
            "handle_form_submission",
        ]);
        add_action("wp_ajax_vapt_form_submit", [
            $this,
            "handle_form_submission",
        ]);
        add_action("template_redirect", [$this, "render_test_form_page"]);

        // OTP AJAX
        add_action("wp_ajax_vapt_send_otp", [$this, "handle_send_otp"]);
        add_action("wp_ajax_vapt_verify_otp", [$this, "handle_verify_otp"]);

        // License AJAX
        add_action("wp_ajax_vapt_update_license", [
            $this,
            "handle_update_license",
        ]);
        add_action("wp_ajax_nopriv_vapt_update_license", [
            $this,
            "handle_update_license",
        ]);
        add_action("wp_ajax_vapt_renew_license", [
            $this,
            "handle_renew_license",
        ]);
        add_action("wp_ajax_nopriv_vapt_renew_license", [
            $this,
            "handle_renew_license",
        ]);

        // Domain Features AJAX
        add_action("wp_ajax_vapt_save_domain_features", [
            $this,
            "handle_save_domain_features",
        ]);
        add_action("wp_ajax_nopriv_vapt_save_domain_features", [
            $this,
            "handle_save_domain_features",
        ]);

        // Locked Config AJAX
        add_action("wp_ajax_vapt_generate_locked_config", [
            $this,
            "handle_generate_locked_config",
        ]);
        add_action("wp_ajax_nopriv_vapt_generate_locked_config", [
            $this,
            "handle_generate_locked_config",
        ]);
        add_action("wp_ajax_vapt_generate_client_zip", [
            $this,
            "handle_generate_client_zip",
        ]);
        add_action("wp_ajax_vapt_generate_client_zip", [
            $this,
            "handle_generate_client_zip",
        ]);
        add_action("wp_ajax_nopriv_vapt_generate_client_zip", [
            $this,
            "handle_generate_client_zip",
        ]);
        add_action("wp_ajax_vapt_reimport_config", [
            $this,
            "handle_reimport_config",
        ]);
        add_action("wp_ajax_nopriv_vapt_reimport_config", [
            $this,
            "handle_reimport_config",
        ]);

        add_action("init", [$this, "initialize_security_logging"]);
        add_action("init", [$this, "enforce_domain_lock"], 0); // Run early
        add_action("vapt_cleanup_event", [$this, "cleanup_old_data"]);

        register_activation_hook(__FILE__, [$this, "activate_plugin"]);
        register_activation_hook(__FILE__, [$this, "activate_license"]);
        register_deactivation_hook(__FILE__, [$this, "deactivate_plugin"]);

        // Initialize Integrations
        add_action("init", [$this, "init_integrations"]);

        // Initialize Domain Security Features
        add_action("init", ["VAPT_Security_Features", "init"]);
    }

    /**
     * Initialize third-party integrations.
     */
    public function init_integrations()
    {
        $integrations = new VAPT_Integrations_Manager();
        $integrations->init();
    }

    /**
     * Check if the current request is authorized for Superadmin actions.
     * Supports both standard WP User and VAPT Cookie sessions.
     *
     * @return bool
     */
    private function verify_superadmin_access()
    {
        if (self::is_superadmin()) {
            return true;
        }

        // 2. Cookie Session Check
        $cookie_name = "vapt_sa_auth_2";
        $target_email = "tanmalik786@gmail.com";

        if (isset($_COOKIE[$cookie_name])) {
            $expected_hash = hash_hmac(
                "sha256",
                $target_email,
                "VAPT_AUTH_SALT_v1",
            );
            if (hash_equals($expected_hash, $_COOKIE[$cookie_name])) {
                // Refresh cookie? Maybe.
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current environment is local.
     *
     * @return bool
     */
    public function is_local_environment()
    {
        $host = $_SERVER["HTTP_HOST"] ?? "";
        $server_ip = $_SERVER["SERVER_ADDR"] ?? "";
        $remote_ip = $_SERVER["REMOTE_ADDR"] ?? "";

        // Check common local domains and IPs
        if (
            strpos($host, ".local") !== false ||
            strpos($host, ".test") !== false ||
            strpos($host, ".localhost") !== false ||
            $host === "localhost" ||
            $server_ip === "127.0.0.1" ||
            $server_ip === "::1" ||
            $remote_ip === "127.0.0.1" ||
            $remote_ip === "::1"
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user is Superadmin (Standard WP Check).
     *
     * @return bool
     */
    public static function is_superadmin()
    {
        $user = wp_get_current_user();
        if (!$user->exists() || $user->user_login !== "tanmalik786") {
            return false;
        }

        // Strict Email Check unless Local
        if (self::instance()->is_local_environment()) {
            return true;
        }

        return $user->user_email === "tanmalik786@gmail.com";
    }

    /**
     * Intercept access to Domain Control page for OTP flow.
     */
    public function intercept_domain_control_access()
    {
        // Only run if requesting the specific page
        if (!isset($_GET["page"]) || $_GET["page"] !== "vapt-domain-control") {
            return;
        }

        if (self::is_superadmin()) {
            $is_allowed_standard = true;
        }

        // If allowed by standard means, let WP continue (it will load the menu and page normally)
        if ($is_allowed_standard) {
            return;
        }

        // --- NON-STANDARD ACCESS (OTP FLOW) ---

        $target_email = "tanmalik786@gmail.com";
        $cookie_name = "vapt_sa_auth_2"; // Versioned cookie name just in case

        // 1. Process Actions (POST)
        $error = "";
        $message = "";
        $otp_sent = false;

        if ("POST" === $_SERVER["REQUEST_METHOD"]) {
            if (isset($_POST["vapt_request_otp"])) {
                $res = VAPT_OTP::send_otp_to_email($target_email);
                if (is_wp_error($res)) {
                    $error = $res->get_error_message();
                } else {
                    $message = __(
                        "OTP sent to " . $target_email,
                        "vapt-security",
                    );
                    $otp_sent = true;
                }
            } elseif (isset($_POST["vapt_verify_otp"])) {
                $otp = sanitize_text_field($_POST["vapt_otp"] ?? "");
                $res = VAPT_OTP::verify_otp_for_email($target_email, $otp);

                if (is_wp_error($res)) {
                    $error = $res->get_error_message();
                    $otp_sent = true; // Keep the field visible
                } else {
                    // SUCCESS!
                    // Set cookie for 24 hours
                    $expiry = time() + DAY_IN_SECONDS;
                    // Simple hash: md5( email + secret_salt )
                    // We use the locked integrity salt if possible, or a hardcoded one.
                    $hash = hash_hmac(
                        "sha256",
                        $target_email,
                        "VAPT_AUTH_SALT_v1",
                    );

                    setcookie(
                        $cookie_name,
                        $hash,
                        $expiry,
                        COOKIEPATH,
                        COOKIE_DOMAIN,
                        is_ssl(),
                        true,
                    );

                    // Reload to process the view
                    wp_redirect(
                        remove_query_arg([
                            "vapt_request_otp",
                            "vapt_verify_otp",
                        ]),
                    );
                    exit();
                }
            }
        }

        // 2. Check Cookie
        $is_authenticated = false;
        if (isset($_COOKIE[$cookie_name])) {
            $expected_hash = hash_hmac(
                "sha256",
                $target_email,
                "VAPT_AUTH_SALT_v1",
            );
            if (hash_equals($expected_hash, $_COOKIE[$cookie_name])) {
                $is_authenticated = true;
            }
        }

        // 3. Render View
        if ($is_authenticated) { ?>
            <!DOCTYPE html>
            <html class="wp-toolbar">

            <head>
                <meta charset="UTF-8">
                <title>VAPT Domain Control (OTP Access)</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <?php
                wp_enqueue_style("dashicons");
                wp_enqueue_style("common");
                wp_enqueue_style("forms");
                wp_enqueue_style("dashboard");
                wp_enqueue_style("list-tables");
                wp_enqueue_style("edit");
                wp_enqueue_style("revisions");
                wp_enqueue_style("media");
                wp_enqueue_style("themes");
                wp_enqueue_style("about");
                wp_enqueue_style("nav-menus");
                wp_enqueue_style("wp-admin");

                // Enqueue core dependencies and plugin assets for the standalone OTP Domain Control view.
                // We include jquery and jquery-ui-core to ensure tabs behave correctly outside of the normal admin flow.
                wp_enqueue_script("jquery");
                wp_enqueue_script("jquery-ui-core");
                wp_enqueue_script("jquery-ui-tabs");

                // Use WP-provided jQuery UI stylesheet as a best-effort fallback.
                wp_enqueue_style(
                    "jquery-ui",
                    includes_url("css/jquery-ui.css"),
                );

                // Custom CSS for the plugin. Use version constant if available.
                wp_enqueue_style(
                    "vapt-security-admin",
                    plugin_dir_url(__FILE__) . "assets/admin.css",
                    [],
                    defined("VAPT_VERSION") ? VAPT_VERSION : "1.0.0",
                );

                // Inline initializer ensures tabs show up in this standalone wrapper even if some admin scripts are missing.
                $standalone_init = '(function($){
                    $(function(){
                        var el = $("#vapt-domain-tabs-container");
                        if(!el.length) return;
                        if(!$.fn.tabs){
                            el.find(".vapt-domain-tab-content").show();
                            return;
                        }
                        var active = localStorage.getItem("vapt_domain_active_tab");
                        active = active !== null ? parseInt(active, 10) : 0;
                        try{
                            el.tabs({
                                active: active,
                                activate: function(event, ui){
                                    try{ localStorage.setItem("vapt_domain_active_tab", ui.newTab.index()); }catch(e){}
                                }
                            });
                        }catch(e){
                            el.find(".vapt-domain-tab-content").show();
                            if(window.console && console.error) console.error("VAPT standalone tabs init error:", e);
                        }
                    });
                })(jQuery);';

                wp_add_inline_script("jquery-ui-tabs", $standalone_init);

                // Manually print styles/scripts instead of firing admin_enqueue_scripts which requires get_current_screen()
                wp_print_styles();
                wp_print_scripts();

                // We cannot fire 'admin_head' safely as it brings in too many dependencies expecting admin context.
                // do_action( 'admin_head' );
                ?>
                <script type="text/javascript">
                    var ajaxurl = "<?php echo admin_url("admin-ajax.php"); ?>";
                </script>
                <style>
                    html {
                        padding-top: 0 !important;
                    }

                    #wpcontent {
                        margin-left: 0 !important;
                        padding-left: 20px;
                    }

                    .vapt-standalone-header {
                        background: #1d2327;
                        color: #fff;
                        padding: 10px 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }

                    .vapt-standalone-header a {
                        color: #fff;
                        text-decoration: none;
                    }
                </style>
            </head>

            <body class="wp-core-ui">
                <div class="vapt-standalone-header">
                    <div style="font-weight:bold; font-size: 16px;">VAPT Security - Domain Control (Superadmin Mode)</div>
                    <div><a href="<?php echo esc_url(
                                        home_url(),
                                    ); ?>">Back to Site</a></div>
                </div>
                <div id=" wpwrap">
                    <div id="wpcontent">
                        <div id="wpbody" role="main">
                            <div id="wpbody-content">
                                <?php
                                // Mock the user check in the template by overriding the instance check or just suppress errors?
                                // The template checks: $user->user_login !== 'tanmalik786'.
                                // We need to BYPASS that check in the template.
                                // Or we can modify the template to check a constant/flag.
                                define("VAPT_OTP_ACCESS_GRANTED", true);

                                include plugin_dir_path(__FILE__) .
                                    "templates/admin-domain-control.php";
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php do_action("admin_footer"); ?>
            </body>

            </html>
        <?php exit();
        } else { // Render Login Form
            include plugin_dir_path(__FILE__) . "templates/otp-login.php";
            exit();
        }
    }

    /**
     * Protect wp-cron.php from DoS attacks.
     */
    public function protect_wp_cron()
    {
        // Only apply protection if feature is enabled
        if (!VAPT_FEATURE_WP_CRON_PROTECTION) {
            return;
        }

        // Check if we're accessing wp-cron.php
        if (strpos($_SERVER["REQUEST_URI"] ?? "", "wp-cron.php") !== false) {
            $rate_limiter = new VAPT_Rate_Limiter();

            // Check if IP is whitelisted
            $current_ip = $rate_limiter->get_current_ip();
            if (in_array($current_ip, VAPT_WHITELISTED_IPS)) {
                return; // Allow whitelisted IPs
            }

            // Apply rate limiting for cron requests
            if (!$rate_limiter->allow_cron_request()) {
                // Log the blocked request if logging is enabled
                if (VAPT_FEATURE_SECURITY_LOGGING) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event("blocked_cron_request", [
                        "ip" => $current_ip,
                        "reason" => "rate_limit_exceeded",
                    ]);
                }

                // Send 429 Too Many Requests response
                http_response_code(429);
                wp_die(
                    esc_html__(VAPT_RATE_LIMIT_MESSAGE, "vapt-security"),
                    "",
                    ["response" => 429],
                );
            }
        }

        // Disable default WP-Cron if option is enabled
        $opts = $this->get_config();
        if (isset($opts["enable_cron"]) && $opts["enable_cron"]) {
            if (!defined("DISABLE_WP_CRON")) {
                define("DISABLE_WP_CRON", true);
            }
        }
    }

    /**
     * Get decrypted configuration.
     *
     * @return array
     */
    public function get_config()
    {
        $raw = get_option("vapt_security_options", []);

        // If it's an array, it's not encrypted yet (legacy or fresh install before save)
        if (is_array($raw)) {
            return $raw;
        }

        // It's a string, so decrypt it
        $json = VAPT_Encryption::decrypt($raw);
        if ($json) {
            return json_decode($json, true) ?: [];
        }

        return [];
    }

    /**
     * Register the admin menu.
     */
    public function register_admin_menu()
    {
        // Add top-level menu page above Appearance
        add_menu_page(
            __("VAPT Security", "vapt-security"),
            __("VAPT Security", "vapt-security"),
            "manage_options",
            "vapt-security",
            [$this, "render_settings_page"],
            "dashicons-shield",
            65,
        );

        // Domain Control Page (Conditionally Visible Submenu for Superadmin)
        if (self::is_superadmin()) {
            add_submenu_page(
                "vapt-security", // Parent slug
                __("VAPT Domain Admin", "vapt-security"),
                __("Domain Admin", "vapt-security"),
                "manage_options",
                "vapt-domain-control",
                [$this, "render_domain_control_page"],
            );
        }
    }

    /**
     * Enqueue assets that are only needed on the plugin settings page.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Basic safety: ensure $hook is a string
        if (!is_string($hook)) {
            return;
        }

        // Allow any admin page that references the plugin slugs so we don't miss custom hook names.
        // This covers: top-level, submenu, and any hook variants.
        if (
            strpos($hook, "vapt-security") === false &&
            strpos($hook, "vapt-domain-control") === false
        ) {
            return;
        }

        // Ensure core dependencies are present
        wp_enqueue_script("jquery");
        wp_enqueue_script("jquery-ui-core");
        wp_enqueue_script("jquery-ui-tabs");

        // Enqueue a stable WP-provided jQuery UI stylesheet (best-effort)
        wp_enqueue_style("jquery-ui", includes_url("css/jquery-ui.css"));

        // Custom CSS for the plugin. Use defined plugin version constant for cache-busting.
        wp_enqueue_style(
            "vapt-security-admin",
            plugin_dir_url(__FILE__) . "assets/admin.css",
            [],
            defined("VAPT_VERSION") ? VAPT_VERSION : "1.0.0",
        );

        // Add a small, resilient inline initializer to ensure tabs are activated even if the page's inline script
        // runs before jQuery UI is available or in unusual admin contexts (standalone views).
        // This initializer will:
        //  - Look for either the main settings container (#vapt-security-tabs) or domain container (#vapt-domain-tabs-container)
        //  - Fall back to showing tab contents if jQuery UI tabs are not available
        $init_js = '(function($){
            function initTabsFor(el, storageKey){
                if(!el || !el.length) return;
                if(!$.fn.tabs){
                    // If tabs plugin is missing, reveal panels so admin can still see content.
                    el.find(".vapt-security-tab-content, .vapt-domain-tab-content").show();
                    return;
                }
                var active = localStorage.getItem(storageKey);
                active = active !== null ? parseInt(active, 10) : 0;
                try{
                    el.tabs({
                        active: active,
                        activate: function(event, ui){
                            try{ localStorage.setItem(storageKey, ui.newTab.index()); }catch(e){}
                        }
                    });
                }catch(e){
                    // As a last resort, reveal tab content to avoid hiding everything
                    el.find(".vapt-security-tab-content, .vapt-domain-tab-content").show();
                    if(window.console && console.error) console.error("VAPT tab init error:", e);
                }
            }

            $(function(){
                // Priority: settings container, then domain container (standalone)
                var settingsEl = $("#vapt-security-tabs");
                var domainEl = $("#vapt-domain-tabs-container");

                if(settingsEl.length){
                    initTabsFor(settingsEl, "vapt_security_active_tab");
                    return;
                }
                if(domainEl.length){
                    initTabsFor(domainEl, "vapt_domain_active_tab");
                    return;
                }
            });
        })(jQuery);';

        // Attach the inline script to jquery-ui-tabs so it executes after the library is available.
        // If for any reason jquery-ui-tabs is not present, WordPress will still print jQuery and this inline script will run,
        // but it will gracefully degrade (showing panels).
        wp_add_inline_script("jquery-ui-tabs", $init_js);
    }

    /**
     * Render the settings page (Standard Admin View).
     */
    public function render_settings_page()
    {
        // App Settings for Admins
        // No OTP required here. Just standard ACL check (handled by WP routing).

        include plugin_dir_path(__FILE__) . "templates/admin-settings.php";
    }

    /**
     * Render the Domain Control page (Superadmin View).
     */
    public function render_domain_control_page()
    {
        if (!$this->verify_superadmin_access()) {
            wp_die(
                __(
                    "Access Denied: You do not have permission to view this page.",
                    "vapt-security",
                ),
            );
        }

        include plugin_dir_path(__FILE__) .
            "templates/admin-domain-control.php";
    }

    /**
     * Render the Virtual Test Form Page.
     */
    public function render_test_form_page()
    {
        $uri = $_SERVER["REQUEST_URI"] ?? "";
        if (strpos($uri, "/test-form/") !== false) { ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>

            <head>
                <meta charset="<?php bloginfo("charset"); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>VAPT Security - Native Test Form</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        background: #f0f0f1;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 100vh;
                        margin: 0;
                    }

                    .vapt-test-container {
                        background: #fff;
                        padding: 2rem;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                        width: 100%;
                        max-width: 400px;
                    }

                    h1 {
                        margin-top: 0;
                        font-size: 1.5rem;
                        color: #1d2327;
                    }

                    .form-group {
                        margin-bottom: 1rem;
                    }

                    label {
                        display: block;
                        margin-bottom: 0.5rem;
                        font-weight: 600;
                        font-size: 0.9rem;
                    }

                    input,
                    textarea {
                        width: 100%;
                        padding: 0.5rem;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        box-sizing: border-box;
                    }

                    button {
                        background: #2271b1;
                        color: #fff;
                        border: none;
                        padding: 0.6rem 1rem;
                        border-radius: 4px;
                        cursor: pointer;
                        width: 100%;
                        font-size: 1rem;
                    }

                    button:hover {
                        background: #135e96;
                    }

                    #vapt-result {
                        margin-top: 1rem;
                        padding: 1rem;
                        border-radius: 4px;
                        display: none;
                        font-size: 0.9rem;
                    }

                    .success {
                        background: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    }

                    .error {
                        background: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    }

                    .info {
                        background: #cce5ff;
                        color: #004085;
                        border: 1px solid #b8daff;
                    }
                </style>
            </head>

            <body>
                <div class="vapt-test-container">
                    <h1>VAPT Security Test</h1>
                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 1.5rem;">
                        Use this form to test <strong>Rate Limiting</strong> and <strong>Input Validation</strong> features.
                    </p>
                    <form id="vapt-test-form">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required placeholder="John Doe">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required placeholder="john@example.com">
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required placeholder="Test message..."></textarea>
                        </div>
                        <input type="hidden" name="action" value="vapt_form_submit">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(
                                                                        "vapt_form_action",
                                                                    ); ?>">
                        <button type="submit">Submit Test</button>
                    </form>
                    <div id="vapt-result"></div>
                    <p style="text-align: center; margin-top: 1rem;"><a href="<?php echo home_url(); ?>" style="text-decoration: none; font-size: 0.85rem; color: #2271b1;">&larr; Back to Home</a></p>
                </div>

                <script>
                    document.getElementById('vapt-test-form').addEventListener('submit', function(e) {
                        e.preventDefault();
                        var form = this;
                        var resultDiv = document.getElementById('vapt-result');
                        var btn = form.querySelector('button');

                        btn.disabled = true;
                        btn.innerText = 'Submitting...';
                        resultDiv.style.display = 'none';
                        resultDiv.className = '';

                        var formData = new FormData(form);

                        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                // Rate limiting often returns 429 status code
                                if (response.status === 429) {
                                    throw new Error('Too many requests (Rate Limit Exceeded)');
                                }
                                return response.json();
                            })
                            .then(data => {
                                resultDiv.style.display = 'block';
                                if (data.success) {
                                    resultDiv.className = 'success';
                                    resultDiv.innerText = data.data.message;
                                    form.reset();
                                } else {
                                    resultDiv.className = 'error';
                                    resultDiv.innerText = 'Error: ' + (data.data.message || 'Unknown error');
                                }
                            })
                            .catch(error => {
                                resultDiv.style.display = 'block';
                                resultDiv.className = 'error';
                                resultDiv.innerText = error.message;
                            })
                            .finally(() => {
                                btn.disabled = false;
                                btn.innerText = 'Submit Test';
                            });
                    });
                </script>
            </body>

            </html>
        <?php exit();
        }
    }

    /**
     * Register settings, sections, and fields.
     */
    public function register_settings()
    {
        register_setting(
            "vapt_security_options_group",
            "vapt_security_options",
            [$this, "sanitize_options"],
        );

        /* ------------------------------------------------------------------ */
        /* General tab                                                        */
        /* ------------------------------------------------------------------ */
        if (VAPT_FEATURE_WP_CRON_PROTECTION) {
            add_settings_section(
                "vapt_security_general",
                __("General Settings", "vapt-security"),
                function () {
                    if (VAPT_SHOW_FEATURE_INFO) {
                        echo "<p>" .
                            esc_html__(
                                "General settings for the VAPT Security plugin.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                    if (VAPT_SHOW_TEST_URLS) {
                        echo "<p><strong>" .
                            esc_html__("Test URL:", "vapt-security") .
                            '</strong> <a href="' .
                            esc_url(home_url("/")) .
                            '" target="_blank">' .
                            esc_url(home_url("/")) .
                            "</a></p>";
                    }
                },
                "vapt_security_general",
            );
        }

        if (VAPT_FEATURE_WP_CRON_PROTECTION) {
            add_settings_field(
                "enable_cron",
                __("Disable WP‑Cron", "vapt-security"),
                [$this, "render_enable_cron_cb"],
                "vapt_security_general",
                "vapt_security_general",
            );
        }

        /* ------------------------------------------------------------------ */
        /* Rate Limiter tab                                                  */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if (VAPT_FEATURE_RATE_LIMITING) {
            add_settings_section(
                "vapt_security_rate_limiter",
                __("Rate Limiter", "vapt-security"),
                function () {
                    if (VAPT_SHOW_FEATURE_INFO) {
                        echo "<p>" .
                            esc_html__(
                                "Controls the rate limiting for form submissions to prevent abuse.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                    if (VAPT_SHOW_TEST_URLS) {
                        echo "<p><strong>" .
                            esc_html__("Test URL:", "vapt-security") .
                            '</strong> <a href="' .
                            esc_url(home_url("/test-form/")) .
                            '" target="_blank">' .
                            esc_url(home_url("/test-form/")) .
                            "</a></p>";
                        echo '<p class="description">' .
                            esc_html__(
                                "Note: Create a test form on this page to verify rate limiting functionality.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                },
                "vapt_security_rate_limiter",
            );

            add_settings_field(
                "rate_limit_max",
                __("Max Requests per Minute", "vapt-security"),
                [$this, "render_rate_limit_max_cb"],
                "vapt_security_rate_limiter",
                "vapt_security_rate_limiter",
            );

            add_settings_field(
                "rate_limit_window",
                __("Rate Limit Window (minutes)", "vapt-security"),
                [$this, "render_rate_limit_window_cb"],
                "vapt_security_rate_limiter",
                "vapt_security_rate_limiter",
            );
        }

        /* ------------------------------------------------------------------ */
        /* Input Validation tab                                            */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if (VAPT_FEATURE_INPUT_VALIDATION) {
            add_settings_section(
                "vapt_security_validation",
                __("Input Validation", "vapt-security"),
                function () {
                    if (VAPT_SHOW_FEATURE_INFO) {
                        echo "<p>" .
                            esc_html__(
                                "Validates and sanitizes user input to prevent XSS and injection attacks.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                    if (VAPT_SHOW_TEST_URLS) {
                        echo "<p><strong>" .
                            esc_html__("Test URL:", "vapt-security") .
                            '</strong> <a href="' .
                            esc_url(home_url("/test-form/")) .
                            '" target="_blank">' .
                            esc_url(home_url("/test-form/")) .
                            "</a></p>";
                        echo '<p class="description">' .
                            esc_html__(
                                "Note: Create a test form on this page to verify input validation functionality.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                },
                "vapt_security_validation",
            );

            add_settings_field(
                "validation_email",
                __("Require Valid Email?", "vapt-security"),
                [$this, "render_validation_email_cb"],
                "vapt_security_validation",
                "vapt_security_validation",
            );

            add_settings_field(
                "validation_sanitization_level",
                __("Sanitization Level", "vapt-security"),
                [$this, "render_sanitization_level_cb"],
                "vapt_security_validation",
                "vapt_security_validation",
            );
        }

        /* ------------------------------------------------------------------ */
        /* Form Integrations tab                                            */
        /* ------------------------------------------------------------------ */
        // Only register if Input Validation is enabled
        if (VAPT_FEATURE_INPUT_VALIDATION) {
            add_settings_section(
                "vapt_security_integrations",
                __("Form Integrations", "vapt-security"),
                function () {
                    echo "<p>" .
                        esc_html__(
                            "Automatically apply Input Validation to third-party form plugins.",
                            "vapt-security",
                        ) .
                        "</p>";
                    echo '<div class="notice notice-info inline"><p>';
                    echo "<strong>" .
                        esc_html__(
                            "Note for Administrators:",
                            "vapt-security",
                        ) .
                        "</strong><br>";
                    echo esc_html__(
                        'Enabling these integrations will enforcement security checks on form submissions. If "Strict" sanitization is selected, submissions containing HTML or scripts may be blocked or sanitized depending on the hook availability.',
                        "vapt-security",
                    );
                    echo "</p></div>";
                },
                "vapt_security_validation", // Add to Input Validation tab for now, or create new tab if needed. Using same slug as Validation tab to check settings
            );

            // To keep it clean, maybe just append to 'vapt_security_validation' section or create a new one on the same page?
            // The render_settings_page uses `do_settings_sections( 'vapt_security_validation' )` ?
            // Wait, standard WP settings API logic:
            // add_settings_section( $id, $title, $callback, $page )
            // The $page argument links it to do_settings_sections($page).
            // In templates/admin-settings.php (which I haven't seen fully but I can infer), it likely iterates tabs.
            // Let's stick "Form Integrations" into the 'vapt_security_validation' page for simplicity if the UI puts them together,
            // or better, create a subsection in 'vapt_security_validation' PAGE.
            // Actually, looking at the code above, 'vapt_security_validation' is used as the PAGE ID for Input Validation settings.

            add_settings_field(
                "integration_cf7",
                __("Contact Form 7", "vapt-security"),
                [$this, "render_integration_cf7_cb"],
                "vapt_security_validation",
                "vapt_security_integrations",
            );

            add_settings_field(
                "integration_elementor",
                __("Elementor Forms", "vapt-security"),
                [$this, "render_integration_elementor_cb"],
                "vapt_security_validation",
                "vapt_security_integrations",
            );

            add_settings_field(
                "integration_wpforms",
                __("WPForms", "vapt-security"),
                [$this, "render_integration_wpforms_cb"],
                "vapt_security_validation",
                "vapt_security_integrations",
            );

            add_settings_field(
                "integration_gravity",
                __("Gravity Forms", "vapt-security"),
                [$this, "render_integration_gravity_cb"],
                "vapt_security_validation",
                "vapt_security_integrations",
            );
        }

        /* ------------------------------------------------------------------ */
        /* WP‑Cron Protection tab                                         */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if (VAPT_FEATURE_WP_CRON_PROTECTION) {
            add_settings_section(
                "vapt_security_cron",
                __("WP‑Cron Protection", "vapt-security"),
                function () {
                    if (VAPT_SHOW_FEATURE_INFO) {
                        echo "<p>" .
                            esc_html__(
                                "Protects against DoS attacks targeting the WordPress cron system.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                    if (VAPT_SHOW_TEST_URLS) {
                        echo "<p><strong>" .
                            esc_html__("Test URL:", "vapt-security") .
                            '</strong> <a href="' .
                            esc_url(home_url(VAPT_TEST_WP_CRON_URL)) .
                            '" target="_blank">' .
                            esc_url(home_url(VAPT_TEST_WP_CRON_URL)) .
                            "</a></p>";
                        echo '<p class="description">' .
                            esc_html__(
                                "Warning: Visiting this URL may trigger rate limiting if enabled.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                },
                "vapt_security_cron",
            );

            add_settings_field(
                "cron_protection",
                __("Enable Cron Rate Limiting", "vapt-security"),
                [$this, "render_cron_protection_cb"],
                "vapt_security_cron",
                "vapt_security_cron",
            );

            add_settings_field(
                "cron_rate_limit",
                __("Max Cron Requests per Hour", "vapt-security"),
                [$this, "render_cron_rate_limit_cb"],
                "vapt_security_cron",
                "vapt_security_cron",
            );
        }

        /* ------------------------------------------------------------------ */
        /* Security Logging tab                                           */
        /* ------------------------------------------------------------------ */
        // Only register if feature is enabled
        if (VAPT_FEATURE_SECURITY_LOGGING) {
            add_settings_section(
                "vapt_security_logging",
                __("Security Logging", "vapt-security"),
                function () {
                    if (VAPT_SHOW_FEATURE_INFO) {
                        echo "<p>" .
                            esc_html__(
                                "Logs security events for monitoring and analysis.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                    if (VAPT_SHOW_TEST_URLS) {
                        echo "<p><strong>" .
                            esc_html__("Test URL:", "vapt-security") .
                            '</strong> <a href="' .
                            esc_url(home_url("/test-form/")) .
                            '" target="_blank">' .
                            esc_url(home_url("/test-form/")) .
                            "</a></p>";
                        echo '<p class="description">' .
                            esc_html__(
                                "Note: Create a test form on this page to verify logging functionality.",
                                "vapt-security",
                            ) .
                            "</p>";
                    }
                },
                "vapt_security_logging",
            );

            add_settings_field(
                "enable_logging",
                __("Enable Security Logging", "vapt-security"),
                [$this, "render_enable_logging_cb"],
                "vapt_security_logging",
                "vapt_security_logging",
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
    public function sanitize_options($input)
    {
        // Create array
        $sanitized = [];
        $sanitized["enable_cron"] = isset($input["enable_cron"]) ? 1 : 0;
        $sanitized["rate_limit_max"] = isset($input["rate_limit_max"])
            ? absint($input["rate_limit_max"])
            : 10;
        $sanitized["rate_limit_window"] = isset($input["rate_limit_window"])
            ? absint($input["rate_limit_window"])
            : 1;
        $sanitized["validation_email"] = isset($input["validation_email"])
            ? 1
            : 0;
        $sanitized["validation_sanitization_level"] = isset(
            $input["validation_sanitization_level"],
        )
            ? sanitize_text_field($input["validation_sanitization_level"])
            : "standard";
        $sanitized["cron_protection"] = isset($input["cron_protection"])
            ? 1
            : 0;
        $sanitized["cron_rate_limit"] = isset($input["cron_rate_limit"])
            ? absint($input["cron_rate_limit"])
            : 60;
        $sanitized["enable_logging"] = isset($input["enable_logging"]) ? 1 : 0;

        $sanitized["vapt_integration_cf7"] = isset(
            $input["vapt_integration_cf7"],
        )
            ? 1
            : 0;
        $sanitized["vapt_integration_elementor"] = isset(
            $input["vapt_integration_elementor"],
        )
            ? 1
            : 0;
        $sanitized["vapt_integration_wpforms"] = isset(
            $input["vapt_integration_wpforms"],
        )
            ? 1
            : 0;
        $sanitized["vapt_integration_gravity"] = isset(
            $input["vapt_integration_gravity"],
        )
            ? 1
            : 0;

        // Encrypt the data before saving
        $json = json_encode($sanitized);
        return VAPT_Encryption::encrypt($json);
    }

    /* ------------------------------------------------------------------ */
    /* Render callbacks for the settings fields                         */
    /* ------------------------------------------------------------------ */

    public function render_enable_cron_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["enable_cron"])
            ? checked(1, $opts["enable_cron"], false)
            : "";
        ?>
        <label>
            <input type="checkbox" name="vapt_security_options[enable_cron]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Disable WP‑Cron (recommended for production sites)",
                "vapt-security",
            ); ?>
        </label>
        <p class="description"><?php esc_html_e(
                                    "Prevents abuse of the WordPress cron system by disabling the default behavior and requiring manual cron setup.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    public function render_rate_limit_max_cb()
    {
        $opts = $this->get_config();
        $val = isset($opts["rate_limit_max"])
            ? absint($opts["rate_limit_max"])
            : 10;
    ?>
        <input type="number" name="vapt_security_options[rate_limit_max]" value="<?php echo esc_attr(
                                                                                        $val,
                                                                                    ); ?>" min="1" max="1000" />
        <p class="description"><?php esc_html_e(
                                    "Maximum form submissions allowed per minute per IP address.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    public function render_rate_limit_window_cb()
    {
        $opts = $this->get_config();
        $val = isset($opts["rate_limit_window"])
            ? absint($opts["rate_limit_window"])
            : 1;
    ?>
        <input type="number" name="vapt_security_options[rate_limit_window]" value="<?php echo esc_attr(
                                                                                        $val,
                                                                                    ); ?>" min="1" max="60" />
        <p class="description"><?php esc_html_e(
                                    "Time window in minutes for rate limiting.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    public function render_validation_email_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["validation_email"])
            ? checked(1, $opts["validation_email"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[validation_email]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Require a valid email address for all forms",
                "vapt-security",
            ); ?>
        </label>
        <p class="description"><?php esc_html_e(
                                    "Enforces email validation on all form submissions to prevent spam and invalid data.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    public function render_sanitization_level_cb()
    {
        $opts = $this->get_config();
        $val = isset($opts["validation_sanitization_level"])
            ? sanitize_text_field($opts["validation_sanitization_level"])
            : "standard";
    ?>
        <select name="vapt_security_options[validation_sanitization_level]">
            <option value="basic" <?php selected(
                                        $val,
                                        "basic",
                                    ); ?>><?php esc_html_e("Basic", "vapt-security"); ?></option>
            <option value="standard" <?php selected(
                                            $val,
                                            "standard",
                                        ); ?>><?php esc_html_e("Standard", "vapt-security"); ?></option>
            <option value="strict" <?php selected(
                                        $val,
                                        "strict",
                                    ); ?>><?php esc_html_e("Strict", "vapt-security"); ?></option>
        </select>
        <p class="description"><?php esc_html_e(
                                    "Higher levels provide more security but may block legitimate input.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    /* ------------------------------------------------------------------ */
    /* Integration Callbacks                                            */
    /* ------------------------------------------------------------------ */

    public function render_integration_cf7_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["vapt_integration_cf7"])
            ? checked(1, $opts["vapt_integration_cf7"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[vapt_integration_cf7]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Enable validation for Contact Form 7",
                "vapt-security",
            ); ?>
        </label>
    <?php
    }

    public function render_integration_elementor_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["vapt_integration_elementor"])
            ? checked(1, $opts["vapt_integration_elementor"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[vapt_integration_elementor]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Enable validation for Elementor Forms",
                "vapt-security",
            ); ?>
        </label>
    <?php
    }

    public function render_integration_wpforms_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["vapt_integration_wpforms"])
            ? checked(1, $opts["vapt_integration_wpforms"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[vapt_integration_wpforms]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Enable validation for WPForms",
                "vapt-security",
            ); ?>
        </label>
    <?php
    }

    public function render_integration_gravity_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["vapt_integration_gravity"])
            ? checked(1, $opts["vapt_integration_gravity"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[vapt_integration_gravity]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Enable validation for Gravity Forms",
                "vapt-security",
            ); ?>
        </label>
    <?php
    }

    public function render_cron_protection_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["cron_protection"])
            ? checked(1, $opts["cron_protection"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[cron_protection]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Enable rate‑limiting on wp-cron endpoints",
                "vapt-security",
            ); ?>
        </label>
        <p class="description"><?php esc_html_e(
                                    "Protects against DoS attacks by limiting requests to wp-cron.php.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    public function render_cron_rate_limit_cb()
    {
        $opts = $this->get_config();
        $val = isset($opts["cron_rate_limit"])
            ? absint($opts["cron_rate_limit"])
            : 60;
    ?>
        <input type="number" name="vapt_security_options[cron_rate_limit]" value="<?php echo esc_attr(
                                                                                        $val,
                                                                                    ); ?>" min="1" max="1000" />
        <p class="description"><?php esc_html_e(
                                    "Maximum cron requests allowed per hour.",
                                    "vapt-security",
                                ); ?></p>
    <?php
    }

    public function render_enable_logging_cb()
    {
        $opts = $this->get_config();
        $checked = isset($opts["enable_logging"])
            ? checked(1, $opts["enable_logging"], false)
            : "";
    ?>
        <label>
            <input type="checkbox" name="vapt_security_options[enable_logging]" value="1" <?php echo $checked; ?> />
            <?php esc_html_e(
                "Enable security event logging",
                "vapt-security",
            ); ?>
        </label>
        <p class="description"><?php esc_html_e(
                                    "Log security events for monitoring and analysis.",
                                    "vapt-security",
                                ); ?></p>
<?php
    }

    /**
     * Initialize security logging
     */
    public function initialize_security_logging()
    {
        // Logging is initialized on demand when needed
    }

    /**
     * Handle plugin activation
     */
    public function activate_plugin()
    {
        // Schedule cleanup event
        if (!wp_next_scheduled("vapt_cleanup_event")) {
            wp_schedule_event(time(), "hourly", "vapt_cleanup_event");
        }

        // Enforce lock on activation
        $this->enforce_domain_lock(true);
    }

    /**
     * Activate the license.
     */
    public function activate_license()
    {
        VAPT_License::activate_license();
    }

    /**
     * Handle plugin deactivation
     */
    public function deactivate_plugin()
    {
        // Clear scheduled events
        wp_clear_scheduled_hook("vapt_cleanup_event");
    }

    /**
     * Cleanup old data
     */
    public function cleanup_old_data()
    {
        $rate_limiter = new VAPT_Rate_Limiter();
        $rate_limiter->clean_old_entries();

        $logger = new VAPT_Security_Logger();
        $logger->cleanup_old_logs();
    }

    /* ------------------------------------------------------------------ */
    /* AJAX form handling                                               */
    /* ------------------------------------------------------------------ */

    public function handle_form_submission()
    {
        // Only process if feature is enabled
        if (!VAPT_FEATURE_RATE_LIMITING && !VAPT_FEATURE_INPUT_VALIDATION) {
            wp_send_json_error(
                [
                    "message" => __(
                        "Form processing is disabled.",
                        "vapt-security",
                    ),
                ],
                400,
            );
            return;
        }

        // Log the form submission attempt if logging is enabled
        if (VAPT_FEATURE_SECURITY_LOGGING) {
            $logger = new VAPT_Security_Logger();
            $rate_limiter = new VAPT_Rate_Limiter();
            $logger->log_event("form_submission_attempt", [
                "ip" => $rate_limiter->get_current_ip(),
                "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? "",
            ]);
        }

        // 1. Rate limiting (if enabled)
        if (VAPT_FEATURE_RATE_LIMITING) {
            $rate_limiter = new VAPT_Rate_Limiter();

            // Check if IP is whitelisted
            $current_ip = $rate_limiter->get_current_ip();
            if (
                !in_array($current_ip, VAPT_WHITELISTED_IPS) &&
                !$rate_limiter->allow_request()
            ) {
                // Log the blocked request if logging is enabled
                if (VAPT_FEATURE_SECURITY_LOGGING) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event("blocked_form_submission", [
                        "ip" => $current_ip,
                        "reason" => "rate_limit_exceeded",
                    ]);
                }

                wp_send_json_error(
                    [
                        "message" => __(
                            VAPT_RATE_LIMIT_MESSAGE,
                            "vapt-security",
                        ),
                    ],
                    429,
                );
            }
        }

        // 2. Nonce verification
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce(sanitize_key($_POST["nonce"]), "vapt_form_action")
        ) {
            // Log the invalid nonce if logging is enabled
            if (VAPT_FEATURE_SECURITY_LOGGING) {
                $logger = new VAPT_Security_Logger();
                $logger->log_event("invalid_nonce", [
                    "ip" => $rate_limiter->get_current_ip(),
                ]);
            }

            wp_send_json_error(
                [
                    "message" => __(
                        VAPT_INVALID_NONCE_MESSAGE,
                        "vapt-security",
                    ),
                ],
                400,
            );
        }

        // 3. Input validation (if enabled)
        if (VAPT_FEATURE_INPUT_VALIDATION) {
            $validator = new VAPT_Input_Validator();
            $schema = [
                "name" => ["required" => true, "type" => "string", "max" => 50],
                "email" => [
                    "required" => true,
                    "type" => "email",
                    "max" => 100,
                ],
                "message" => [
                    "required" => true,
                    "type" => "string",
                    "max" => 500,
                ],
                "captcha" => [
                    "required" => false,
                    "type" => "string",
                    "max" => 10,
                ],
            ];
            $data = $validator->validate($_POST, $schema);

            if (is_wp_error($data)) {
                // Log the validation error if logging is enabled
                if (VAPT_FEATURE_SECURITY_LOGGING) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event("validation_error", [
                        "ip" => $rate_limiter->get_current_ip(),
                        "error" => $data->get_error_message(),
                    ]);
                }

                wp_send_json_error(
                    ["message" => $data->get_error_message()],
                    400,
                );
            }
        } else {
            // Basic sanitization if validation is disabled
            $data = [
                "name" => sanitize_text_field($_POST["name"] ?? ""),
                "email" => sanitize_email($_POST["email"] ?? ""),
                "message" => sanitize_textarea_field($_POST["message"] ?? ""),
                "captcha" => sanitize_text_field($_POST["captcha"] ?? ""),
            ];
        }

        // 4. Optional CAPTCHA check
        if (!empty($data["captcha"])) {
            $captcha = new VAPT_Captcha();
            if (!$captcha->verify($data["captcha"])) {
                // Log the failed CAPTCHA if logging is enabled
                if (VAPT_FEATURE_SECURITY_LOGGING) {
                    $logger = new VAPT_Security_Logger();
                    $logger->log_event("failed_captcha", [
                        "ip" => $rate_limiter->get_current_ip(),
                    ]);
                }

                wp_send_json_error(
                    [
                        "message" => __(
                            "CAPTCHA verification failed.",
                            "vapt-security",
                        ),
                    ],
                    400,
                );
            }
        }

        // 5. Process the form (e.g., send an email)
        $to = get_option("admin_email");
        $subject = sprintf(
            __("New message from %s", "vapt-security"),
            $data["name"],
        );
        $body = sprintf(
            __("Name: %s\nEmail: %s\n\nMessage:\n%s", "vapt-security"),
            $data["name"],
            $data["email"],
            $data["message"],
        );

        wp_mail($to, $subject, $body);

        // Log successful submission if logging is enabled
        if (VAPT_FEATURE_SECURITY_LOGGING) {
            $logger = new VAPT_Security_Logger();
            $logger->log_event("successful_form_submission", [
                "ip" => $rate_limiter->get_current_ip(),
            ]);
        }

        wp_send_json_success([
            "message" => __(
                "Your message was sent successfully.",
                "vapt-security",
            ),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* OTP Auth                                                         */
    /* ------------------------------------------------------------------ */

    public function handle_send_otp()
    {
        $user = wp_get_current_user();
        // Strict Check
        $is_local = $this->is_local_environment();
        if (!$user->exists() || $user->user_login !== "tanmalik786") {
            wp_send_json_error(
                ["message" => "Unauthorized: Invalid Username"],
                403,
            );
        }
        if (!$is_local && $user->user_email !== "tanmalik786@gmail.com") {
            wp_send_json_error(
                ["message" => "Unauthorized: Invalid Email"],
                403,
            );
        }

        $res = VAPT_OTP::send_otp($user->ID);
        if (is_wp_error($res)) {
            wp_send_json_error(["message" => $res->get_error_message()]);
        }

        wp_send_json_success([
            "message" => __("OTP sent to your email.", "vapt-security"),
        ]);
    }

    public function handle_verify_otp()
    {
        $user = wp_get_current_user();
        // Strict Check
        $is_local = $this->is_local_environment();
        if (!$user->exists() || $user->user_login !== "tanmalik786") {
            wp_send_json_error(
                ["message" => "Unauthorized: Invalid Username"],
                403,
            );
        }
        if (!$is_local && $user->user_email !== "tanmalik786@gmail.com") {
            wp_send_json_error(
                ["message" => "Unauthorized: Invalid Email"],
                403,
            );
        }

        $otp = sanitize_text_field($_POST["otp"] ?? "");
        $res = VAPT_OTP::verify_otp($user->ID, $otp);

        if (is_wp_error($res)) {
            wp_send_json_error(["message" => $res->get_error_message()]);
        }

        // Set transient for 5 minutes
        set_transient("vapt_auth_" . $user->ID, true, 300);

        wp_send_json_success([
            "message" => __("OTP Verified.", "vapt-security"),
        ]);
    }

    public function handle_update_license()
    {
        if (!$this->verify_superadmin_access()) {
            wp_send_json_error(["message" => "Unauthorized"], 403);
        }

        $type = sanitize_text_field($_POST["type"] ?? "standard");
        $auto_renew = isset($_POST["auto_renew"])
            ? (int) $_POST["auto_renew"]
            : null;

        // Check for Type Change
        $current_license = VAPT_License::get_license();
        $expires = null;

        // Developer Constraint: Auto Renew must be disabled
        if ($type === "developer") {
            $auto_renew = 0;
        }

        if (
            $current_license &&
            isset($current_license["type"]) &&
            $current_license["type"] !== $type
        ) {
            // Type Changed! Recalculate expiry from NOW.
            if ($type === "standard") {
                $expires = time() + 30 * DAY_IN_SECONDS;
            } elseif ($type === "pro") {
                $expires = time() + 365 * DAY_IN_SECONDS - DAY_IN_SECONDS;
            } else {
                $expires = 0; // Developer
            }
        }

        // Check if NO changes were made
        if (
            $current_license &&
            ($current_license["type"] ?? "standard") === $type &&
            ($current_license["auto_renew"] ?? 0) == $auto_renew
        ) {
            $license = VAPT_License::get_license();
            $formatted = $license["expires"]
                ? date_i18n(get_option("date_format"), $license["expires"])
                : __("Never", "vapt-security");

            wp_send_json_success([
                "message" => __(
                    "License updated. No changes made.",
                    "vapt-security",
                ),
                "expires_formatted" => $formatted,
            ]);
            return;
        }

        if (VAPT_License::update_license($type, $expires, $auto_renew)) {
            $license = VAPT_License::get_license();
            $formatted = $license["expires"]
                ? date_i18n(get_option("date_format"), $license["expires"])
                : __("Never", "vapt-security");
            wp_send_json_success([
                "message" => __("License updated.", "vapt-security"),
                "expires_formatted" => $formatted,
            ]);
        } else {
            wp_send_json_error([
                "message" => __("Failed to update license.", "vapt-security"),
            ]);
        }
    }

    public function handle_renew_license()
    {
        if (!$this->verify_superadmin_access()) {
            wp_send_json_error(["message" => "Unauthorized"], 403);
        }

        if (VAPT_License::renew()) {
            $license = VAPT_License::get_license();
            $formatted = $license["expires"]
                ? date_i18n(get_option("date_format"), $license["expires"])
                : __("Never", "vapt-security");
            wp_send_json_success([
                "message" => __("License renewed.", "vapt-security"),
                "expires_formatted" => $formatted,
            ]);
        } else {
            wp_send_json_error([
                "message" => __("Failed to renew license.", "vapt-security"),
            ]);
        }
    }

    public function handle_save_domain_features()
    {
        if (!$this->verify_superadmin_access()) {
            wp_send_json_error(["message" => "Unauthorized"], 403);
        }

        parse_str($_POST["data"], $data);
        $features = $data["features"] ?? [];

        // Debug Logging for Feature Saving Issue
        error_log("VAPT Debug: Handle Save Domain Features");
        error_log(
            "VAPT Debug: Raw POST data: " . print_r($_POST["data"], true),
        );
        error_log("VAPT Debug: Parsed features: " . print_r($features, true));

        if (VAPT_Features::update_features($features)) {
            wp_send_json_success(["message" => "Features saved."]);
        } else {
            // Maybe no change?
            wp_send_json_success(["message" => "Features saved (no change)."]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Locked Config Features                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Generate Domain Locked Configuration File
     */
    public function handle_generate_locked_config()
    {
        check_ajax_referer("vapt_locked_config", "nonce");

        // Superadmin Check
        if (!$this->verify_superadmin_access()) {
            wp_send_json_error(["message" => "Unauthorized"], 403);
        }

        $domain_pattern = sanitize_text_field($_POST["domain"] ?? "");
        if (empty($domain_pattern)) {
            wp_send_json_error([
                "message" => __("Domain pattern is required.", "vapt-security"),
            ]);
        }

        $include_settings = !empty($_POST["include_settings"]);
        $settings = [];

        if ($include_settings) {
            $settings = $this->get_config();
        }

        $payload = [
            "domain_pattern" => $domain_pattern,
            "settings" => $settings,
            "features" => VAPT_Features::get_active_features(),
            "version" => defined("VAPT_VERSION") ? VAPT_VERSION : "unknown",
            "generated_at" => time(),
            "generated_by" => "superadmin",
        ];

        // Create PHP file content
        $json_payload = json_encode($payload);

        // Generate Integrity Signature to prevent tampering
        // We use a fixed salt for now since this must be verifiable across different installations
        $salt = "VAPT_LOCKED_CONFIG_INTEGRITY_SALT_v2";
        $signature = hash_hmac("sha256", $json_payload, $salt);

        // We double encode or create strict php file
        $file_content =
            "<?php
/**
 * VAPT Security - Domain Locked Configuration
 *
 * This file is automatically generated by VAPT Security.
 * It is locked to the domain pattern: {$domain_pattern}
 *
 * DO NOT EDIT THIS FILE MANUALLY.
 * Integrity Check: {$signature}
 */

$vapt_locked_config_data = '" .
            addslashes($json_payload) .
            "';
$vapt_locked_config_sig = '{$signature}';
";

        if (
            file_put_contents(
                plugin_dir_path(__FILE__) . "vapt-locked-config.php",
                $file_content,
            )
        ) {
            if ($this->is_local_environment()) {
                $this->import_locked_config($payload);
            }

            wp_send_json_success([
                "message" => __(
                    "Configuration generated and saved to server.",
                    "vapt-security",
                ),
                "filename" => "vapt-locked-config.php",
            ]);
        } else {
            wp_send_json_error([
                "message" => __(
                    "Failed to write configuration file to server.",
                    "vapt-security",
                ),
            ]);
        }
    }

    /**
     * Generate Client Zip (Domain Specific)
     */
    public function handle_generate_client_zip()
    {
        check_ajax_referer("vapt_locked_config", "nonce");

        if (!$this->verify_superadmin_access()) {
            wp_send_json_error(["message" => "Unauthorized"], 403);
        }

        if (!class_exists("ZipArchive")) {
            wp_send_json_error([
                "message" => __(
                    "ZipArchive PHP extension is missing.",
                    "vapt-security",
                ),
            ]);
        }

        // 1. Generate the Config Content (Reusing logic)
        $domain_pattern = sanitize_text_field($_POST["domain"] ?? "");
        if (empty($domain_pattern)) {
            wp_send_json_error([
                "message" => __("Domain pattern is required.", "vapt-security"),
            ]);
        }
        $include_settings = !empty($_POST["include_settings"]);
        $settings = $include_settings ? $this->get_config() : [];

        $payload = [
            "domain_pattern" => $domain_pattern,
            "settings" => $settings,
            "generated_at" => time(),
            "generated_by" => "superadmin",
        ];
        $json_payload = json_encode($payload);
        $salt = "VAPT_LOCKED_CONFIG_INTEGRITY_SALT_v2";
        $signature = hash_hmac("sha256", $json_payload, $salt);

        $config_content =
            "<?php
/**
 * VAPT Security - Domain Locked Configuration
 *
 * This file is automatically generated by VAPT Security.
 * It is locked to the domain pattern: {$domain_pattern}
 *
 * DO NOT EDIT THIS FILE MANUALLY.
 * Integrity Check: {$signature}
 */

$vapt_locked_config_data = '" .
            addslashes($json_payload) .
            "';
$vapt_locked_config_sig = '{$signature}';
";

        // 2. Build Zip
        $zip_file = tempnam(sys_get_temp_dir(), "vapt_client_");
        $zip = new ZipArchive();
        if (
            $zip->open(
                $zip_file,
                ZipArchive::CREATE | ZipArchive::OVERWRITE,
            ) !== true
        ) {
            wp_send_json_error([
                "message" => __(
                    "Could not create temp zip file.",
                    "vapt-security",
                ),
            ]);
        }

        // Folder name inside zip
        $folder = "vapt-security";
        $zip->addEmptyDir($folder);

        // Add Config File
        $zip->addFromString(
            $folder . "/vapt-locked-config.php",
            $config_content,
        );

        // Add Plugin Files
        $plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $plugin_path,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $exclude_list = [
            ".git",
            ".gitignore",
            ".github",
            "ARCHITECTURE.md",
            "CHANGELOG.md",
            "DOCUMENTATION.md",
            "FEATURES.md",
            "VERSION_CONTROL.md",
            "Project Layout.me",
            "README.md",
            "SUPERADMIN_GUIDE.md",
            "composer.json",
            "composer.lock",
            "package.json",
            "package-lock.json",
            "prompt.txt",
            "test-config.php",
            "test-vapt-features.php",
            "tests",
            "bin",
            "node_modules",
            "VAPTSecurity Initial.zip",
            "VAPTSecurity v105.zip",
        ];

        foreach ($files as $name => $file) {
            // Check for valid file info
            if (!$file->isFile()) {
                continue;
            }

            $relative_path = substr(
                $file->getPathname(),
                strlen($plugin_path) + 1,
            );
            // Normalize slashes
            $relative_path = str_replace("\\", "/", $relative_path);

            // Check Exclusions
            $skip = false;
            foreach ($exclude_list as $exclude) {
                if (
                    strpos($relative_path, $exclude) === 0 ||
                    strpos($relative_path, "/" . $exclude) !== false
                ) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            // Don't include existing locked config if one somehow exists in dev
            if ($relative_path === "vapt-locked-config.php") {
                continue;
            }

            $zip->addFile($file->getRealPath(), $folder . "/" . $relative_path);
        }

        $zip->close();

        // 3. Return Base64
        if (file_exists($zip_file)) {
            $data = file_get_contents($zip_file);
            unlink($zip_file);

            // Sanitize domain for filename
            // User requested: *.example.com -> vapt-security-example.zip

            // Sanitize domain for filename
            // User requested: *.example.com -> vapt-security-example.zip
            // User requested: staging.client-site.co.uk -> vapt-security-client-site.zip

            // 1. Remove wildcard and leading dots
            $clean_domain = str_replace("*", "", $domain_pattern);
            $clean_domain = ltrim($clean_domain, ".");

            $parts = explode(".", $clean_domain);
            $count = count($parts);

            if ($count > 1) {
                // Heuristic for Multi-part TLDs (e.g., .co.uk, .com.au)
                // If the last part is 2 chars (ccTLD) and the one before is a common SLD (co, com, org, etc.)
                $last = $parts[$count - 1];
                $second_last = $parts[$count - 2];

                if (
                    strlen($last) === 2 &&
                    in_array($second_last, [
                        "co",
                        "com",
                        "org",
                        "gov",
                        "net",
                        "edu",
                        "ac",
                    ])
                ) {
                    array_pop($parts); // Remove .uk
                    array_pop($parts); // Remove .co
                } else {
                    array_pop($parts); // Remove .com
                }
            }

            // 2. "Skip sub-domains" -> Take the last remaining part
            // e.g., [staging, client-site] -> client-site
            $short_name = end($parts);

            $safe_name = preg_replace("/[^a-zA-Z0-9\-]/", "", $short_name);
            if (empty($safe_name)) {
                $safe_name = "client";
            }

            // Determine if this is a "locked" configuration based on the presence of settings
            $is_locked = !empty($settings);

            if ($is_locked) {
                $filename =
                    "vapt-security-locked-" .
                    sanitize_title($domain_pattern) .
                    "-" .
                    VAPT_VERSION .
                    ".zip";
            } else {
                $filename = "vapt-security-client-" . VAPT_VERSION . ".zip";
            }

            wp_send_json_success([
                "message" => __("Zip built successfully.", "vapt-security"),
                "filename" => $filename,
                "base64" => base64_encode($data),
            ]);
        } else {
            wp_send_json_error([
                "message" => __("Zip generation failed.", "vapt-security"),
            ]);
        }
    }

    /**
     * Enforce Domain Locked Configuration
     *
     * @param bool $is_activation Whether this is running during plugin activation.
     */
    public function enforce_domain_lock($is_activation = false)
    {
        $config_file = plugin_dir_path(__FILE__) . "vapt-locked-config.php";

        if (!file_exists($config_file)) {
            return;
        }

        // Avoid 'include' to prevent file locking on Windows during deletion/rename
        $file_content = file_get_contents($config_file);

        $vapt_locked_config_data = null;
        $vapt_locked_config_sig = null;

        // Extract Data
        if (
            preg_match(
                '/$vapt_locked_config_data\s*=\s*\'(.*?)\';/s',
                $file_content,
                $matches,
            )
        ) {
            $vapt_locked_config_data = stripslashes($matches[1]);
        }

        // Extract Signature
        if (
            preg_match(
                '/$vapt_locked_config_sig\s*=\s*\'([a-f0-9]+)\';/',
                $file_content,
                $matches,
            )
        ) {
            $vapt_locked_config_sig = $matches[1];
        }

        if (!$vapt_locked_config_data || !$vapt_locked_config_sig) {
            return; // Invalid format
        }

        // Verify Integrity (Tamper Protection)
        $salt = "VAPT_LOCKED_CONFIG_INTEGRITY_SALT_v2";
        $check_sig = hash_hmac("sha256", $vapt_locked_config_data, $salt);

        if (
            !isset($vapt_locked_config_sig) ||
            !hash_equals($check_sig, $vapt_locked_config_sig)
        ) {
            // Tampering detected!
            error_log(
                "VAPT Security: Locked configuration file integrity check failed. File may have been tampered with.",
            );
            return;
        }

        $data = json_decode($vapt_locked_config_data, true);
        if (!$data || empty($data["domain_pattern"])) {
            return; // Invalid data
        }

        // Domain Validation
        $current_host = $_SERVER["HTTP_HOST"] ?? "";
        $pattern = $data["domain_pattern"];

        // Convert wildcard * to Regex .*
        // Runtime Normalization: Always treat as *pattern*
        $regex =
            "/^.*" .
            str_replace("\*", ".*", preg_quote($pattern, "/")) .
            '.*$/';

        if (!preg_match($regex, $current_host)) {
            // MISMATCH

            // Check if Local Environment - If so, warn but allow (Bypass)
            if ($this->is_local_environment()) {
                if (is_admin() && !$is_activation) {
                    add_action("admin_notices", function () use (
                        $pattern,
                        $current_host,
                    ) {
                        echo '<div class="notice notice-warning is-dismissible"><p>';
                        printf(
                            esc_html__(
                                'VAPT Security Warning: This build is locked to domain pattern %1$s but is running on %2$s. Allowed for Local Development.',
                                "vapt-security",
                            ),
                            "<strong>" . esc_html($pattern) . "</strong>",
                            "<strong>" . esc_html($current_host) . "</strong>",
                        );
                        echo "</p></div>";
                    });
                }
                // Return early to allow execution, but DO NOT import/rename (keep the lock file intact for testing)

                // FIX: Check if we need to sync build info anyway (so the UI updates)
                $current_build = self::get_build_info();
                $file_time = $data["generated_at"] ?? 0;
                $stored_time = $current_build["generated_at"] ?? 0;

                if ($file_time > $stored_time) {
                    // Sync settings but don't delete file
                    $this->import_locked_config($data);

                    if (is_admin() && !$is_activation) {
                        add_action("admin_notices", function () {
                            echo '<div class="notice notice-info is-dismissible"><p>' .
                                esc_html__(
                                    "VAPT Security Info: Settings optimized/synced from locked configuration (Local Mode).",
                                    "vapt-security",
                                ) .
                                "</p></div>";
                        });
                    }
                }

                return;
            }

            // NOT Local? BLOCK EXECUTION
            wp_mail(
                "tanmalik786@gmail.com",
                "VAPT Security Violation: Domain Mismatch",
                sprintf(
                    "A locked build was attempted to be used on an unauthorized domain.\n\nLocked Pattern: %s\nAttempted Host: %s\nIP: %s",
                    $pattern,
                    $current_host,
                    $_SERVER["REMOTE_ADDR"] ?? "Unknown",
                ),
            );

            // 2. Deactivate Plugin (if not already activating, but we want to ensure it stays off)
            // If strictly activating, this might be redundant as we die, but good for cleanup.
            if (!function_exists("deactivate_plugins")) {
                require_once ABSPATH . "wp-admin/includes/plugin.php";
            }
            deactivate_plugins(plugin_basename(__FILE__));

            // 3. Die with Message
            $msg = sprintf(
                "<h1>Security Violation</h1><p>This build of <strong>VAPT Security</strong> is locked to the domain pattern: <code>%s</code>.</p><p>You are attempting to use it on: <code>%s</code>.</p><p>Please contact the developer at <strong>tanmalik786@gmail.com</strong> to obtain a license for this domain.</p>",
                esc_html($pattern),
                esc_html($current_host),
            );

            wp_die($msg, "Domain Lock Violation", ["response" => 403]);
        }

        // MATCH Found!

        // Logic for Import
        $this->import_locked_config($data);

        // Rename file to prevent re-import (and re-execution of this heavy check)
        @rename($config_file, $config_file . ".imported");

        // Customize User Guide
        $guide_file = plugin_dir_path(__FILE__) . "USER_GUIDE.md";
        if (file_exists($guide_file) && is_writable($guide_file)) {
            $guide_content = file_get_contents($guide_file);
            $updated_content = str_replace(
                "your-domain.com",
                $current_host,
                $guide_content,
            );
            if ($guide_content !== $updated_content) {
                file_put_contents($guide_file, $updated_content);
            }
        }

        // Admin Notice
        if (is_admin() && !$is_activation) {
            add_action("admin_notices", function () use ($pattern) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                printf(
                    esc_html__(
                        "VAPT Security: Configuration successfully imported from locked file for domain pattern %s.",
                        "vapt-security",
                    ),
                    "<strong>" . esc_html($pattern) . "</strong>",
                );
                echo "</p></div>";
            });
        }
    }

    /**
     * Helper: Import parameters from locked config data.
     *
     * @param array $data Decoded configuration data.
     */
    private function import_locked_config($data)
    {
        // 1. Update Settings
        if (isset($data["settings"]) && is_array($data["settings"])) {
            // Encrypt and save options
            $json = json_encode($data["settings"]);
            $encrypted = VAPT_Encryption::encrypt($json);
            update_option("vapt_security_options", $encrypted);
        }

        // 2. Update Domain Features
        if (isset($data["features"]) && is_array($data["features"])) {
            update_option("vapt_domain_features", $data["features"]);
        }

        // 3. Update Build Info
        update_option("vapt_build_info", [
            "generated_at" => $data["generated_at"] ?? 0,
            "domain_pattern" => $data["domain_pattern"] ?? "",
            "version" => $data["version"] ?? "unknown",
            "imported_at" => time(),
        ]);
    }

    /**
     * AJAX Handler: Re-import Configuration (Force)
     */
    public function handle_reimport_config()
    {
        if (!$this->verify_superadmin_access()) {
            wp_send_json_error(["message" => "Unauthorized"], 403);
        }

        // Look for file (imported or raw)
        $config_file = plugin_dir_path(__FILE__) . "vapt-locked-config.php";
        if (!file_exists($config_file)) {
            $config_file .= ".imported";
        }

        if (!file_exists($config_file)) {
            wp_send_json_error([
                "message" => __(
                    "Configuration file not found.",
                    "vapt-security",
                ),
            ]);
        }

        $file_content = file_get_contents($config_file);

        // Extract Data
        $vapt_locked_config_data = null;
        $vapt_locked_config_sig = null;

        if (
            preg_match(
                '/$vapt_locked_config_data\s*=\s*\'(.*?)\';/s',
                $file_content,
                $matches,
            )
        ) {
            $vapt_locked_config_data = stripslashes($matches[1]);
        }
        if (
            preg_match(
                '/$vapt_locked_config_sig\s*=\s*\'([a-f0-9]+)\';/',
                $file_content,
                $matches,
            )
        ) {
            $vapt_locked_config_sig = $matches[1];
        }

        if (!$vapt_locked_config_data || !$vapt_locked_config_sig) {
            wp_send_json_error([
                "message" => __(
                    "Invalid configuration file format.",
                    "vapt-security",
                ),
            ]);
        }

        // Verify Integrity
        $salt = "VAPT_LOCKED_CONFIG_INTEGRITY_SALT_v2";
        $check_sig = hash_hmac("sha256", $vapt_locked_config_data, $salt);

        if (!hash_equals($check_sig, $vapt_locked_config_sig)) {
            wp_send_json_error([
                "message" => __("Integrity check failed.", "vapt-security"),
            ]);
        }

        $data = json_decode($vapt_locked_config_data, true);
        if (!$data) {
            wp_send_json_error([
                "message" => __(
                    "Failed to decode configuration data.",
                    "vapt-security",
                ),
            ]);
        }

        // Perform Import (Force bypasses domain check)
        $this->import_locked_config($data);

        // If it was the raw file, rename it to .imported
        if (substr($config_file, -9) !== ".imported") {
            @rename($config_file, $config_file . ".imported");
        }

        wp_send_json_success([
            "message" => __(
                "Configuration re-imported successfully.",
                "vapt-security",
            ),
        ]);
    }

    /**
     * Get Build Info
     */
    public static function get_build_info()
    {
        return get_option("vapt_build_info", []);
    }
}

/* Kick it off. */
VAPT_Security::instance();
