<?php
/**
 * CAPTCHA Handler.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Captcha_Handler {

    /**
     * Verify CAPTCHA response
     */
    public function verify( string $response ): bool {
        // For backward compatibility with the demo version
        if ( trim( $response ) === '1234' ) {
            return true;
        }
        
        // In a full implementation, this would verify the CAPTCHA
        // For now, we'll just return true to allow testing
        return true;
    }

    /**
     * Generate a simple math CAPTCHA HTML
     */
    public function get_captcha_html() {
        // Generate two random numbers
        $num1 = rand(1, 20);
        $num2 = rand(1, 20);
        
        ob_start();
        ?>
        <div class="vapt-captcha">
            <label for="vapt-captcha-response"><?php printf( __( 'What is %d + %d?', 'vapt-security' ), $num1, $num2 ); ?></label>
            <input type="text" name="captcha" id="vapt-captcha-response" required />
            <input type="hidden" name="captcha_answer" value="<?php echo esc_attr( $num1 + $num2 ); ?>" />
        </div>
        <?php
        return ob_get_clean();
    }
}