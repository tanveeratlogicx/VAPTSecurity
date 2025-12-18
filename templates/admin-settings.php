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

        // Count enabled tabs
        $tab_count = 2; // General + Stats
        if ( VAPT_Features::is_enabled( 'rate_limiting' ) && defined( 'VAPT_FEATURE_RATE_LIMITING' ) && VAPT_FEATURE_RATE_LIMITING ) $tab_count++;
        if ( VAPT_Features::is_enabled( 'input_validation' ) && defined( 'VAPT_FEATURE_INPUT_VALIDATION' ) && VAPT_FEATURE_INPUT_VALIDATION ) $tab_count++;
        if ( VAPT_Features::is_enabled( 'cron_protection' ) && defined( 'VAPT_FEATURE_WP_CRON_PROTECTION' ) && VAPT_FEATURE_WP_CRON_PROTECTION ) $tab_count++;
        if ( VAPT_Features::is_enabled( 'security_logging' ) && defined( 'VAPT_FEATURE_SECURITY_LOGGING' ) && VAPT_FEATURE_SECURITY_LOGGING ) $tab_count++;
        
        $vertical_tabs = ( $tab_count > 5 );
        $container_class = $vertical_tabs ? 'vapt-vertical-tabs' : '';
        ?>

        <?php if ( $vertical_tabs ) : ?>
        <style>
            .vapt-vertical-tabs {
                display: flex;
                border: 1px solid #c3c4c7;
                background: #fff;
            }
            .vapt-vertical-tabs .ui-tabs-nav {
                display: block;
                float: none;
                width: 200px;
                padding: 0;
                margin: 0;
                background: #f0f0f1;
                border-right: 1px solid #c3c4c7;
            }
            .vapt-vertical-tabs .ui-tabs-nav li {
                float: none;
                margin: 0;
                border: none;
                border-bottom: 1px solid #c3c4c7;
                background: #f0f0f1;
                white-space: normal;
            }
            .vapt-vertical-tabs .ui-tabs-nav li a {
                 display: block;
                 padding: 10px 15px;
                 font-weight: 600;
                 color: #2271b1 !important;
                 text-decoration: none;
            }
            .vapt-vertical-tabs .ui-tabs-nav li.ui-tabs-active {
                background: #fff;
                border-bottom: 1px solid #c3c4c7; 
                margin-right: -1px;
                border-right: 1px solid #fff;
            }
            .vapt-vertical-tabs .ui-tabs-nav li.ui-tabs-active a {
                color: #1d2327 !important;
            }
            .vapt-security-tab-content {
                flex-grow: 1;
                padding: 20px;
                border: none;
                background: #fff;
            }
            /* Hide default jQuery UI borders if we handle them */
            .vapt-vertical-tabs.ui-tabs {
                padding: 0;
            }
        </style>
        <?php endif; ?>

        <!-- Modern Horizontal/Vertical Tabs -->
        <div id="vapt-security-tabs" class="<?php echo esc_attr( $container_class ); ?>">
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