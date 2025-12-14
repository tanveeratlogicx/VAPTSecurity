<?php
/**
 * Rate Limiter.
 *
 * Stores timestamps in an options entry.  For high‑traffic sites,
 * replace it with a Redis/Memcached implementation.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Rate_Limiter {

    const OPTION_KEY          = 'vapt_rate_limit';
    const WINDOW_MINUTES      = 1;          // 1 minute
    const MAX_REQUESTS_PER_MIN = 10;        // default

    private static $instance;
    private $data = [];

    public static function instance(): VAPT_Rate_Limiter {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $stored = get_option( self::OPTION_KEY, '[]' );
        $this->data = json_decode( $stored, true );
        if ( ! is_array( $this->data ) ) {
            $this->data = [];
        }
    }

    public function allow_request(): bool {
        $ip   = $this->get_ip();
        $now  = time();

        if ( ! isset( $this->data[ $ip ] ) ) {
            $this->data[ $ip ] = [];
        }

        // Keep only timestamps within the window
        $this->data[ $ip ] = array_filter(
            $this->data[ $ip ],
            fn( $ts ) => ( $now - $ts ) <= self::WINDOW_MINUTES * 60
        );

        if ( count( $this->data[ $ip ] ) >= self::MAX_REQUESTS_PER_MIN ) {
            return false;
        }

        $this->data[ $ip ][] = $now;
        $this->save();
        return true;
    }

    public function clean_old_entries() {
        $now = time();
        $changed = false;

        foreach ( $this->data as $ip => $timestamps ) {
            $new = array_filter(
                $timestamps,
                fn( $ts ) => ( $now - $ts ) <= self::WINDOW_MINUTES * 60
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

    private function save() {
        update_option( self::OPTION_KEY, wp_json_encode( $this->data ), false );
    }

    private function get_ip(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip  = trim( $ips[0] );
        }
        return $ip;
    }
}
