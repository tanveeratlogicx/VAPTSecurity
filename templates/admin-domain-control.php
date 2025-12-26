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
                <h2><?php esc_html_e('License Management', 'vapt-security'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('License Type', 'vapt-security'); ?></th>
                        <td>
                            <select id="vapt-license-type">
                                <option value="standard" <?php selected($license_type, 'standard'); ?>>Standard</option>
                                <option value="pro" <?php selected($license_type, 'pro'); ?>>Pro</option>
                                <option value="developer" <?php selected($license_type, 'developer'); ?>>Developer</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Expiry', 'vapt-security'); ?></th>
                        <td><input type="text" id="vapt-license-expiry" value="<?php echo esc_attr($expiry_date); ?>" readonly class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto Renewal', 'vapt-security'); ?></th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" id="vapt-license-auto-renew" <?php checked($license_auto_renew); ?>>
                                <span class="slider round"></span>
                            </label>
                            <p class="description"><?php esc_html_e('Extend license automatically upon expiry.', 'vapt-security'); ?></p>
                        </td>
                    </tr>
                    <?php if (isset($license['renewal_count'])) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Terms Renewed', 'vapt-security'); ?></th>
                            <td>
                                <span class="badge" style="background: #2271b1; color: white; padding: 5px 10px; border-radius: 10px;">
                                    <?php echo esc_html($license['renewal_count']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" id="vapt-update-license" class="button button-secondary"><?php esc_html_e('Update License', 'vapt-security'); ?></button>
                            <button type="button" id="vapt-renew-license" class="button button-secondary"><?php esc_html_e('Renew', 'vapt-security'); ?></button>
                            <span id="vapt-license-msg" style="margin-left: 10px;"></span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Tab 2: Domain Features -->
            <div id="tab-features" class="vapt-domain-tab-content">
                <h2><?php esc_html_e('Domain Features', 'vapt-security'); ?></h2>
                <p class="description"><?php esc_html_e('Enable or disable features for this domain. Disabled features are hidden from Admins.', 'vapt-security'); ?></p>

                <form id="vapt-domain-features-form">
                    <?php if (count($all_features) > 5) : ?>
                        <!-- Grid Layout for many features -->
                        <div class="vapt-feature-grid">
                            <?php foreach ($all_features as $slug => $default) : ?>
                                <div class="vapt-feature-item">
                                    <div class="vapt-feature-header">
                                        <h4><?php echo esc_html(ucwords(str_replace('_', ' ', $slug))); ?></h4>
                                        <label class="switch">
                                            <input type="checkbox" name="features[<?php echo esc_attr($slug); ?>]" value="1" <?php checked(VAPT_Features::is_enabled($slug)); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </div>
                                    <?php if (isset($feature_descriptions[$slug])) : ?>
                                        <p class="description" style="margin: 5px 0 0; font-size: 12px;"><?php echo esc_html($feature_descriptions[$slug]); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <!-- Standard Table for few features -->
                        <table class="form-table">
                            <?php foreach ($all_features as $slug => $default) : ?>
                                <tr>
                                    <th scope="row" style="padding: 10px 0;">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $slug))); ?>
                                        <?php if (isset($feature_descriptions[$slug])) : ?>
                                            <p class="description" style="font-weight: 400; margin: 5px 0 0;"><?php echo esc_html($feature_descriptions[$slug]); ?></p>
                                        <?php endif; ?>
                                    </th>
                                    <td style="padding: 10px 0; vertical-align: top;">
                                        <label class="switch">
                                            <input type="checkbox" name="features[<?php echo esc_attr($slug); ?>]" value="1" <?php checked(VAPT_Features::is_enabled($slug)); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>

                    <p>
                        <button type="button" id="vapt-save-features" class="button button-primary"><?php esc_html_e('Save Domain Features', 'vapt-security'); ?></button>
                        <span id="vapt-features-msg" style="margin-left: 10px;"></span>
                    </p>
                </form>
            </div>

            <!-- Tab 3: Build Generator -->
            <div id="tab-build" class="vapt-domain-tab-content">
                <h2><?php esc_html_e('Locked Configuration Generator', 'vapt-security'); ?></h2>
                <p class="description"><?php esc_html_e('Generate a portable configuration file locked to a specific domain pattern.', 'vapt-security'); ?></p>

                <!-- Build Info Card -->
                <div class="card" style="padding: 15px; margin-bottom: 20px; background: #f0f0f1; border: 1px solid #ccd0d4;">
                    <h3 style="margin-top:0;">Current Build Information</h3>
                    <p>
                        <strong>Generated Version:</strong> <?php echo esc_html($build_info['version'] ?? 'N/A'); ?><br>
                        <strong>Generated At:</strong> <?php echo esc_html($build_ver); ?><br>
                        <strong>Locked Domain:</strong> <code><?php echo esc_html($build_domain); ?></code><br>
                        <strong>Imported At:</strong> <?php echo esc_html(isset($build_info['imported_at']) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $build_info['imported_at']) : 'N/A'); ?>
                    </p>
                    <button type="button" id="vapt-reimport-config" class="button button-secondary">
                        <?php esc_html_e('Force Re-import from Server File', 'vapt-security'); ?>
                    </button>
                    <span id="vapt-reimport-msg" style="margin-left: 10px;"></span>
                    <p class="description" style="margin-top:5px; font-size:12px;">
                        Forces an import from <code>vapt-locked-config.php</code> (or .imported) regardless of domain match. Use this to verify configurations on a test environment.
                    </p>
                </div>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Target Domain Pattern', 'vapt-security'); ?></th>
                        <td>
                            <input type="text" id="vapt-lock-domain" class="regular-text" placeholder="*.example.com" value="<?php echo esc_attr($_SERVER['HTTP_HOST']); ?>">
                            <p class="description"><?php esc_html_e('Use * for wildcards (e.g., *.example.com matches staging.example.com).', 'vapt-security'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Include Current Settings', 'vapt-security'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="vapt-lock-include-settings" checked>
                                <?php esc_html_e('Export current plugin configuration', 'vapt-security'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Custom Plugin Name', 'vapt-security'); ?></th>
                        <td>
                            <input type="text" id="vapt-build-plugin-name" class="regular-text" placeholder="VAPT Security">
                            <p class="description"><?php esc_html_e('Leave empty to use default. Changes the "Plugin Name" header in the built zip.', 'vapt-security'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Custom Author Name', 'vapt-security'); ?></th>
                        <td>
                            <input type="text" id="vapt-build-author-name" class="regular-text" placeholder="Tanveer Malik">
                            <p class="description"><?php esc_html_e('Leave empty to use default. Changes the "Author" header in the built zip.', 'vapt-security'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button type="button" id="vapt-generate-client-zip" class="button button-primary">
                                    <span class="dashicons dashicons-download" style="line-height: 1.3;"></span>
                                    <?php esc_html_e('Download Client Zip', 'vapt-security'); ?>
                                </button>
                                <button type="button" id="vapt-generate-locked-config" class="button button-secondary">
                                    <span class="dashicons dashicons-upload" style="line-height: 1.3;"></span>
                                    <?php esc_html_e('Save Config to Server', 'vapt-security'); ?>
                                </button>
                            </div>

                            <div id="vapt-generate-msg" style="margin-top: 10px;"></div>

                            <p class="description" style="margin-top: 15px;">
                                <strong><?php esc_html_e('Download Client Zip', 'vapt-security'); ?>:</strong> <?php esc_html_e('Generates a portable plugin zip locked to the target domain.', 'vapt-security'); ?><br>
                                <strong><?php esc_html_e('Save Config to Server', 'vapt-security'); ?>:</strong> <?php esc_html_e('Writes vapt-locked-config.php to THIS server. Use to lock this environment.', 'vapt-security'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
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
                    },
                    create: function(event, ui) {
                        // Restore active tab logic if desired
                    }
                });
                // Force show first tab content if tabs didn't fire (fallback)
                // But tabs() usually sets display style.
            } catch (e) {
                console.error('VAPT Tabs Error:', e);
                // Fallback: show all or first
                $('.vapt-domain-tab-content').show();
            }
        }

        // --- Original Logic Preserved Below ---

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
            $(this).prop('disabled', true);

            $.post(ajaxurl, {
                action: 'vapt_send_otp'
            }, function(r) {
                // Re-enable (for send btn)
                $('#vapt-send-otp').prop('disabled', false);

                if (r.success) {
                    $('#vapt-otp-step-1').hide();
                    $('#vapt-otp-step-2').show();
                    $('#vapt-otp-message').html('<span style="color:green">' + r.data.message + '</span>');
                    startOtpTimer();
                } else {
                    $('#vapt-otp-message').html('<span style="color:red">' + r.data.message + '</span>');
                }
            });
        });

        $('#vapt-verify-otp').click(function() {
            $.post(ajaxurl, {
                action: 'vapt_verify_otp',
                otp: $('#vapt-otp-input').val()
            }, function(r) {
                if (r.success) location.reload();
                else $('#vapt-otp-message').html('<span style="color:red">' + r.data.message + '</span>');
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
            var originalText = btn.text();
            btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'vapt-security'); ?>');

            var data = $('#vapt-domain-features-form').serialize();
            $.post(ajaxurl, {
                action: 'vapt_save_domain_features',
                data: data
            }, function(r) {
                btn.prop('disabled', false).text(originalText);
                if (r.success) $('#vapt-features-msg').html('<span style="color:green">' + r.data.message + '</span>');
                else $('#vapt-features-msg').html('<span style="color:red">' + r.data.message + '</span>');

                // Clear message after 3 seconds
                setTimeout(function() {
                    $('#vapt-features-msg').fadeOut(function() {
                        $(this).html('').show();
                    });
                }, 3000);
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
            var originalText = btn.text();
            btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'vapt-security'); ?>');

            $.post(ajaxurl, {
                action: 'vapt_update_license',
                type: $('#vapt-license-type').val(),
                auto_renew: $('#vapt-license-auto-renew').is(':checked') ? 1 : 0
            }, function(r) {
                btn.prop('disabled', false).text(originalText);
                if (r.success) {
                    $('#vapt-license-msg').html('<span style="color:green">' + r.data.message + '</span>');
                    if (r.data.expires_formatted) $('#vapt-license-expiry').val(r.data.expires_formatted);
                } else $('#vapt-license-msg').html('<span style="color:red">' + r.data.message + '</span>');

                setTimeout(function() {
                    $('#vapt-license-msg').fadeOut(function() {
                        $(this).html('').show();
                    });
                }, 3000);
            });
        });
        // Locked Config Generator
        $('#vapt-generate-locked-config').click(function(e) {
            e.preventDefault();
            if (!confirm('<?php esc_html_e('Are you sure?\\n\\nThis will overwrite the vapt-locked-config.php file on THIS server and lock the plugin to the specified domain.', 'vapt-security'); ?>')) {
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'vapt-security'); ?>');

            // Dynamic AJAX URL fix for port mismatches (e.g. localhost:10017 vs localhost)
            var currentAjaxUrl = ajaxurl;
            try {
                var url = new URL(ajaxurl);
                if (url.origin !== window.location.origin) {
                    currentAjaxUrl = window.location.origin + url.pathname + url.search;
                }
            } catch (e) {
                // Warning: invalid URL, fallback to default
            }

            $.post(currentAjaxUrl, {
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
                    btn.prop('disabled', false).text('<?php esc_html_e('Save Config to Server', 'vapt-security'); ?>');
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
                btn.prop('disabled', false).text('<?php esc_html_e('Save Config to Server', 'vapt-security'); ?>');
            });
        });

        // Client Zip Generator
        $('#vapt-generate-client-zip').click(function() {
            var btn = $(this);
            var originalText = btn.text();
            btn.prop('disabled', true).text('<?php esc_html_e('Building Zip...', 'vapt-security'); ?>');

            $.post(ajaxurl, {
                action: 'vapt_generate_client_zip',
                domain: $('#vapt-lock-domain').val(),
                plugin_name: $('#vapt-build-plugin-name').val(),
                author_name: $('#vapt-build-author-name').val(),
                include_settings: $('#vapt-lock-include-settings').is(':checked') ? 1 : 0,
                nonce: '<?php echo wp_create_nonce("vapt_locked_config"); ?>'
            }, function(r) {
                btn.prop('disabled', false).text(originalText);
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
            });
        });

        // Re-import Config
        $('#vapt-reimport-config').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).text('<?php esc_html_e('Importing...', 'vapt-security'); ?>');
            $.post(ajaxurl, {
                action: 'vapt_reimport_config',
                nonce: '<?php echo wp_create_nonce("vapt_locked_config"); ?>' // Reuse same context nonce or create new one? Locked config nonce fits.
            }, function(r) {
                btn.prop('disabled', false).text('<?php esc_html_e('Force Re-import from Server File', 'vapt-security'); ?>');
                if (r.success) {
                    $('#vapt-reimport-msg').html('<span style="color:green">' + r.data.message + '</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#vapt-reimport-msg').html('<span style="color:red">' + r.data.message + '</span>');
                }
            });
        });
    });
</script>