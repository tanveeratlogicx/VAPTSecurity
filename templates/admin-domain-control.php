<?php
// Superadmin Domain Control Page
// Check transient auth
$user = wp_get_current_user();
$user_id = ($user instanceof WP_User) ? $user->ID : 0;

// Superadmin Check
$is_super = VAPT_Security::is_superadmin();
$is_verified = (get_transient('vapt_auth_' . $user_id)) || (defined('VAPT_OTP_ACCESS_GRANTED') && VAPT_OTP_ACCESS_GRANTED) || $is_super;

// License Data
$license = VAPT_License::get_license();
if (!$license) {
    VAPT_License::activate_license(); // Try to init
    $license = VAPT_License::get_license();
}
// Fallback if still false (should not happen after activate)
if (!$license) {
    $license = ['type' => 'standard', 'expires' => 0, 'auto_renew' => false];
}

$license_type = $license['type'] ?? 'standard';
$license_expires = $license['expires'] ?? 0;
$license_auto_renew = $license['auto_renew'] ?? false;
$expiry_date = $license_expires ? date_i18n(get_option('date_format'), $license_expires) : __('Never', 'vapt-security');

// Get Active Features
$features = VAPT_Features::get_active_features();
$all_features = VAPT_Features::get_defined_features();
$build_info   = VAPT_Security::get_build_info();
$build_ver    = $build_info['generated_at'] ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $build_info['generated_at']) : __('N/A', 'vapt-security');
$build_domain = $build_info['domain_pattern'] ?? 'N/A';

// Pre-calculate future expiries for JS
$future_expiries = [
    'standard' => date_i18n(get_option('date_format'), time() + (30 * DAY_IN_SECONDS)),
    'pro'      => date_i18n(get_option('date_format'), time() + (365 * DAY_IN_SECONDS) - DAY_IN_SECONDS),
    'developer' => __('Never', 'vapt-security')
];

// Feature Descriptions
$feature_descriptions = [
    'rate_limiting'    => __('Rate limits form submissions to prevent spam.', 'vapt-security'),
    'input_validation' => __('Validates user input to prevent XSS.', 'vapt-security'),
    'cron_protection'  => __('Protects wp-cron.php and allows disabling default cron.', 'vapt-security'),
];

// Current Version
$vapt_version = defined('VAPT_VERSION') ? VAPT_VERSION : '2.x';
?>
<div class="wrap">
    <h1>
        <?php esc_html_e('VAPT Security - Domain Admin', 'vapt-security'); ?>
        <span style="font-size: 0.6em; color: #646970; font-weight: 400;">v<?php echo esc_html($vapt_version); ?></span>
    </h1>

    <?php if (! $is_verified) : ?>
        <!-- OTP Form (Similar to before but specific to Superadmin flow) -->
        <div class="vapt-otp-container card" style="max-width: 400px; margin-top: 20px; padding: 20px;">
            <h2><?php esc_html_e('Superadmin Authentication', 'vapt-security'); ?></h2>
            <p><?php esc_html_e('Verify your identity to manage Domain Features.', 'vapt-security'); ?></p>

            <div id="vapt-otp-step-1">
                <button type="button" id="vapt-send-otp" class="button button-primary button-hero">
                    <?php esc_html_e('Send OTP', 'vapt-security'); ?>
                </button>
            </div>

            <div id="vapt-otp-step-2" style="display:none; margin-top: 20px;">
                <input type="text" id="vapt-otp-input" class="regular-text" placeholder="------" maxlength="6" style="width: 100%; text-align: center; letter-spacing: 5px;" />
                <button type="button" id="vapt-verify-otp" class="button button-primary button-hero" style="width: 100%; margin-top: 10px;">
                    <?php esc_html_e('Verify', 'vapt-security'); ?>
                </button>
                <div style="margin-top: 10px; text-align: center;">
                    <span id="vapt-otp-timer-container"><?php esc_html_e('Resend in', 'vapt-security'); ?> <span id="vapt-otp-timer">120</span>s</span>
                    <a href="#" id="vapt-resend-otp" style="display:none;"><?php esc_html_e('Resend OTP', 'vapt-security'); ?></a>
                </div>
            </div>
            <div id="vapt-otp-message" style="margin-top: 15px;"></div>
        </div>

    <?php else : ?>
        <!-- Verified Superadmin UI with Tabs -->

        <div id="vapt-domain-tabs-container">
            <ul class="vapt-domain-tabs">
                <li><a href="#tab-license"><?php esc_html_e('License Management', 'vapt-security'); ?></a></li>
                <li><a href="#tab-features"><?php esc_html_e('Domain Features', 'vapt-security'); ?></a></li>
                <li><a href="#tab-build"><?php esc_html_e('Build Generator', 'vapt-security'); ?></a></li>
            </ul>

            <!-- Tab 1: License Management -->
            <div id="tab-license" class="vapt-domain-tab-content">
                <div class="vapt-grid-row">
                    <!-- Left Column: Current Status -->
                    <div class="vapt-grid-col">
                        <h3><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e('License Status', 'vapt-security'); ?></h3>
                        <div class="settings-feature-box">
                            <p style="margin-top: 0;">
                                <strong><?php esc_html_e('Current Type:', 'vapt-security'); ?></strong>
                                <span id="vapt-current-license-type" class="vapt-badge" style="text-transform: capitalize;">
                                    <?php echo esc_html($license_type); ?>
                                </span>
                            </p>
                            <p><strong><?php esc_html_e('Expiry Date:', 'vapt-security'); ?></strong> <code id="vapt-current-expiry"><?php echo esc_html($expiry_date); ?></code></p>
                            <?php if (isset($license['renewal_count'])) : ?>
                                <p>
                                    <strong><?php esc_html_e('Terms Renewed:', 'vapt-security'); ?></strong>
                                    <span class="vapt-badge" style="background: #646970;">
                                        <?php echo esc_html($license['renewal_count']); ?>
                                    </span>
                                </p>
                            <?php endif; ?>

                            <hr style="border: none; border-top: 1px solid #eee; margin: 15px 0;">

                            <p class="description">
                                <?php esc_html_e('Licenses are enforced at the domain level. Standard and Pro licenses expire after their term, while Developer licenses are perpetual for the authorized domain.', 'vapt-security'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Update/Renew -->
                    <div class="vapt-grid-col">
                        <h3><span class="dashicons dashicons-update"></span> <?php esc_html_e('Update License', 'vapt-security'); ?></h3>
                        <table class="form-table" style="margin-top: 0;">
                            <tr>
                                <th scope="row" style="width: 150px;"><?php esc_html_e('License Type', 'vapt-security'); ?></th>
                                <td>
                                    <select id="vapt-license-type" style="width: 100%;">
                                        <option value="standard" <?php selected($license_type, 'standard'); ?>>Standard</option>
                                        <option value="pro" <?php selected($license_type, 'pro'); ?>>Pro</option>
                                        <option value="developer" <?php selected($license_type, 'developer'); ?>>Developer</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('New Expiry', 'vapt-security'); ?></th>
                                <td><input type="text" id="vapt-license-expiry" value="<?php echo esc_attr($expiry_date); ?>" readonly class="regular-text" style="width: 100%; background: #f0f0f1;"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Auto Renew', 'vapt-security'); ?></th>
                                <td>
                                    <label class="vapt-toggle-switch">
                                        <input type="checkbox" id="vapt-license-auto-renew" <?php checked($license_auto_renew); ?>>
                                        <span class="vapt-toggle-slider round"></span>
                                    </label>
                                    <span style="font-size: 13px; vertical-align: middle; margin-left: 10px;"><?php esc_html_e('Extend automatically', 'vapt-security'); ?></span>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px; border: 1px solid #ccd0d4;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" id="vapt-update-license" class="button button-primary" style="flex: 1;">
                                    <?php esc_html_e('Update License', 'vapt-security'); ?>
                                </button>
                                <button type="button" id="vapt-renew-license" class="button button-secondary" style="flex: 1;">
                                    <?php esc_html_e('Manual Renew', 'vapt-security'); ?>
                                </button>
                            </div>
                            <div id="vapt-license-msg" style="margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Domain Features -->
            <div id="tab-features" class="vapt-domain-tab-content">
                <div class="vapt-grid-row">
                    <div class="vapt-grid-col">
                        <h3><span class="dashicons dashicons-forms"></span> <?php esc_html_e('Domain Features', 'vapt-security'); ?></h3>
                        <p class="description" style="margin-bottom: 20px;"><?php esc_html_e('Enable or disable features for this domain. Disabled features are hidden from Admins.', 'vapt-security'); ?></p>

                        <form id="vapt-domain-features-form">
                            <?php if (count($all_features) > 0) : ?>
                                <div class="vapt-feature-grid">
                                    <?php foreach ($all_features as $slug => $default) : ?>
                                        <div class="vapt-feature-item">
                                            <div class="vapt-feature-header">
                                                <h4><?php echo esc_html(ucwords(str_replace('_', ' ', $slug))); ?></h4>
                                                <label class="vapt-toggle-switch">
                                                    <input type="checkbox" name="features[<?php echo esc_attr($slug); ?>]" value="1" <?php checked(VAPT_Features::is_enabled($slug)); ?>>
                                                    <span class="vapt-toggle-slider round"></span>
                                                </label>
                                            </div>
                                            <?php if (isset($feature_descriptions[$slug])) : ?>
                                                <p class="description" style="margin: 5px 0 0; font-size: 12px;"><?php echo esc_html($feature_descriptions[$slug]); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px; border: 1px solid #ccd0d4; text-align: right;">
                                <button type="button" id="vapt-save-features" class="button button-primary"><?php esc_html_e('Save Domain Features', 'vapt-security'); ?></button>
                                <span id="vapt-features-msg" style="margin-left: 10px;"></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Build Generator -->
            <div id="tab-build" class="vapt-domain-tab-content">
                <div class="vapt-grid-row">
                    <!-- Left Column: Generation Form -->
                    <div class="vapt-grid-col">
                        <h3><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Generate New Build', 'vapt-security'); ?></h3>
                        <div class="settings-feature-box">
                            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                                <span class="dashicons dashicons-admin-generic" style="vertical-align: text-bottom; margin-right: 5px; color: #2271b1;"></span>
                                <?php esc_html_e('Configuration Details', 'vapt-security'); ?>
                            </h4>

                            <table class="form-table" style="margin-top: 0;">
                                <tr>
                                    <th scope="row" style="width: 150px;"><?php esc_html_e('Target Domain', 'vapt-security'); ?></th>
                                    <td>
                                        <input type="text" id="vapt-lock-domain" class="regular-text" style="width: 100%;" placeholder="*.example.com" value="<?php echo esc_attr($_SERVER['HTTP_HOST']); ?>">
                                        <p class="description"><?php esc_html_e('Use * for wildcards (e.g., *.example.com).', 'vapt-security'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Include Config', 'vapt-security'); ?></th>
                                    <td>
                                        <label class="vapt-toggle-switch">
                                            <input type="checkbox" id="vapt-lock-include-settings" checked>
                                            <span class="vapt-toggle-slider round"></span>
                                        </label>
                                        <span style="font-size: 13px; vertical-align: middle; margin-left: 10px;"><?php esc_html_e('Export current settings', 'vapt-security'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Plugin Name', 'vapt-security'); ?></th>
                                    <td><input type="text" id="vapt-build-plugin-name" class="regular-text" style="width: 100%;" placeholder="VAPT Security"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Author', 'vapt-security'); ?></th>
                                    <td><input type="text" id="vapt-build-author-name" class="regular-text" style="width: 100%;" placeholder="Tanveer Malik"></td>
                                </tr>
                            </table>

                            <div style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px; border: 1px solid #ccd0d4;">
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="button" id="vapt-generate-client-zip" class="button button-primary" style="flex: 1;">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php esc_html_e('Download Zip', 'vapt-security'); ?>
                                    </button>
                                    <button type="button" id="vapt-generate-locked-config" class="button button-secondary" style="flex: 1;">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php esc_html_e('Save to Server', 'vapt-security'); ?>
                                    </button>
                                </div>
                                <div id="vapt-generate-msg" style="margin-top: 10px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Current Build Status -->
                    <div class="vapt-grid-col">
                        <h3><span class="dashicons dashicons-info"></span> <?php esc_html_e('Build Status', 'vapt-security'); ?></h3>
                        <div class="settings-feature-box">
                            <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                                <span class="dashicons dashicons-info" style="vertical-align: text-bottom; margin-right: 5px; color: #2271b1;"></span>
                                <?php esc_html_e('Current Build Information', 'vapt-security'); ?>
                            </h4>
                            <p style="margin-top: 0;">
                                <strong>Generated Version:</strong> <?php echo esc_html($build_info['version'] ?? 'N/A'); ?><br>
                                <strong>Generated At:</strong> <?php echo esc_html($build_ver); ?><br>
                                <strong>Locked Domain:</strong> <code><?php echo esc_html($build_domain); ?></code><br>
                                <strong>Imported At:</strong> <?php echo esc_html(isset($build_info['imported_at']) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $build_info['imported_at']) : 'N/A'); ?>
                            </p>
                            <button type="button" id="vapt-reimport-config" class="button button-secondary" style="width: 100%; margin-top: 10px;">
                                <?php esc_html_e('Force Re-import from Server', 'vapt-security'); ?>
                            </button>
                            <div id="vapt-reimport-msg" style="margin-top: 10px;"></div>
                            <p class="description" style="margin-top:10px; font-size:12px;">
                                <?php esc_html_e('Forces an import from vapt-locked-config.php regardless of domain match. Use this to verify configurations on a test environment.', 'vapt-security'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // Initialize Tabs if element exists
                if ($('#vapt-domain-tabs-container').length) {
                    try {
                        var activeTab = localStorage.getItem('vapt_domain_active_tab');
                        var activeIndex = activeTab ? parseInt(activeTab) : 0;

                        $('#vapt-domain-tabs-container').tabs({
                            active: activeIndex,
                            activate: function(event, ui) {
                                var newIndex = ui.newTab.index();
                                localStorage.setItem('vapt_domain_active_tab', newIndex);
                            }
                        });
                    } catch (e) {
                        console.error('VAPT Tabs Error:', e);
                        // Fallback: show all or first
                        $('.vapt-domain-tab-content').show();
                    }
                }

                // Global AJAX URL Fix for port mismatches (e.g. Local by Flywheel)
                var vaptAjaxUrl = ajaxurl;
                try {
                    var url = new URL(ajaxurl);
                    if (url.origin !== window.location.origin) {
                        vaptAjaxUrl = window.location.origin + url.pathname + url.search;
                    }
                } catch (e) {}

                let timerInterval;

                function startOtpTimer() {
                    let timeLeft = 120;
                    $('#vapt-otp-timer').text(timeLeft);
                    $('#vapt-otp-timer-container').show();
                    $('#vapt-resend-otp').hide();

                    clearInterval(timerInterval);
                    timerInterval = setInterval(function() {
                        timeLeft--;
                        $('#vapt-otp-timer').text(timeLeft);
                        if (timeLeft <= 0) {
                            clearInterval(timerInterval);
                            $('#vapt-otp-timer-container').hide();
                            $('#vapt-resend-otp').show();
                        }
                    }, 1000);
                }

                // OTP Logic (Same endpoints, generic)
                $('#vapt-send-otp, #vapt-resend-otp').click(function(e) {
                    e.preventDefault();

                    // Disable buttons temporarily
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.prop('disabled', true).text('<?php esc_html_e('Sending...', 'vapt-security'); ?>');

                    $.post(vaptAjaxUrl, {
                        action: 'vapt_send_otp'
                    }, function(r) {
                        // Re-enable (for send btn)
                        btn.prop('disabled', false).html(originalText);

                        if (r.success) {
                            $('#vapt-otp-step-1').hide();
                            $('#vapt-otp-step-2').show();
                            $('#vapt-otp-message').html('<span style="color:green">' + r.data.message + '</span>');
                            startOtpTimer();
                        } else {
                            $('#vapt-otp-message').html('<span style="color:red">' + r.data.message + '</span>');
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#vapt-otp-message').html('<span style="color:red"><?php esc_html_e('Network error. Check console.', 'vapt-security'); ?></span>');
                    });
                });

                $('#vapt-verify-otp').click(function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('<?php esc_html_e('Verifying...', 'vapt-security'); ?>');
                    $.post(vaptAjaxUrl, {
                        action: 'vapt_verify_otp',
                        otp: $('#vapt-otp-input').val()
                    }, function(r) {
                        if (r.success) location.reload();
                        else {
                            btn.prop('disabled', false).text('<?php esc_html_e('Verify', 'vapt-security'); ?>');
                            $('#vapt-otp-message').html('<span style="color:red">' + r.data.message + '</span>');
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).text('<?php esc_html_e('Verify', 'vapt-security'); ?>');
                        $('#vapt-otp-message').html('<span style="color:red"><?php esc_html_e('Network error.', 'vapt-security'); ?></span>');
                    });
                });

                // Save Features
                $('#vapt-domain-features-form').on('submit', function(e) {
                    e.preventDefault();
                    $('#vapt-save-features').click();
                });

                $('#vapt-save-features').click(function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'vapt-security'); ?>');

                    var data = $('#vapt-domain-features-form').serialize();
                    $.post(vaptAjaxUrl, {
                        action: 'vapt_save_domain_features',
                        data: data
                    }, function(r) {
                        btn.prop('disabled', false).html(originalText);
                        if (r.success) $('#vapt-features-msg').html('<span style="color:green">' + r.data.message + '</span>');
                        else $('#vapt-features-msg').html('<span style="color:red">' + r.data.message + '</span>');

                        // Clear message after 3 seconds
                        setTimeout(function() {
                            $('#vapt-features-msg').fadeOut(function() {
                                $(this).html('').show();
                            });
                        }, 3000);
                    }).fail(function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#vapt-features-msg').html('<span style="color:red"><?php esc_html_e('Error saving features.', 'vapt-security'); ?></span>');
                    });
                });

                // License (Reuse endpoints)

                // Immediate Frontend Update
                var futureExpiries = <?php echo json_encode($future_expiries); ?>;

                function toggleRenewButton() {
                    var isChecked = $('#vapt-license-auto-renew').is(':checked');
                    $('#vapt-renew-license').prop('disabled', !isChecked);
                }

                // Initial state
                toggleRenewButton();

                $('#vapt-license-auto-renew').change(function() {
                    toggleRenewButton();
                });

                $('#vapt-license-type').change(function() {
                    var type = $(this).val();
                    if (futureExpiries[type]) {
                        $('#vapt-license-expiry').val(futureExpiries[type]);
                    }

                    // Developer Constraint
                    if (type === 'developer') {
                        $('#vapt-license-auto-renew').prop('checked', false).prop('disabled', true);
                    } else {
                        $('#vapt-license-auto-renew').prop('disabled', false);
                    }
                    toggleRenewButton();
                });

                $('#vapt-update-license').click(function() {
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'vapt-security'); ?>');

                    $.post(vaptAjaxUrl, {
                        action: 'vapt_update_license',
                        type: $('#vapt-license-type').val(),
                        auto_renew: $('#vapt-license-auto-renew').is(':checked') ? 1 : 0
                    }, function(r) {
                        btn.prop('disabled', false).html(originalText);
                        if (r.success) {
                            $('#vapt-license-msg').html('<span style="color:green">' + r.data.message + '</span>');
                            if (r.data.expires_formatted) {
                                $('#vapt-license-expiry').val(r.data.expires_formatted);
                                $('#vapt-current-expiry').text(r.data.expires_formatted);
                            }
                            if (r.data.type) $('#vapt-current-license-type').text(r.data.type);
                        } else $('#vapt-license-msg').html('<span style="color:red">' + r.data.message + '</span>');

                        setTimeout(function() {
                            $('#vapt-license-msg').fadeOut(function() {
                                $(this).html('').show();
                            });
                        }, 3000);
                    }).fail(function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#vapt-license-msg').html('<span style="color:red"><?php esc_html_e('Error updating license.', 'vapt-security'); ?></span>');
                    });
                });
                // Locked Config Generator
                $('#vapt-generate-locked-config').click(function(e) {
                    e.preventDefault();
                    if (!confirm('<?php esc_html_e('Are you sure?\\n\\nThis will overwrite the vapt-locked-config.php file on THIS server and lock the plugin to the specified domain.', 'vapt-security'); ?>')) {
                        return;
                    }

                    var btn = $(this);
                    var originalText = btn.html();
                    btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'vapt-security'); ?>');

                    $.post(vaptAjaxUrl, {
                        action: 'vapt_generate_locked_config',
                        domain: $('#vapt-lock-domain').val(),
                        include_settings: $('#vapt-lock-include-settings').is(':checked') ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce("vapt_locked_config"); ?>'
                    }, function(r) {
                        if (r.success) {
                            $('#vapt-generate-msg').html('<span style="color:green">' + r.data.message + '</span>');
                            // Reload to show updated build info and verify sync
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            $('#vapt-generate-msg').html('<span style="color:red">' + r.data.message + '</span>');
                            btn.prop('disabled', false).html(originalText);
                        }
                    }).fail(function(xhr) {
                        var msg = 'Error: ' + xhr.status + ' ' + xhr.statusText;
                        if (xhr.status === 0) {
                            msg = 'Connection Blocked. Check console for CORS or Network errors.';
                        }
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            msg = xhr.responseJSON.data.message;
                        }
                        $('#vapt-generate-msg').html('<span style="color:red">' + msg + '</span>');
                        btn.prop('disabled', false).html(originalText);
                    });
                });

                // Client Zip Generator
                $('#vapt-generate-client-zip').click(function() {
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.prop('disabled', true).text('<?php esc_html_e('Building Zip...', 'vapt-security'); ?>');

                    $.post(vaptAjaxUrl, {
                        action: 'vapt_generate_client_zip',
                        domain: $('#vapt-lock-domain').val(),
                        plugin_name: $('#vapt-build-plugin-name').val(),
                        author_name: $('#vapt-build-author-name').val(),
                        include_settings: $('#vapt-lock-include-settings').is(':checked') ? 1 : 0,
                        nonce: '<?php echo wp_create_nonce("vapt_locked_config"); ?>'
                    }, function(r) {
                        btn.prop('disabled', false).html(originalText);
                        if (r.success) {
                            $('#vapt-generate-msg').html('<span style="color:green"><?php esc_html_e('Zip generated!', 'vapt-security'); ?></span>');
                            // Download
                            var byteCharacters = atob(r.data.base64);
                            var byteNumbers = new Array(byteCharacters.length);
                            for (var i = 0; i < byteCharacters.length; i++) {
                                byteNumbers[i] = byteCharacters.charCodeAt(i);
                            }
                            var byteArray = new Uint8Array(byteNumbers);
                            var blob = new Blob([byteArray], {
                                type: "application/zip"
                            });

                            var link = document.createElement('a');
                            link.href = window.URL.createObjectURL(blob);
                            link.download = r.data.filename;
                            link.click();
                        } else {
                            $('#vapt-generate-msg').html('<span style="color:red">' + r.data.message + '</span>');
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#vapt-generate-msg').html('<span style="color:red"><?php esc_html_e('Error generating zip.', 'vapt-security'); ?></span>');
                    });
                });

                // Re-import Config
                $('#vapt-reimport-config').click(function() {
                    var btn = $(this);
                    var originalText = btn.html();
                    btn.prop('disabled', true).text('<?php esc_html_e('Importing...', 'vapt-security'); ?>');
                    $.post(vaptAjaxUrl, {
                        action: 'vapt_reimport_config',
                        nonce: '<?php echo wp_create_nonce("vapt_locked_config"); ?>' // Reuse same context nonce or create new one? Locked config nonce fits.
                    }, function(r) {
                        btn.prop('disabled', false).html(originalText);
                        if (r.success) {
                            $('#vapt-reimport-msg').html('<span style="color:green">' + r.data.message + '</span>');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            $('#vapt-reimport-msg').html('<span style="color:red">' + r.data.message + '</span>');
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).html(originalText);
                        $('#vapt-reimport-msg').html('<span style="color:red"><?php esc_html_e('Error importing config.', 'vapt-security'); ?></span>');
                    });
                });
            });
        </script>

        <style>
            .vapt-domain-tab-content {
                padding: 20px;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-top: none;
            }

            .vapt-feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }

            .vapt-feature-item {
                background: #fdfdfd;
                border: 1px solid #ccd0d4;
                padding: 15px;
                border-radius: 6px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .02);
            }

            .vapt-feature-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f0f0f1;
            }

            .vapt-feature-header h4 {
                margin: 0;
                font-size: 1em;
                color: #1d2327;
            }
        </style>
        <?php
        // Close the wrapper div from line 51 if not closed elsewhere, but looking at file it seems closed.
        ?>