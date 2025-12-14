<?php
/**
 * Very light‑weight CAPTCHA placeholder.
 * Replace with reCAPTCHA, hCaptcha, or any custom solution.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Captcha {

    private static $instance;

    public static function instance(): VAPT_Captcha {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function verify( string $response ): bool {
        // For demo: accept "1234" as the correct answer.
        return trim( $response ) === '1234';
    }
}
