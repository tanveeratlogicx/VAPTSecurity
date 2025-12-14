<?php
/**
 * Cron helper functions.
 *
 * This file adds a single example cron job (`vapt_security_daily`) and
 * provides a small helper to schedule additional jobs in the future.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct access forbidden
}

/**
 * Class VAPT_Cron
 *
 * Handles all cron‑related operations for the plugin.
 */
final class VAPT_Cron {

    /**
     * Hook names
     */
    const HOOK_DAILY = 'vapt_security_daily';

    /**
     * Singleton instance
     *
     * @var VAPT_Cron
     */
    private static $instance;

    /**
     * Get the singleton instance.
     *
     * @return VAPT_Cron
     */
    public static function instance(): VAPT_Cron {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register hooks.
     */
    private function __construct() {
        // Register the daily event on init (only if it doesn't already exist)
        add_action( 'init', [ $this, 'register_daily_event' ], 20 );

        // Hook the callback
        add_action( self::HOOK_DAILY, [ $this, 'run_daily_task' ] );

        // Clear scheduled event on plugin deactivation
        register_deactivation_hook( VAPT_SECURITY_PLUGIN_FILE, [ $this, 'clear_scheduled_events' ] );
    }

    /**
     * Register a daily cron event (if it hasn't already been scheduled).
     */
    public function register_daily_event() {
        if ( ! wp_next_scheduled( self::HOOK_DAILY ) ) {
            wp_schedule_event( time(), 'daily', self::HOOK_DAILY );
        }
    }

    /**
     * The callback that runs on the daily cron event.
     */
    public function run_daily_task() {
        // Example: perform a quick audit of post revisions
        // (you can replace this with any custom logic you need)
        $this->cleanup_old_revisions();
    }

    /**
     * Example cleanup routine – delete revisions older than 30 days.
     */
    private function cleanup_old_revisions() {
        global $wpdb;
        $days = 30;
        $date = date( 'Y-m-d H:i:s', strtotime( "-$days days" ) );

        // Find revisions older than $days
        $revisions = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'inherit' AND post_date < %s",
            'revision',
            $date
        ) );

        foreach ( $revisions as $rev_id ) {
            wp_delete_post( $rev_id, true ); // Force delete
        }
    }

    /**
     * Helper: schedule a custom cron event.
     *
     * @param string   $hook      Action hook to fire.
     * @param string   $recurrence 'hourly', 'twicedaily', 'daily' or a custom interval key.
     * @param int|null $timestamp Unix timestamp when the event should first fire.
     *
     * @return bool True on success, false if the event already exists.
     */
    public function schedule_event( string $hook, string $recurrence, ?int $timestamp = null ): bool {
        if ( wp_next_scheduled( $hook, [ 'custom' => true ] ) ) {
            return false; // Already scheduled
        }

        $timestamp = $timestamp ?? time();
        wp_schedule_event( $timestamp, $recurrence, $hook, [ 'custom' => true ] );

        return true;
    }

    /**
     * Clear all scheduled events when the plugin is deactivated.
     */
    public function clear_scheduled_events() {
        // Delete the daily event
        wp_clear_scheduled_hook( self::HOOK_DAILY );
        // Add any other custom hooks you scheduled here
    }
}

// Instantiate the class
VAPT_Cron::instance();
