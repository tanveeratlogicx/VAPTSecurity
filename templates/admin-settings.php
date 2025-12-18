<?php
/**
 * Settings page markup with modern horizontal tabs.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check for Superadmin
$current_user = wp_get_current_user();
$is_superadmin = ( $current_user->user_login === 'tanmalik786' && $current_user->user_email === 'tanmalik786@gmail.com' );
$is_verified_super = $is_superadmin ? get_transient( 'vapt_auth_' . $current_user->ID ) : false;
?>
<div class="wrap">
    <h1><?php esc_html_e( 'VAPT Security Settings', 'vapt-security' ); ?></h1>

    <form method="post" action="options.php">
        <?php
        // Output security fields.
        settings_fields( 'vapt_security_options_group' );
        ?>

        <!-- Modern Horizontal Tabs -->
        <div id="vapt-security-tabs">
            <!-- Tab Navigation -->
            <ul class="vapt-security-tabs">
                <li class="vapt-security-tab"><a href="#tab-general"><?php esc_html_e( 'General', 'vapt-security' ); ?></a></li>
                <?php if ( VAPT_Features::is_enabled( 'rate_limiting' ) && defined( 'VAPT_FEATURE_RATE_LIMITING' ) && VAPT_FEATURE_RATE_LIMITING ) : ?>
                <li class="vapt-security-tab"><a href="#tab-rate-limiter"><?php esc_html_e( 'Rate Limiter', 'vapt-security' ); ?></a></li>
                <?php endif; ?>
                <?php if ( VAPT_Features::is_enabled( 'input_validation' ) && defined( 'VAPT_FEATURE_INPUT_VALIDATION' ) && VAPT_FEATURE_INPUT_VALIDATION ) : ?>
                <li class="vapt-security-tab"><a href="#tab-validation"><?php esc_html_e( 'Input Validation', 'vapt-security' ); ?></a></li>
                <?php endif; ?>
                <?php if ( VAPT_Features::is_enabled( 'cron_protection' ) && defined( 'VAPT_FEATURE_WP_CRON_PROTECTION' ) && VAPT_FEATURE_WP_CRON_PROTECTION ) : ?>
                <li class="vapt-security-tab"><a href="#tab-cron"><?php esc_html_e( 'WP‑Cron Protection', 'vapt-security' ); ?></a></li>
                <?php endif; ?>
                <?php if ( VAPT_Features::is_enabled( 'security_logging' ) && defined( 'VAPT_FEATURE_SECURITY_LOGGING' ) && VAPT_FEATURE_SECURITY_LOGGING ) : ?>
                <li class="vapt-security-tab"><a href="#tab-logging"><?php esc_html_e( 'Security Logging', 'vapt-security' ); ?></a></li>
                <?php endif; ?>
                <li class="vapt-security-tab"><a href="#tab-stats"><?php esc_html_e( 'Statistics', 'vapt-security' ); ?></a></li>
                <?php if ( $is_superadmin ) : ?>
                <li class="vapt-security-tab"><a href="#tab-domain-admin"><?php esc_html_e( 'Domain Admin', 'vapt-security' ); ?></a></li>
                <?php endif; ?>
            </ul>

            <!-- Tab Content -->
            <div id="tab-general" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php do_settings_sections( 'vapt_security_general' ); ?>
                </div>
            </div>

            <?php if ( VAPT_Features::is_enabled( 'rate_limiting' ) && defined( 'VAPT_FEATURE_RATE_LIMITING' ) && VAPT_FEATURE_RATE_LIMITING ) : ?>
            <div id="tab-rate-limiter" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php do_settings_sections( 'vapt_security_rate_limiter' ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( VAPT_Features::is_enabled( 'input_validation' ) && defined( 'VAPT_FEATURE_INPUT_VALIDATION' ) && VAPT_FEATURE_INPUT_VALIDATION ) : ?>
            <div id="tab-validation" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php do_settings_sections( 'vapt_security_validation' ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( VAPT_Features::is_enabled( 'cron_protection' ) && defined( 'VAPT_FEATURE_WP_CRON_PROTECTION' ) && VAPT_FEATURE_WP_CRON_PROTECTION ) : ?>
            <div id="tab-cron" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php do_settings_sections( 'vapt_security_cron' ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( VAPT_Features::is_enabled( 'security_logging' ) && defined( 'VAPT_FEATURE_SECURITY_LOGGING' ) && VAPT_FEATURE_SECURITY_LOGGING ) : ?>
            <div id="tab-logging" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php do_settings_sections( 'vapt_security_logging' ); ?>
                    
                    <?php
                    // Display log statistics
                    $logger = new VAPT_Security_Logger();
                    $stats = $logger->get_statistics();
                    ?>
                    <h3><?php esc_html_e( 'Logging Statistics', 'vapt-security' ); ?></h3>
                    <table class="statistics-table">
                        <tr>
                            <td><?php esc_html_e( 'Total Events Logged:', 'vapt-security' ); ?></td>
                            <td><?php echo esc_html( $stats['total_events'] ); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e( 'Events in Last 24 Hours:', 'vapt-security' ); ?></td>
                            <td><?php echo esc_html( $stats['last_24_hours'] ); ?></td>
                        </tr>
                    </table>

                    <?php if ( ! empty( $stats['event_types'] ) ) : ?>
                    <h4><?php esc_html_e( 'Event Types', 'vapt-security' ); ?></h4>
                    <table class="statistics-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Event Type', 'vapt-security' ); ?></th>
                                <th><?php esc_html_e( 'Count', 'vapt-security' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $stats['event_types'] as $type => $count ) : ?>
                            <tr>
                                <td><?php echo esc_html( $type ); ?></td>
                                <td><?php echo esc_html( $count ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if ( ! empty( $stats['top_ips'] ) ) : ?>
                    <h4><?php esc_html_e( 'Top IPs', 'vapt-security' ); ?></h4>
                    <table class="statistics-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'IP Address', 'vapt-security' ); ?></th>
                                <th><?php esc_html_e( 'Event Count', 'vapt-security' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $stats['top_ips'] as $ip => $count ) : ?>
                            <tr>
                                <td><?php echo esc_html( $ip ); ?></td>
                                <td><?php echo esc_html( $count ); ?></td>
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
                    <h3><?php esc_html_e( 'Rate Limiting Statistics', 'vapt-security' ); ?></h3>
                    
                    <h4><?php esc_html_e( 'Regular Request Statistics', 'vapt-security' ); ?></h4>
                    <table class="statistics-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'IP Address', 'vapt-security' ); ?></th>
                                <th><?php esc_html_e( 'Request Count', 'vapt-security' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'vapt-security' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $limiter_stats['regular_requests'] as $ip => $requests ) : ?>
                            <tr>
                                <td><?php echo esc_html( $ip ); ?></td>
                                <td><?php echo esc_html( count( $requests ) ); ?></td>
                                <td>
                                    <button type="button" class="button vapt-reset-ip" data-ip="<?php echo esc_attr( $ip ); ?>">
                                        <?php esc_html_e( 'Reset Data', 'vapt-security' ); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h4><?php esc_html_e( 'Cron Request Statistics', 'vapt-security' ); ?></h4>
                    <table class="statistics-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'IP Address', 'vapt-security' ); ?></th>
                                <th><?php esc_html_e( 'Request Count', 'vapt-security' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'vapt-security' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $limiter_stats['cron_requests'] as $ip => $requests ) : ?>
                            <tr>
                                <td><?php echo esc_html( $ip ); ?></td>
                                <td><?php echo esc_html( count( $requests ) ); ?></td>
                                <td>
                                    <button type="button" class="button vapt-reset-ip" data-ip="<?php echo esc_attr( $ip ); ?>">
                                        <?php esc_html_e( 'Reset Data', 'vapt-security' ); ?>
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
                            var confirmReset = confirm('<?php esc_html_e( 'Are you sure you want to reset data for IP:', 'vapt-security' ); ?> ' + ip);
                            
                            if (confirmReset) {
                                // In a real implementation, this would make an AJAX call to reset the IP data
                                alert('<?php esc_html_e( 'In a full implementation, this would reset data for IP: ', 'vapt-security' ); ?>' + ip);
                            }
                        });
                    });
                    </script>
                </div>
            </div>

            <?php if ( $is_superadmin ) : ?>
            <div id="tab-domain-admin" class="vapt-security-tab-content">
                <div class="settings-section">
                    <?php if ( $is_verified_super ) : ?>
                        <div class="card" style="max-width: 500px; padding: 20px; border-left: 4px solid #46b450;">
                            <h3><?php esc_html_e( 'Domain Admin Verified', 'vapt-security' ); ?></h3>
                            <p><?php esc_html_e( 'You have successfully verified your identity.', 'vapt-security' ); ?></p>
                            <p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=vapt-domain-control' ) ); ?>" class="button button-primary button-hero" target="_blank">
                                    <?php esc_html_e( 'Manage Domain Features', 'vapt-security' ); ?>
                                    <span class="dashicons dashicons-external" style="margin-top: 5px;"></span>
                                </a>
                            </p>
                        </div>
                    <?php else : ?>
                        <!-- Integrated OTP Form -->
                        <div class="card" style="max-width: 400px; padding: 20px;">
                            <h3><?php esc_html_e( 'Admin Authentication', 'vapt-security' ); ?></h3>
                            <p><?php esc_html_e( 'Verify your credentials to access domain-level controls.', 'vapt-security' ); ?></p>
                            
                            <div id="vapt-sa-otp-step-1">
                                <button type="button" id="vapt-sa-send-otp" class="button button-primary">
                                    <?php esc_html_e( 'Send Verification Code', 'vapt-security' ); ?>
                                </button>
                            </div>
                            
                            <div id="vapt-sa-otp-step-2" style="display:none; margin-top: 15px;">
                                <input type="text" id="vapt-sa-otp-input" class="regular-text" placeholder="------" maxlength="6" style="text-align: center; letter-spacing: 3px;" />
                                <button type="button" id="vapt-sa-verify-otp" class="button button-primary" style="margin-top: 5px; width: 100%;">
                                    <?php esc_html_e( 'Verify Code', 'vapt-security' ); ?>
                                </button>
                                <div style="margin-top: 10px; text-align: center; font-size: 0.9em;">
                                    <a href="#" id="vapt-sa-resend-otp"><?php esc_html_e( 'Resend', 'vapt-security' ); ?></a>
                                </div>
                            </div>
                            <div id="vapt-sa-otp-msg" style="margin-top: 10px;"></div>
                        </div>

                        <!-- Local JS for this Tab's OTP -->
                        <script>
                        jQuery(document).ready(function($) {
                            $('#vapt-sa-send-otp, #vapt-sa-resend-otp').click(function(e){
                                e.preventDefault();
                                var $btn = $(this);
                                $btn.prop('disabled', true);
                                $.post(ajaxurl, {action: 'vapt_send_otp'}, function(r){
                                    $btn.prop('disabled', false);
                                    if(r.success) {
                                        $('#vapt-sa-otp-step-1').slideUp();
                                        $('#vapt-sa-otp-step-2').slideDown();
                                        $('#vapt-sa-otp-msg').html('<span style="color:green">'+r.data.message+'</span>');
                                    } else {
                                        $('#vapt-sa-otp-msg').html('<span style="color:red">'+r.data.message+'</span>');
                                    }
                                });
                            });

                            $('#vapt-sa-verify-otp').click(function(e){
                                e.preventDefault();
                                var otp = $('#vapt-sa-otp-input').val();
                                if(!otp) return;
                                
                                var $btn = $(this);
                                $btn.prop('disabled', true);
                                
                                $.post(ajaxurl, {action: 'vapt_verify_otp', otp: otp}, function(r){
                                    if(r.success) {
                                        location.reload(); // Reload to show Link
                                    } else {
                                        $btn.prop('disabled', false);
                                        $('#vapt-sa-otp-msg').html('<span style="color:red">'+r.data.message+'</span>');
                                    }
                                });
                            });
                        });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery( function( $ ) {
    $( '#vapt-security-tabs' ).tabs({
        active: 0,
        activate: function( event, ui ) {
            // Store the active tab in localStorage
            localStorage.setItem( 'vapt_security_active_tab', ui.newTab.index() );
        },
        create: function( event, ui ) {
            // Restore the active tab from localStorage
            var activeTab = localStorage.getItem( 'vapt_security_active_tab' );
            if ( activeTab !== null ) {
                $( this ).tabs( 'option', 'active', parseInt( activeTab ) );
            }
        }
    });
} );
</script>