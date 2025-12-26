<?php

/**
 * Settings page markup with modern horizontal tabs.
 *
 * @package VAPT_Security
 */

if (!defined("ABSPATH")) {
    exit();
}

// Check for Superadmin
// Used for display logic or sensitive field shows if needed later.
$is_superadmin = VAPT_Security::is_superadmin();
?>
<div class="wrap">
    <h1><?php esc_html_e("VAPT Security Settings", "vapt-security"); ?></h1>
    <?php
    $build_info = VAPT_Security::get_build_info();
    // Only show if Superadmin
    if ($is_superadmin && !empty($build_info["domain_pattern"])) {
        $build_ver = $build_info["generated_at"]
            ? date_i18n(get_option("date_format") . ' ' . get_option("time_format"), $build_info["generated_at"])
            : "";
        echo '<div class="notice notice-info inline" style="margin: 5px 0 20px 0;"><p>';
        echo "<strong>" .
            esc_html__("Active Build:", "vapt-security") .
            "</strong> " .
            esc_html($build_info["domain_pattern"]);
        if ($build_ver) {
            echo ' <span style="color:#666;">(' .
                esc_html__("Generated:", "vapt-security") .
                " " .
                esc_html($build_ver) .
                ")</span>";
        }
        echo "</p></div>";
    }
    ?>

    <form method="post" action="options.php" id="vapt-settings-form">
        <?php
        // Output security fields.
        settings_fields("vapt_security_options_group");


        // Calculate active tabs to determine layout
        $active_tabs = 1; // Start with 1 for Statistics

        // General Tab
        if (defined("VAPT_FEATURE_WP_CRON_PROTECTION") && VAPT_FEATURE_WP_CRON_PROTECTION) {
            $active_tabs++;
        }

        // Rate Limiter
        if (
            VAPT_Features::is_enabled("rate_limiting") &&
            defined("VAPT_FEATURE_RATE_LIMITING") &&
            VAPT_FEATURE_RATE_LIMITING
        ) {
            $active_tabs++;
        }

        // Input Validation
        if (
            VAPT_Features::is_enabled("input_validation") &&
            defined("VAPT_FEATURE_INPUT_VALIDATION") &&
            VAPT_FEATURE_INPUT_VALIDATION
        ) {
            $active_tabs++;
        }



        // Security Logging
        if (
            VAPT_Features::is_enabled("security_logging") &&
            defined("VAPT_FEATURE_SECURITY_LOGGING") &&
            VAPT_FEATURE_SECURITY_LOGGING
        ) {
            $active_tabs++;
        }

        // Hardening Features (Grouped)
        $hardening_features = [
            'disable_xmlrpc' => [
                'label' => __('XML-RPC Protection', 'vapt-security'),
                'desc' => __('Disables the XML-RPC API, which is often used for DDoS and brute force attacks.', 'vapt-security'),
                'note' => sprintf(__('Test: Visit %s. You should see "Forbidden" or a 403 error.', 'vapt-security'), '<a href="' . esc_url(site_url('/xmlrpc.php')) . '" target="_blank"><code>' . esc_html(site_url('/xmlrpc.php')) . '</code></a>'),
                'icon' => 'dashicons-shield',
            ],
            'disable_user_enum' => [
                'label' => __('User Enumeration Protection', 'vapt-security'),
                'desc' => __('Prevents attackers from scanning for valid usernames using author archives.', 'vapt-security'),
                'note' => sprintf(__('Test: Access %s. It should redirect to home or show 403.', 'vapt-security'), '<a href="' . esc_url(site_url('/?author=1')) . '" target="_blank"><code>' . esc_html(site_url('/?author=1')) . '</code></a>'),
                'icon' => 'dashicons-admin-users',
            ],
            'disable_file_edit' => [
                'label' => __('File Editor Disabled', 'vapt-security'),
                'desc' => __('Disables the built-in file editor for themes and plugins to prevent code injection if compromised.', 'vapt-security'),
                'note' => __('Test: Check Appearance > Theme File Editor. The menu should be missing.', 'vapt-security'),
                'icon' => 'dashicons-edit',
            ],
            'hide_wp_version' => [
                'label' => __('Hide WP Version', 'vapt-security'),
                'desc' => __('Hides the WordPress version number from the page source to prevent targeted exploits.', 'vapt-security'),
                'note' => __('Test: View Page Source and search for "generator". The WP version should not be visible.', 'vapt-security'),
                'icon' => 'dashicons-hidden',
            ],
            'security_headers' => [
                'label' => __('Security Headers', 'vapt-security'),
                'desc' => __('Adds strictly configured HTTP security headers (HSTS, X-Frame-Options, etc.).', 'vapt-security'),
                'note' => __('Test: Check HTTP headers in browser dev tools. Look for X-Frame-Options: SAMEORIGIN.', 'vapt-security'),
                'icon' => 'dashicons-admin-network',
            ],
            'restrict_rest_api' => [
                'label' => __('REST API Restriction', 'vapt-security'),
                'desc' => __('Restricts REST API access to authenticated users only.', 'vapt-security'),
                'note' => sprintf(__('Test: Visit %s while logged out. It should return 401 Unauthorized.', 'vapt-security'), '<a href="' . esc_url(site_url('/wp-json/wp/v2/users')) . '" target="_blank"><code>' . esc_html(site_url('/wp-json/wp/v2/users')) . '</code></a>'),
                'icon' => 'dashicons-lock',
            ],
        ];

        // Filter Hardening features by availability
        $domain_features = VAPT_Features::get_active_features();
        $allowed_hardening_features = [];
        foreach ($hardening_features as $slug => $data) {
            if (!empty($domain_features[$slug])) {
                $allowed_hardening_features[$slug] = $data;
            }
        }
        $hardening_features = $allowed_hardening_features;

        if (!empty($hardening_features)) {
            $active_tabs++;
        }

        // Dynamic Layout Decision
        // If <= 5, use Horizontal (default/legacy structure, or generic class)
        // If > 5, use Vertical
        $container_class = ($active_tabs > 5) ? "vapt-vertical-tabs" : "vapt-horizontal-tabs";
        ?>


        <!-- Modern Horizontal/Vertical Tabs -->
        <div id="vapt-security-tabs" class="<?php echo esc_attr(
                                                $container_class,
                                            ); ?>">
            <!-- Tab Navigation -->
            <ul class="vapt-security-tabs">
                <?php if (
                    defined("VAPT_FEATURE_WP_CRON_PROTECTION") &&
                    VAPT_FEATURE_WP_CRON_PROTECTION
                ): ?>
                    <li class="vapt-security-tab"><a href="#tab-general"><?php esc_html_e(
                                                                                "WP-Cron Protection",
                                                                                "vapt-security",
                                                                            ); ?></a></li>
                <?php endif; ?>
                <?php if (
                    VAPT_Features::is_enabled("input_validation") &&
                    defined("VAPT_FEATURE_INPUT_VALIDATION") &&
                    VAPT_FEATURE_INPUT_VALIDATION
                ): ?>
                    <li class="vapt-security-tab"><a href="#tab-validation"><?php esc_html_e(
                                                                                "Input Validation",
                                                                                "vapt-security",
                                                                            ); ?></a></li>
                <?php endif; ?>
                <?php if (
                    VAPT_Features::is_enabled("rate_limiting") &&
                    defined("VAPT_FEATURE_RATE_LIMITING") &&
                    VAPT_FEATURE_RATE_LIMITING
                ): ?>
                    <li class="vapt-security-tab"><a href="#tab-rate-limiter"><?php esc_html_e(
                                                                                    "Rate Limiter",
                                                                                    "vapt-security",
                                                                                ); ?></a></li>
                <?php endif; ?>


                <?php if (!empty($hardening_features)): ?>
                    <li class="vapt-security-tab"><a href="#tab-hardening"><?php esc_html_e(
                                                                                "Hardening",
                                                                                "vapt-security",
                                                                            ); ?></a></li>
                <?php endif; ?>
                <li class="vapt-security-tab"><a href="#tab-stats"><?php esc_html_e(
                                                                        "Statistics",
                                                                        "vapt-security",
                                                                    ); ?></a></li>

            </ul>

            <!-- Tab Content -->
            <?php if (
                defined("VAPT_FEATURE_WP_CRON_PROTECTION") &&
                VAPT_FEATURE_WP_CRON_PROTECTION
            ): ?>
                <div id="tab-general" class="vapt-security-tab-content">
                    <div class="vapt-grid-row">
                        <!-- Left Column: General Configuration -->
                        <div class="vapt-grid-col">
                            <h3><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e("General Configuration", "vapt-security"); ?></h3>
                            <div class="settings-section">
                                <?php do_settings_sections("vapt_security_general"); ?>
                            </div>

                            <div class="vapt-server-cron-info" style="background: #fdfdfd; padding: 15px; border: 1px solid #ccd0d4; border-left: 4px solid #72aee6; margin-top: 25px; border-radius: 4px;">
                                <h4 style="margin-top: 0;"><span class="dashicons dashicons-admin-settings" style="vertical-align: text-bottom; margin-right: 5px;"></span><?php esc_html_e("Server Level Custom Cron", "vapt-security"); ?></h4>
                                <p class="description" style="margin-bottom: 10px;"><?php esc_html_e("To ensure robust performance and reliability, we recommend disabling WP-Cron and setting up a real server-level cron job.", "vapt-security"); ?></p>
                                <p><strong><?php esc_html_e("Example Command (every 30 mins):", "vapt-security"); ?></strong></p>
                                <code style="display: block; padding: 10px; background: #fff; border: 1px solid #eee; border-radius: 4px; font-family: monospace; word-break: break-all; font-size: 11px;">*/30 * * * * wget -q -O - <?php echo esc_url(site_url("wp-cron.php?doing_wp_cron")); ?> >/dev/null 2>&1</code>
                            </div>

                            <div class="settings-feature-box" style="margin-top: 20px;">
                                <h4><span class="dashicons dashicons-performance" style="vertical-align: text-bottom; margin-right: 5px;"></span><?php esc_html_e("Cron Rate Limit Test", "vapt-security"); ?></h4>
                                <p><?php
                                    $cron_limit = isset($opts["cron_rate_limit"]) ? (int)$opts["cron_rate_limit"] : 60;
                                    $cron_sim_count = ceil($cron_limit * 1.2);
                                    printf(
                                        esc_html__(
                                            "Simulate rapid cron requests to verify throttling. Recommended simulation: %d requests.",
                                            "vapt-security"
                                        ),
                                        $cron_sim_count
                                    );
                                    ?></p>
                                <p>
                                    <label for="vapt_cron_sim_count" style="margin-right: 5px; font-weight: 600;"><?php esc_html_e("Requests:", "vapt-security"); ?></label>
                                    <input type="number" id="vapt_cron_sim_count" value="<?php echo (int)$cron_sim_count; ?>" min="1" step="1" style="width: 60px; margin-right: 5px;" autocomplete="off">

                                    <button type="button" class="button button-secondary" id="vapt-run-cron-diagnostic">
                                        <?php esc_html_e("Run Cron Test", "vapt-security"); ?>
                                    </button>
                                    <span id="vapt-cron-diagnostic-spinner" class="spinner" style="float: none; margin: 0 10px;"></span>
                                </p>
                                <div id="vapt-cron-diagnostic-result"></div>
                            </div>
                        </div>

                        <!-- Right Column: WP-Cron Settings -->
                        <div class="vapt-grid-col">
                            <h3><span class="dashicons dashicons-clock"></span> <?php esc_html_e("WP-Cron Settings", "vapt-security"); ?></h3>
                            <div class="settings-section">
                                <?php do_settings_sections("vapt_security_cron"); ?>
                            </div>

                            <div class="settings-feature-box" style="margin-top: 25px;">
                                <h4><span class="dashicons dashicons-visibility" style="vertical-align: text-bottom; margin-right: 5px;"></span><?php esc_html_e("Diagnostics & Evidence", "vapt-security"); ?></h4>
                                <table class="vapt-diag-table" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 5px 0;"><strong><?php esc_html_e("WP-Cron Disabled:", "vapt-security"); ?></strong></td>
                                        <td style="text-align: right;"><?php echo defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? '<span style="color:green; font-weight:600;">' . esc_html__('Yes (Recommended)', 'vapt-security') . '</span>' : '<span style="color:orange; font-weight:600;">' . esc_html__('No (Default)', 'vapt-security') . '</span>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0;"><strong><?php esc_html_e("Alternate Cron:", "vapt-security"); ?></strong></td>
                                        <td style="text-align: right;"><?php echo defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ? esc_html__('Active', 'vapt-security') : esc_html__('Inactive', 'vapt-security'); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0;"><strong><?php esc_html_e("Server IP Address:", "vapt-security"); ?></strong></td>
                                        <td style="text-align: right;"><code><?php
                                                                                $server_ip = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
                                                                                if ($server_ip === '::1') {
                                                                                    $server_ip = gethostbyname(gethostname()) ?: '127.0.0.1';
                                                                                }
                                                                                echo esc_html($server_ip);
                                                                                ?></code></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0;"><strong><?php esc_html_e("Next Scheduled Event:", "vapt-security"); ?></strong></td>
                                        <td style="text-align: right;"><?php
                                                                        $next = wp_next_scheduled('wp_version_check'); // Common event
                                                                        echo $next ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next) : esc_html__('None found', 'vapt-security');
                                                                        ?></td>
                                    </tr>
                                </table>
                                <p class="description" style="margin-top: 10px; font-size: 11px;">
                                    <?php esc_html_e("Evidence data helps confirm if server-level triggers are reaching WordPress correctly.", "vapt-security"); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                VAPT_Features::is_enabled("input_validation") &&
                defined("VAPT_FEATURE_INPUT_VALIDATION") &&
                VAPT_FEATURE_INPUT_VALIDATION
            ): ?>
                <div id="tab-validation" class="vapt-security-tab-content">
                    <div class="vapt-grid-row">
                        <!-- Left Column: Validation Rules -->
                        <div class="vapt-grid-col">
                            <h3><span class="dashicons dashicons-filter"></span> <?php esc_html_e("Validation Rules", "vapt-security"); ?></h3>
                            <div class="settings-section">
                                <?php do_settings_sections("vapt_security_validation"); ?>
                            </div>
                        </div>

                        <!-- Right Column: Form Integrations -->
                        <div class="vapt-grid-col">
                            <h3><span class="dashicons dashicons-forms"></span> <?php esc_html_e("Form Integrations", "vapt-security"); ?></h3>
                            <div class="settings-section">
                                <?php do_settings_sections("vapt_security_integrations"); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                VAPT_Features::is_enabled("rate_limiting") &&
                defined("VAPT_FEATURE_RATE_LIMITING") &&
                VAPT_FEATURE_RATE_LIMITING
            ): ?>
                <div id="tab-rate-limiter" class="vapt-security-tab-content">
                    <div class="vapt-grid-row">
                        <!-- Left Column: Settings -->
                        <div class="vapt-grid-col">
                            <h3><span class="dashicons dashicons-shield"></span> <?php esc_html_e('Rate Limiter Settings', 'vapt-security'); ?></h3>
                            <div class="settings-section">
                                <?php do_settings_sections("vapt_security_rate_limiter"); ?>
                            </div>
                        </div>

                        <!-- Right Column: Diagnostics -->
                        <div class="vapt-grid-col">
                            <h3 style="margin-top:0;"><span class="dashicons dashicons-performance"></span> <?php esc_html_e(
                                                                                                                "Diagnostics",
                                                                                                                "vapt-security",
                                                                                                            ); ?></h3>
                            <div class="settings-feature-box">
                                <h4><span class="dashicons dashicons-test-automation" style="vertical-align: text-bottom; margin-right: 5px;"></span><?php esc_html_e(
                                                                                                                                                            "Rate Limit Test",
                                                                                                                                                            "vapt-security",
                                                                                                                                                        ); ?></h4>
                                <p><?php
                                    $opts = $this->get_config();
                                    // Fix key mismatch: use rate_limit_max primarily
                                    $rl_val = isset($opts["rate_limit_max"]) ? (int)$opts["rate_limit_max"] : (isset($opts["vapt_rate_limit_requests"]) ? (int)$opts["vapt_rate_limit_requests"] : 15);
                                    $rate_limit = ($rl_val < 1) ? 15 : $rl_val;

                                    $sim_count = ceil($rate_limit * 1.5);
                                    printf(
                                        esc_html__(
                                            "Click the button below to simulate %d rapid requests. If Rate Limiting is working, it should block requests after the limit (%d) is reached.",
                                            "vapt-security",
                                        ),
                                        $sim_count,
                                        $rate_limit
                                    ); ?></p>
                                <p>
                                    <label for="vapt_sim_count" style="margin-right: 5px; font-weight: 600;"><?php esc_html_e("Simulation Request Count", "vapt-security"); ?></label>
                                    <input type="number" id="vapt_sim_count" value="<?php echo (int)$sim_count; ?>" min="1" step="1" style="width: 60px; margin-right: 5px;" autocomplete="off">
                                    <span style="margin-right: 15px;"><?php esc_html_e("per minute", "vapt-security"); ?></span>

                                    <button type="button" class="button button-secondary" id="vapt-run-diagnostic">
                                        <?php esc_html_e(
                                            "Run Diagnostic Test",
                                            "vapt-security",
                                        ); ?>
                                    </button>
                                    <span id="vapt-diagnostic-spinner" class="spinner" style="float: none; margin: 0 10px;"></span>
                                </p>
                                <div id="vapt-diagnostic-result"></div>
                            </div>
                            <div class="settings-feature-box" style="margin-top: 20px;">
                                <h4><span class="dashicons dashicons-networking" style="vertical-align: text-bottom; margin-right: 5px;"></span><?php esc_html_e(
                                                                                                                                                    "Active Integrations",
                                                                                                                                                    "vapt-security",
                                                                                                                                                ); ?></h4>
                                <p><?php esc_html_e(
                                        "The following integrations are currently active and hooked into the security engine:",
                                        "vapt-security",
                                    ); ?></p>
                                <ul style="list-style: disc; margin-left: 20px;">
                                    <?php
                                    $active_integrations = [];
                                    $opts = $this->get_config();

                                    if (!empty($opts["vapt_integration_cf7"])) {
                                        $active_integrations[] = "Contact Form 7";
                                    }
                                    if (!empty($opts["vapt_integration_elementor"])) {
                                        $active_integrations[] = "Elementor Forms";
                                    }
                                    if (!empty($opts["vapt_integration_wpforms"])) {
                                        $active_integrations[] = "WPForms";
                                    }
                                    if (!empty($opts["vapt_integration_gravity"])) {
                                        $active_integrations[] = "Gravity Forms";
                                    }

                                    if (empty($active_integrations)) {
                                        echo "<li><em>" .
                                            esc_html__(
                                                "No integrations enabled.",
                                                "vapt-security",
                                            ) .
                                            "</em></li>";
                                    } else {
                                        foreach ($active_integrations as $int) {
                                            echo '<li style="color: #00a32a; font-weight: bold;">' .
                                                esc_html($int) .
                                                ' <span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span></li>';
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div> <!-- End Grid Row -->
                </div>
            <?php endif; ?>





            <?php if (!empty($hardening_features)): ?>
                <div id="tab-hardening" class="vapt-security-tab-content">
                    <h3><span class="dashicons dashicons-lock"></span> <?php esc_html_e("Server Hardening", "vapt-security"); ?></h3>
                    <p class="description" style="margin-bottom: 20px;"><?php esc_html_e("Enable these hardening features to secure your WordPress installation against common attacks.", "vapt-security"); ?></p>

                    <div class="vapt-hardening-grid">
                        <?php
                        // Admin Settings (Activation Control by Admin)
                        $admin_settings = get_option('vapt_hardening_settings', []);
                        if (!is_array($admin_settings)) {
                            $admin_settings = [];
                        }

                        foreach ($hardening_features as $slug => $data):
                            // 2. Activation Check: Has the Admin enabled it?
                            $is_active = !empty($admin_settings[$slug]);
                        ?>
                            <div class="vapt-hardening-card <?php echo $is_active ? 'active' : ''; ?>">
                                <span class="dashicons <?php echo esc_attr($data['icon']); ?>"></span>
                                <h4><?php echo esc_html($data['label']); ?></h4>

                                <label class="vapt-toggle-switch">
                                    <input type="checkbox" name="vapt_hardening_settings[<?php echo esc_attr($slug); ?>]" value="1" <?php checked($is_active); ?>>
                                    <span class="vapt-toggle-slider"></span>
                                </label>

                                <div class="vapt-hardening-desc">
                                    <?php echo esc_html($data['desc']); ?>
                                </div>

                                <div class="vapt-hardening-note">
                                    <strong><?php esc_html_e('Validation:', 'vapt-security'); ?></strong>
                                    <?php echo wp_kses_post($data['note']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div id="tab-stats" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php
                    // Display rate limiting statistics
                    $limiter = new VAPT_Rate_Limiter();
                    $limiter_stats = $limiter->get_stats();
                    ?>
                    <h3><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e(
                                                                                "Rate Limiting Statistics",
                                                                                "vapt-security",
                                                                            ); ?></h3>

                    <div class="vapt-grid-row">
                        <!-- Regular Requests -->
                        <div class="vapt-grid-col">
                            <h4 class="vapt-stat-heading"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e(
                                                                                                                    "Regular Request Statistics",
                                                                                                                    "vapt-security",
                                                                                                                ); ?></h4>
                            <table class="statistics-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e(
                                                "IP Address",
                                                "vapt-security",
                                            ); ?></th>
                                        <th><?php esc_html_e(
                                                "Request Count",
                                                "vapt-security",
                                            ); ?></th>
                                        <th><?php esc_html_e(
                                                "Actions",
                                                "vapt-security",
                                            ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (
                                        $limiter_stats["regular_requests"]
                                        as $ip => $requests
                                    ): ?>
                                        <tr>
                                            <td><?php echo esc_html($ip); ?></td>
                                            <td><?php echo esc_html(
                                                    count($requests),
                                                ); ?></td>
                                            <td>
                                                <button type="button" class="button vapt-reset-ip" data-ip="<?php echo esc_attr(
                                                                                                                $ip,
                                                                                                            ); ?>">
                                                    <?php esc_html_e(
                                                        "Reset Data",
                                                        "vapt-security",
                                                    ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Cron Requests -->
                        <div class="vapt-grid-col">
                            <h4 class="vapt-stat-heading"><span class="dashicons dashicons-update"></span> <?php esc_html_e(
                                                                                                                "Cron Request Statistics",
                                                                                                                "vapt-security",
                                                                                                            ); ?></h4>
                            <table class="statistics-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e(
                                                "IP Address",
                                                "vapt-security",
                                            ); ?></th>
                                        <th><?php esc_html_e(
                                                "Request Count",
                                                "vapt-security",
                                            ); ?></th>
                                        <th><?php esc_html_e(
                                                "Actions",
                                                "vapt-security",
                                            ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (
                                        $limiter_stats["cron_requests"]
                                        as $ip => $requests
                                    ): ?>
                                        <tr>
                                            <td><?php echo esc_html($ip); ?></td>
                                            <td><?php echo esc_html(
                                                    count($requests),
                                                ); ?></td>
                                            <td>
                                                <button type="button" class="button vapt-reset-ip" data-ip="<?php echo esc_attr(
                                                                                                                $ip,
                                                                                                            ); ?>">
                                                    <?php esc_html_e(
                                                        "Reset Data",
                                                        "vapt-security",
                                                    ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div><!-- .vapt-grid-row -->


                    <script>
                        jQuery(document).ready(function($) {
                            $('.vapt-reset-ip').on('click', function() {
                                var ip = $(this).data('ip');
                                var confirmReset = confirm('<?php esc_html_e(
                                                                "Are you sure you want to reset data for IP:",
                                                                "vapt-security",
                                                            ); ?> ' + ip);

                                if (confirmReset) {
                                    // In a real implementation, this would make an AJAX call to reset the IP data
                                    alert('<?php esc_html_e(
                                                "In a full implementation, this would reset data for IP: ",
                                                "vapt-security",
                                            ); ?>' + ip);
                                }
                            });
                        });
                    </script>

                    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">

                    <?php
                    // Display log statistics
                    $logger = new VAPT_Security_Logger();
                    $stats = $logger->get_statistics();
                    ?>
                    <h3><span class="dashicons dashicons-list-view"></span> <?php esc_html_e(
                                                                                "Logging Statistics",
                                                                                "vapt-security",
                                                                            ); ?></h3>
                    <table class="statistics-table">
                        <tr>
                            <td><?php esc_html_e(
                                    "Total Events Logged:",
                                    "vapt-security",
                                ); ?></td>
                            <td><?php echo esc_html(
                                    $stats["total_events"],
                                ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e(
                                    "Events in Last 24 Hours:",
                                    "vapt-security",
                                ); ?></td>
                            <td><?php echo esc_html(
                                    $stats["last_24_hours"],
                                ); ?></td>
                        </tr>
                    </table>

                    <?php if (!empty($stats["event_types"])): ?>
                        <h4 class="vapt-stat-heading"><span class="dashicons dashicons-category"></span> <?php esc_html_e(
                                                                                                                "Event Types",
                                                                                                                "vapt-security",
                                                                                                            ); ?></h4>
                        <table class="statistics-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e(
                                            "Event Type",
                                            "vapt-security",
                                        ); ?></th>
                                    <th><?php esc_html_e(
                                            "Count",
                                            "vapt-security",
                                        ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (
                                    $stats["event_types"]
                                    as $type => $count
                                ): ?>
                                    <tr>
                                        <td><?php echo esc_html(
                                                $type,
                                            ); ?></td>
                                        <td><?php echo esc_html(
                                                $count,
                                            ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if (!empty($stats["top_ips"])): ?>
                        <h4 class="vapt-stat-heading"><span class="dashicons dashicons-location"></span> <?php esc_html_e(
                                                                                                                "Top IPs",
                                                                                                                "vapt-security",
                                                                                                            ); ?></h4>
                        <table class="statistics-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e(
                                            "IP Address",
                                            "vapt-security",
                                        ); ?></th>
                                    <th><?php esc_html_e(
                                            "Event Count",
                                            "vapt-security",
                                        ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (
                                    $stats["top_ips"]
                                    as $ip => $count
                                ): ?>
                                    <tr>
                                        <td><?php echo esc_html(
                                                $ip,
                                            ); ?></td>
                                        <td><?php echo esc_html(
                                                $count,
                                            ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>


        </div>

        <?php submit_button(); ?>
    </form>
</div>

<style>
    .vapt-grid-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .vapt-grid-col {
        flex: 1;
        min-width: 300px;
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    }

    .vapt-grid-col h3 {
        margin-top: 0;
        border-bottom: 2px solid #e2e4e7;
        padding: 12px 15px;
        margin-bottom: 20px;
        color: #1d2327;
        font-size: 1.1em;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f6f7f7;
        border-radius: 4px;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.05);
    }

    .vapt-grid-col h3 .dashicons {
        color: #2271b1;
        font-size: 20px;
        width: 20px;
        height: 20px;
    }

    .vapt-grid-col h4 {
        margin-top: 15px;
        margin-bottom: 10px;
        font-weight: 600;
        color: #2c3338;
    }

    .vapt-stat-heading {
        margin-top: 0 !important;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f1;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .vapt-stat-heading .dashicons {
        color: #646970;
    }

    .settings-feature-box {
        background: #fdfdfd;
        border: 1px solid #ccd0d4;
        padding: 15px;
        border-radius: 6px;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .vapt-security-tab-content>h3 {
        margin: 0 0 20px 0;
        padding: 12px 15px;
        border: 1px solid #ccd0d4;
        background: #f6f7f7;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.3em;
        color: #1d2327;
    }

    .vapt-security-tab-content>h3 .dashicons {
        color: #2271b1;
    }

    @media (max-width: 782px) {
        .vapt-grid-row {
            display: block;
        }

        .vapt-grid-col {
            margin-bottom: 20px;
        }
    }
</style>
<script>
    jQuery(function($) {
        $('#vapt-security-tabs').tabs({
            active: 0,
            activate: function(event, ui) {
                // Store the active tab in localStorage
                localStorage.setItem('vapt_security_active_tab', ui.newTab.index());
            },
            create: function(event, ui) {
                // Restore the active tab from localStorage
                var activeTab = localStorage.getItem('vapt_security_active_tab');
                if (activeTab !== null) {
                    $(this).tabs('option', 'active', parseInt(activeTab));
                }
            }
        });


        // AJAXify Main Settings Form
        $('#vapt-settings-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = form.find('input[type="submit"]');
            var originalText = submitBtn.val();

            submitBtn.prop('disabled', true).val('<?php esc_html_e("Saving...", "vapt-security"); ?>');

            var data = form.serialize();

            $.post(ajaxurl, {
                action: 'vapt_save_settings',
                data: data,
                vapt_save_nonce: '<?php echo wp_create_nonce("vapt_save_settings_action"); ?>' // Add dedicated nonce
            }, function(response) {
                submitBtn.prop('disabled', false).val(originalText);

                // Show success/error message
                var msgHtml = '';
                if (response.success) {
                    msgHtml = '<div class="notice notice-success is-dismissible" style="display:none; margin: 15px 0;"><p>' + response.data.message + '</p></div>';
                } else {
                    msgHtml = '<div class="notice notice-error is-dismissible" style="display:none; margin: 15px 0;"><p>' + (response.data.message || 'Error saving settings') + '</p></div>';
                }

                // Remove old notices
                $('.notice').remove();

                // Prepend new notice to form
                form.prepend(msgHtml);
                form.find('.notice').slideDown();

                // Auto hide success
                if (response.success) {
                    setTimeout(function() {
                        $('.notice-success').slideUp(function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
            }).fail(function() {
                submitBtn.prop('disabled', false).val(originalText);
                alert('<?php esc_html_e("Server error or connection failed.", "vapt-security"); ?>');
            });
        });

        // Live update of simulation count when Rate Limit setting changes
        $('input[name="vapt_security_options[rate_limit_max]"]').on('change keyup', function() {
            var newLimit = parseInt($(this).val()) || 15;
            if (newLimit < 1) newLimit = 15;
            var newSimCount = Math.ceil(newLimit * 1.5);

            // Update input value
            $('#vapt_sim_count').val(newSimCount);

            // Update description text (optional but good for consistency)
            // We can't easily regex replace the text node without a span wrapper, 
            // but updating the input is the main requirement.
        });
    });

    // Diagnostic Tool JS
    $('#vapt-run-diagnostic').on('click', function() {
        var btn = $(this);
        var spinner = $('#vapt-diagnostic-spinner');
        var resultDiv = $('#vapt-diagnostic-result');

        btn.prop('disabled', true);
        spinner.addClass('is-active');
        resultDiv.html('');

        var requests = [];
        var blocked = false;
        var successCount = 0;
        var blockedCount = 0;

        <?php
        // Get configured rate limit or default to 15
        $opts = $this->get_config();
        // Set test count to 1.5x the limit, rounded up
        // Use correct key 'rate_limit_max' matching vapt-security.php registry
        $rl_val = isset($opts["rate_limit_max"]) ? (int)$opts["rate_limit_max"] : (isset($opts["vapt_rate_limit_requests"]) ? (int)$opts["vapt_rate_limit_requests"] : 15);
        $rate_limit = ($rl_val < 1) ? 15 : $rl_val;

        $test_count = ceil($rate_limit * 1.5);
        ?>
        var rateLimitSetting = <?php echo (int)$rate_limit; ?>;
        var totalRequests = parseInt($('#vapt_sim_count').val()) || <?php echo (int)$test_count; ?>;

        if (totalRequests < 1) {
            alert('Please enter a valid number of requests.');
            btn.prop('disabled', false);
            spinner.removeClass('is-active');
            return;
        }

        function sendRequest(i) {
            return $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'vapt_form_submit', // Target the protected action
                    nonce: 'dummy_nonce_diagnostic', // Rate Limit runs BEFORE Nonce check
                    test_mode: true
                }
            }).always(function(data, textStatus, jqXHR) {
                // Check for 429 status or specific error message
                if (jqXHR.status === 429 || (data && data.success === false && data.data && data.data.message && data.data.message.indexOf('Too many') !== -1)) {
                    blocked = true;
                    blockedCount++;
                } else {
                    successCount++;
                }
            });
        }

        var promises = [];
        for (var i = 0; i < totalRequests; i++) {
            promises.push(sendRequest(i));
        }

        $.when.apply($, promises).always(function() {
            btn.prop('disabled', false);
            spinner.removeClass('is-active');

            if (blocked) {
                resultDiv.html('<div class="notice notice-success inline"><p><strong><?php esc_html_e(
                                                                                            "Success:",
                                                                                            "vapt-security",
                                                                                        ); ?></strong> <?php esc_html_e(
                                                                                                            "Rate Limiting is WORKING. Requests were blocked after the limit was exceeded.",
                                                                                                            "vapt-security",
                                                                                                        ); ?> (' + blockedCount + ' blocked)</p></div>');
            } else {
                resultDiv.html('<div class="notice notice-error inline"><p><strong><?php esc_html_e(
                                                                                        "Warning:",
                                                                                        "vapt-security",
                                                                                    ); ?></strong> <?php esc_html_e(
                                                                                                        "Rate Limiting did NOT trigger. Ensure the limit is low enough (e.g., 10 requests/min) for this test.",
                                                                                                        "vapt-security",
                                                                                                    ); ?></p></div>');
            }
        });
    });

    // Cron Diagnostic Tool JS
    $('#vapt-run-cron-diagnostic').on('click', function() {
    var btn = $(this);
    var spinner = $('#vapt-cron-diagnostic-spinner');
    var resultDiv = $('#vapt-cron-diagnostic-result');
    var cronUrl = '<?php echo esc_url(site_url("wp-cron.php")); ?>?doing_wp_cron=' + Date.now();

    btn.prop('disabled', true);
    spinner.addClass('is-active');
    resultDiv.html('');

    var requests = [];
    var blocked = false;
    var successCount = 0;
    var blockedCount = 0;
    var totalRequests = parseInt($('#vapt_cron_sim_count').val()) || 60;

    function sendCronRequest(i) {
        return $.ajax({
            url: cronUrl,
            method: 'GET',
            cache: false
        }).always(function(data, textStatus, jqXHR) {
            if (jqXHR.status === 429) {
                blocked = true;
                blockedCount++;
            } else {
                successCount++;
            }
        });
    }

    var promises = [];
    for (var i = 0; i < totalRequests; i++) {
        promises.push(sendCronRequest(i));
    }

    $.when.apply($, promises).always(function() {
        btn.prop('disabled', false);
        spinner.removeClass('is-active');

        if (blocked) {
            resultDiv.html('<div class="notice notice-success inline"><p><strong><?php esc_html_e("Success:", "vapt-security"); ?></strong> <?php esc_html_e("Cron Rate Limiting is ACTIVE. Requests were blocked after reaching the hourly limit.", "vapt-security"); ?> (' + blockedCount + ' blocked)</p></div>');
        } else {
            resultDiv.html('<div class="notice notice-error inline"><p><strong><?php esc_html_e("Warning:", "vapt-security"); ?></strong> <?php esc_html_e("Cron Limiter did NOT trigger. Checked ", "vapt-security"); ?>' + totalRequests + '<?php esc_html_e(" requests. Try lowering the Cron Limit setting temporarily to test.", "vapt-security"); ?></p></div>');
        }
    });
    });
    });
</script>