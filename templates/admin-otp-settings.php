<?php
// Check for transient authentication
$user_id = get_current_user_id();
$is_verified = get_transient( 'vapt_auth_' . $user_id );

// License Data
$license = VAPT_License::get_license();
$license_type = $license['type'] ?? 'standard';
$license_expires = $license['expires'] ?? 0;
$license_auto_renew = $license['auto_renew'] ?? false;
$expiry_date = $license_expires ? date_i18n( get_option( 'date_format' ), $license_expires ) : __( 'Never', 'vapt-security' );

?>
<div class="wrap">
    <h1><?php esc_html_e( 'VAPT Security - Admin Settings', 'vapt-security' ); ?></h1>

    <?php if ( ! $is_verified ) : ?>
        <!-- OTP Verification Form -->
        <div class="vapt-otp-container card" style="max-width: 400px; margin-top: 20px; padding: 20px;">
            <h2><?php esc_html_e( 'Authentication Required', 'vapt-security' ); ?></h2>
            <p><?php esc_html_e( 'Please verify your identity to access configuration settings.', 'vapt-security' ); ?></p>
            
            <div id="vapt-otp-step-1">
                <button type="button" id="vapt-send-otp" class="button button-primary button-hero">
                    <?php esc_html_e( 'Send OTP', 'vapt-security' ); ?>
                </button>
                <p class="description" style="margin-top: 10px;">
                    <?php esc_html_e( 'A 6-digit code will be sent to your registered email address.', 'vapt-security' ); ?>
                </p>
            </div>

            <div id="vapt-otp-step-2" style="display:none; margin-top: 20px;">
                <p>
                    <input type="text" id="vapt-otp-input" class="regular-text" placeholder="Enter 6-digit OTP" maxlength="6" style="width: 100%; font-size: 1.2em; text-align: center; letter-spacing: 5px;" />
                </p>
                <p>
                    <button type="button" id="vapt-verify-otp" class="button button-primary button-hero" style="width: 100%;">
                        <?php esc_html_e( 'Verify OTP', 'vapt-security' ); ?>
                    </button>
                </p>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="#" id="vapt-resend-otp"><?php esc_html_e( 'Resend OTP', 'vapt-security' ); ?></a>
                    <span id="vapt-timer" style="margin-left: 10px; color: #666;">(120s)</span>
                </div>
            </div>

            <div id="vapt-otp-message" style="margin-top: 15px;"></div>
        </div>

    <?php else : ?>
        <!-- Verified - Show Settings -->
        <div id="vapt-settings-container">
            
            <!-- License Management -->
            <div class="card" style="margin-bottom: 20px; padding: 20px;">
                <h2><?php esc_html_e( 'License Management', 'vapt-security' ); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'License Type', 'vapt-security' ); ?></th>
                        <td>
                            <select id="vapt-license-type">
                                <option value="standard" <?php selected( $license_type, 'standard' ); ?>>Standard (30 Days)</option>
                                <option value="pro" <?php selected( $license_type, 'pro' ); ?>>Pro (1 Year)</option>
                                <option value="developer" <?php selected( $license_type, 'developer' ); ?>>Developer (Lifetime)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Expires On', 'vapt-security' ); ?></th>
                        <td>
                            <input type="text" id="vapt-license-expiry" value="<?php echo esc_attr( $expiry_date ); ?>" class="regular-text" readonly />
                            <p class="description"><?php esc_html_e( 'Expiry date adjusts automatically based on type/renewal.', 'vapt-security' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Auto Renew', 'vapt-security' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="vapt-license-autorenew" <?php checked( $license_auto_renew ); ?> />
                                <?php esc_html_e( 'Enable Auto-Renew', 'vapt-security' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                         <th scope="row"></th>
                         <td>
                             <button type="button" id="vapt-update-license" class="button button-secondary"><?php esc_html_e( 'Update License', 'vapt-security' ); ?></button>
                             <button type="button" id="vapt-renew-license" class="button button-secondary"><?php esc_html_e( 'Renew Now', 'vapt-security' ); ?></button>
                             <span id="vapt-license-msg" style="margin-left: 10px;"></span>
                         </td>
                    </tr>
                </table>
            </div>

            <!-- Settings Tabs -->
            <div id="vapt-tabs">
                <ul>
                    <li><a href="#tab-general"><?php esc_html_e( 'General', 'vapt-security' ); ?></a></li>
                    <?php if ( VAPT_FEATURE_RATE_LIMITING ) : ?>
                    <li><a href="#tab-rate-limiter"><?php esc_html_e( 'Rate Limiter', 'vapt-security' ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( VAPT_FEATURE_INPUT_VALIDATION ) : ?>
                    <li><a href="#tab-input-validation"><?php esc_html_e( 'Input Validation', 'vapt-security' ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( VAPT_FEATURE_WP_CRON_PROTECTION ) : ?>
                    <li><a href="#tab-cron"><?php esc_html_e( 'WP‑Cron Protection', 'vapt-security' ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( VAPT_FEATURE_SECURITY_LOGGING ) : ?>
                    <li><a href="#tab-logging"><?php esc_html_e( 'Security Logging', 'vapt-security' ); ?></a></li>
                    <?php endif; ?>
                </ul>

                <form method="post" action="options.php">
                    <?php settings_fields( 'vapt_security_options_group' ); ?>
                    
                    <div id="tab-general">        <?php do_settings_sections( 'vapt_security_general' );        ?></div>
                    
                    <?php if ( VAPT_FEATURE_RATE_LIMITING ) : ?>
                    <div id="tab-rate-limiter">   <?php do_settings_sections( 'vapt_security_rate_limiter' );   ?></div>
                    <?php endif; ?>
                    
                    <?php if ( VAPT_FEATURE_INPUT_VALIDATION ) : ?>
                    <div id="tab-input-validation"><?php do_settings_sections( 'vapt_security_validation' ); ?></div>
                    <?php endif; ?>
                    
                    <?php if ( VAPT_FEATURE_WP_CRON_PROTECTION ) : ?>
                    <div id="tab-cron">          <?php do_settings_sections( 'vapt_security_cron' );          ?></div>
                    <?php endif; ?>
                    
                    <?php if ( VAPT_FEATURE_SECURITY_LOGGING ) : ?>
                    <div id="tab-logging">       <?php do_settings_sections( 'vapt_security_logging' );       ?></div>
                    <?php endif; ?>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tabs
    if ($('#vapt-tabs').length) {
        $('#vapt-tabs').tabs();
    }

    // OTP Flow
    $('#vapt-send-otp, #vapt-resend-otp').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'vapt_send_otp'
        }, function(res) {
            $btn.prop('disabled', false);
            if (res.success) {
                $('#vapt-otp-step-1').slideUp();
                $('#vapt-otp-step-2').slideDown();
                $('#vapt-otp-message').html('<span style="color:green">' + res.data.message + '</span>');
                startTimer(120);
            } else {
                $('#vapt-otp-message').html('<span style="color:red">' + res.data.message + '</span>');
            }
        });
    });

    $('#vapt-verify-otp').on('click', function() {
        var otp = $('#vapt-otp-input').val();
        if (!otp) return;
        
        var $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'vapt_verify_otp',
            otp: otp
        }, function(res) {
            $btn.prop('disabled', false);
            if (res.success) {
                location.reload(); // Reload to show settings
            } else {
                $('#vapt-otp-message').html('<span style="color:red">' + res.data.message + '</span>');
            }
        });
    });

    // Timer
    function startTimer(duration) {
        var timer = duration, minutes, seconds;
        var display = $('#vapt-timer');
        var interval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.text("(" + minutes + ":" + seconds + ")");

            if (--timer < 0) {
                clearInterval(interval);
                display.text("(Expired)");
            }
        }, 1000);
    }
    
    // License Management
    $('#vapt-update-license').on('click', function() {
        var type = $('#vapt-license-type').val();
        var auto_renew = $('#vapt-license-autorenew').is(':checked') ? 1 : 0;
        
        $.post(ajaxurl, {
            action: 'vapt_update_license',
            type: type,
            auto_renew: auto_renew
        }, function(res) {
             if (res.success) {
                 $('#vapt-license-msg').html('<span style="color:green">' + res.data.message + '</span>');
                 // Update expiry display if returned?
                 if(res.data.expires_formatted) {
                     $('#vapt-license-expiry').val(res.data.expires_formatted);
                 }
             } else {
                 $('#vapt-license-msg').html('<span style="color:red">' + res.data.message + '</span>');
             }
        });
    });
    
    $('#vapt-renew-license').on('click', function() {
        $.post(ajaxurl, {
            action: 'vapt_renew_license'
        }, function(res) {
             if (res.success) {
                 $('#vapt-license-msg').html('<span style="color:green">' + res.data.message + '</span>');
                 if(res.data.expires_formatted) {
                     $('#vapt-license-expiry').val(res.data.expires_formatted);
                 }
             } else {
                 $('#vapt-license-msg').html('<span style="color:red">' + res.data.message + '</span>');
             }
        });
    });
});
</script>
