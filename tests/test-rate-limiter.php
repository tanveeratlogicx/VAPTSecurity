<?php
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase {

    public function setUp(): void {
        // Reset stored option
        delete_option( 'vapt_rate_limit' );
    }

    public function test_allow_under_limit() {
        $rl = VAPT_Rate_Limiter::instance();

        // Simulate 5 requests from the same IP
        for ( $i = 0; $i < 5; $i++ ) {
            $this->assertTrue( $rl->allow_request() );
        }
    }

    public function test_block_over_limit() {
        $rl = VAPT_Rate_Limiter::instance();

        // Simulate 12 requests (max 10 per minute)
        for ( $i = 0; $i < 12; $i++ ) {
            $allow = $rl->allow_request();
            if ( $i < 10 ) {
                $this->assertTrue( $allow );
            } else {
                $this->assertFalse( $allow );
            }
        }
    }
}
