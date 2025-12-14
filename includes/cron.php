<?php
/**
 * Cron helper functions.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class VAPT_Cron {

    const HOOK_DAILY = 'vapt_security_daily';

    private static $instance;

    public static function instance(): VAPT_Cron {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_daily_event' ], 20 );
        add_action( self::HOOK_DAILY, [ $this, 'run_daily_task' ] );
        register_deactivation_hook( VAPT_Security::FILE, [ $this, 'clear_scheduled_events' ] );
    }

    public function register_daily_event() {
        if ( ! wp_next_scheduled( self::HOOK_DAILY ) ) {
            wp_schedule_event( time(), 'daily', self::HOOK_DAILY );
        }
    }

    public function run_daily_task() {
        // Example: delete old revisions older than 30 days.
        global $wpdb;
        $days = 30;
        $date = date( 'Y-m-d H:i:s', strtotime( "-$days days" ) );

        $revisions = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'inherit' AND post_date < %s",
                'revision',
                $date
            )
        );

        foreach ( $revisions as $rev_id ) {
            wp_delete_post( $rev_id, true );
        }
    }

    public function schedule_event( string $hook, string $recurrence, ?int $timestamp = null ): bool {
        if ( wp_next_scheduled( $hook, [ 'custom' => true ] ) ) {
            return false;
        }

        $timestamp = $timestamp ?? time();
        wp_schedule_event( $timestamp, $recurrence, $hook, [ 'custom' => true ] );

        return true;
    }

    public function clear_scheduled_events() {
        wp_clear_scheduled_hook( self::HOOK_DAILY );
    }
}
