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

    <form method="post" action="options.php">
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

        // WP-Cron Protection (distinct from General?)
        if (
            VAPT_Features::is_enabled("cron_protection") &&
            defined("VAPT_FEATURE_WP_CRON_PROTECTION") &&
            VAPT_FEATURE_WP_CRON_PROTECTION
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
                                                                                "General",
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
                    VAPT_Features::is_enabled("cron_protection") &&
                    defined("VAPT_FEATURE_WP_CRON_PROTECTION") &&
                    VAPT_FEATURE_WP_CRON_PROTECTION
                ): ?>
                    <li class="vapt-security-tab"><a href="#tab-cron"><?php esc_html_e(
                                                                            "WP‑Cron Protection",
                                                                            "vapt-security",
                                                                        ); ?></a></li>
                <?php endif; ?>
                <?php if (
                    VAPT_Features::is_enabled("security_logging") &&
                    defined("VAPT_FEATURE_SECURITY_LOGGING") &&
                    VAPT_FEATURE_SECURITY_LOGGING
                ): ?>
                    <li class="vapt-security-tab"><a href="#tab-logging"><?php esc_html_e(
                                                                                "Security Logging",
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
                    <div class="settings-section">
                        <h2>General Settings</h2>
                        <?php do_settings_sections("vapt_security_general"); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                VAPT_Features::is_enabled("rate_limiting") &&
                defined("VAPT_FEATURE_RATE_LIMITING") &&
                VAPT_FEATURE_RATE_LIMITING
            ): ?>
                <div id="tab-rate-limiter" class="vapt-security-tab-content">
                    <h2>Rate Limiter Settings</h2>

                    <!-- Nested Tabs for Rate Limiter -->
                    <!-- Removed sub-tabs as per user request -->

                    <?php do_settings_sections("vapt_security_rate_limiter"); ?>

                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ddd;">

                    <!-- Sub Tab 2: Diagnostics -->
                    <div id="sub-tab-rl-diagnostics">
                        <h3 style="margin-top:0;"><?php esc_html_e(
                                                        "Diagnostics",
                                                        "vapt-security",
                                                    ); ?></h3>
                        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px;">
                            <h4><?php esc_html_e(
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
                        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; border-radius: 4px; margin-top: 15px;">
                            <h4><?php esc_html_e(
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
                    </div> <!-- End Diagnostics Wrapper -->

                </div>
            <?php endif; ?>

            <?php if (
                VAPT_Features::is_enabled("input_validation") &&
                defined("VAPT_FEATURE_INPUT_VALIDATION") &&
                VAPT_FEATURE_INPUT_VALIDATION
            ): ?>
                <div id="tab-validation" class="vapt-security-tab-content">
                    <div class="settings-section">
                        <?php do_settings_sections(
                            "vapt_security_validation",
                        ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                VAPT_Features::is_enabled("cron_protection") &&
                defined("VAPT_FEATURE_WP_CRON_PROTECTION") &&
                VAPT_FEATURE_WP_CRON_PROTECTION
            ): ?>
                <div id="tab-cron" class="vapt-security-tab-content">
                    <div class="settings-section">
                        <?php do_settings_sections("vapt_security_cron"); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (
                VAPT_Features::is_enabled("security_logging") &&
                defined("VAPT_FEATURE_SECURITY_LOGGING") &&
                VAPT_FEATURE_SECURITY_LOGGING
            ): ?>
                <div id="tab-logging" class="vapt-security-tab-content">
                    <div class="settings-section">
                        <?php do_settings_sections("vapt_security_logging"); ?>

                        <?php
                        // Display log statistics
                        $logger = new VAPT_Security_Logger();
                        $stats = $logger->get_statistics();
                        ?>
                        <h3><?php esc_html_e(
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
                            <h4><?php esc_html_e(
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
                            <h4><?php esc_html_e(
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
            <?php endif; ?>

            <div id="tab-stats" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php
                    // Display rate limiting statistics
                    $limiter = new VAPT_Rate_Limiter();
                    $limiter_stats = $limiter->get_stats();
                    ?>
                    <h3><?php esc_html_e(
                            "Rate Limiting Statistics",
                            "vapt-security",
                        ); ?></h3>

                    <h4><?php esc_html_e(
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

                    <h4><?php esc_html_e(
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
                </div>
            </div>


        </div>

        <?php submit_button(); ?>
    </form>
</div>

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

    });
</script>