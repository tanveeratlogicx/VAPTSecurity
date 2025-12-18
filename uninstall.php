<?php
/**
 * Uninstall VAPT Security
 *
 * @package VAPT_Security
 */

// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Delete all plugin options
delete_option( 'vapt_rate_limit' );
delete_option( 'vapt_cron_rate_limit' );
delete_option( 'vapt_blocked_ips' );
delete_option( 'vapt_security_logs' );
delete_option( 'vapt_ip_violations' );
delete_option( 'vapt_security_options' );

// For site options in Multisite
delete_site_option( 'vapt_rate_limit' );
delete_site_option( 'vapt_cron_rate_limit' );
delete_site_option( 'vapt_blocked_ips' );
delete_site_option( 'vapt_security_logs' );
delete_site_option( 'vapt_ip_violations' );
delete_site_option( 'vapt_security_options' );

// Clear any scheduled events
wp_clear_scheduled_hook( 'vapt_cleanup_event' );