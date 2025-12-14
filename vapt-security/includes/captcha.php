<?php
/**
 * Lightweight CAPTCHA placeholder.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Captcha {

    /**
     * Singleton instance
     *
     * @var VAPT_Captcha
     */
    private static $instance;

    /**
     * Private constructor
     */
    private function __construct() {
        // Load any external API keys or options here
    }

    /**
     * Return singleton
     *
     * @return VAPT_Captcha
     */
    public static function instance(): VAPT_Captcha {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Very simple CAPTCHA verification – replace with real API call.
     *
     * @param string $response
     *
     * @return bool
     */
    public function verify( string $response ): bool {
        // For demo purposes: accept "1234" as a valid captcha
        return trim( $response ) === '1234';
    }
}
