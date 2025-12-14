<?php
/**
 * Simple IP‑based rate limiter.
 *
 * Stores request timestamps in WP options (JSON‑encoded array) – suitable for low‑traffic sites.
 * For high‑traffic sites, consider using Redis or Memcached.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Rate_Limiter {

    const OPTION_KEY          = 'vapt_rate_limit';
    const REQUEST_WINDOW_MIN  = 60;   // 1 minute
    const MAX_REQUESTS_PER_IP = 10;   // 10 requests per minute

    /**
     * Singleton instance.
     *
     * @var VAPT_Rate_Limiter
     */
    private static $instance;

    /**
     * @var array
     */
    private $data = [];

    /**
     * Constructor – loads stored data.
     */
    private function __construct() {
        $stored = get_option( self::OPTION_KEY, '[]' );
        $this->data = json_decode( $stored, true );
        if ( ! is_array( $this->data ) ) {
            $this->data = [];
        }
    }

    /**
     * Return singleton instance.
     *
     * @return VAPT_Rate_Limiter
     */
    public static function instance(): VAPT_Rate_Limiter {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Allow a request from the current IP if under the threshold.
     *
     * @return bool
     */
    public function allow_request(): bool {
        $ip = $this->get_ip();
        $now = time();

        // Clean old entries for this IP
        if ( ! isset( $this->data[ $ip ] ) ) {
            $this->data[ $ip ] = [];
        }
        $this->data[ $ip ] = array_filter(
            $this->data[ $ip ],
            fn( $ts ) => ( $now - $ts ) <= self::REQUEST_WINDOW_MIN * 60
        );

        if ( count( $this->data[ $ip ] ) >= self::MAX_REQUESTS_PER_IP ) {
            return false; // Too many requests
        }

        // Record this request
        $this->data[ $ip ][] = $now;
        $this->save();
        return true;
    }

    /**
     * Clean up data older than REQUEST_WINDOW_MIN for all IPs.
     */
    public function clean_old_entries() {
        $now = time();
        $changed = false;

        foreach ( $this->data as $ip => $timestamps ) {
            $new = array_filter(
                $timestamps,
                fn( $ts ) => ( $now - $ts ) <= self::REQUEST_WINDOW_MIN * 60
            );
            if ( count( $new ) !== count( $timestamps ) ) {
                $changed = true;
                $this->data[ $ip ] = $new;
            }
        }

        if ( $changed ) {
            $this->save();
        }
    }

    /**
     * Persist data to the database.
     */
    private function save() {
        update_option( self::OPTION_KEY, wp_json_encode( $this->data ), false );
    }

    /**
     * Retrieve the client IP in a secure way.
     *
     * @return string
     */
    private function get_ip(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        // If behind a proxy, use X‑Forwarded‑For if safe
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip = trim( $ips[0] );
        }
        return $ip;
    }
}
