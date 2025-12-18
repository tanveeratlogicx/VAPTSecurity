<?php
/**
 * Security Logger.
 *
 * @package VAPT_Security_Qoder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Security_Logger {

    const LOG_OPTION_KEY = 'vapt_security_logs';
    const LOG_LIMIT = 1000; // Maximum number of log entries

    private $logs = [];

    public function __construct() {
        $stored = get_option( self::LOG_OPTION_KEY, '[]' );
        $this->logs = json_decode( $stored, true );
        if ( ! is_array( $this->logs ) ) {
            $this->logs = [];
        }
    }

    /**
     * Log a security event
     */
    public function log_event( $event_type, $data = [] ) {
        // Check if logging is enabled
        $options = get_option( 'vapt_security_options', [] );
        if ( ! isset( $options['enable_logging'] ) || ! $options['enable_logging'] ) {
            return;
        }

        $log_entry = [
            'timestamp' => time(),
            'event_type' => $event_type,
            'data' => $data,
            'ip' => $this->get_current_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        ];

        // Add to logs
        $this->logs[] = $log_entry;

        // Limit log size
        if ( count( $this->logs ) > self::LOG_LIMIT ) {
            // Keep only the most recent entries
            $this->logs = array_slice( $this->logs, -self::LOG_LIMIT );
        }

        // Save logs
        $this->save();
    }

    /**
     * Get current IP address
     */
    private function get_current_ip(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Handle Cloudflare
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $ip = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
        }
        // Handle proxy
        elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip  = trim( $ips[0] );
        }
        // Handle alternative proxy header
        elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            $ip = sanitize_text_field( $_SERVER['HTTP_X_REAL_IP'] );
        }
        
        return $ip;
    }

    /**
     * Save logs
     */
    private function save() {
        update_option( self::LOG_OPTION_KEY, wp_json_encode( $this->logs ), false );
    }

    /**
     * Get all logs
     */
    public function get_logs() {
        return $this->logs;
    }

    /**
     * Get logs filtered by event type
     */
    public function get_logs_by_type( $event_type ) {
        return array_filter( $this->logs, function( $log ) use ( $event_type ) {
            return $log['event_type'] === $event_type;
        });
    }

    /**
     * Get logs for a specific IP
     */
    public function get_logs_by_ip( $ip ) {
        return array_filter( $this->logs, function( $log ) use ( $ip ) {
            return $log['ip'] === $ip;
        });
    }

    /**
     * Get logs within a time range
     */
    public function get_logs_by_time_range( $start_time, $end_time ) {
        return array_filter( $this->logs, function( $log ) use ( $start_time, $end_time ) {
            return $log['timestamp'] >= $start_time && $log['timestamp'] <= $end_time;
        });
    }

    /**
     * Cleanup old logs (older than 30 days)
     */
    public function cleanup_old_logs() {
        $thirty_days_ago = time() - ( 30 * 24 * 60 * 60 );
        
        $this->logs = array_filter( $this->logs, function( $log ) use ( $thirty_days_ago ) {
            return $log['timestamp'] >= $thirty_days_ago;
        });
        
        $this->save();
    }

    /**
     * Clear all logs
     */
    public function clear_logs() {
        $this->logs = [];
        $this->save();
    }

    /**
     * Get log statistics
     */
    public function get_statistics() {
        $stats = [
            'total_events' => count( $this->logs ),
            'event_types' => [],
            'top_ips' => [],
            'last_24_hours' => 0
        ];

        $twenty_four_hours_ago = time() - ( 24 * 60 * 60 );

        foreach ( $this->logs as $log ) {
            // Count event types
            if ( ! isset( $stats['event_types'][ $log['event_type'] ] ) ) {
                $stats['event_types'][ $log['event_type'] ] = 0;
            }
            $stats['event_types'][ $log['event_type'] ]++;

            // Count IPs
            if ( ! isset( $stats['top_ips'][ $log['ip'] ] ) ) {
                $stats['top_ips'][ $log['ip'] ] = 0;
            }
            $stats['top_ips'][ $log['ip'] ]++;

            // Count last 24 hours
            if ( $log['timestamp'] >= $twenty_four_hours_ago ) {
                $stats['last_24_hours']++;
            }
        }

        // Sort IPs by frequency
        arsort( $stats['top_ips'] );
        // Keep only top 10
        $stats['top_ips'] = array_slice( $stats['top_ips'], 0, 10, true );

        return $stats;
    }
}