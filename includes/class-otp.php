<?php
/**
 * VAPT OTP Helper
 *
 * Handles One-Time Password (OTP) generation, storage, validation, and delivery.
 */
class VAPT_OTP {

    /**
     * Generate and send an OTP to the user.
     *
     * @param int $user_id The ID of the user to send the OTP to.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function send_otp( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return new WP_Error( 'invalid_user', __( 'Invalid user ID.', 'vapt-security' ) );
        }

        // Generate a 6-digit numeric OTP
        $otp = str_pad( rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );

        // Store OTP and timestamp in user meta
        // We store the expiry time directly to make checks easier (now + 120 seconds)
        $expiry = time() + 120;
        update_user_meta( $user_id, 'vapt_otp', $otp );
        update_user_meta( $user_id, 'vapt_otp_expiry', $expiry );

        // Prepare email
        $to      = $user->user_email;
        $subject = __( 'Your VAPTSecurity OTP', 'vapt-security' );
        $message = sprintf(
            __( 'Your OTP for VAPTSecurity configuration is: %s. It is valid for 120 seconds.', 'vapt-security' ),
            $otp
        );
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        // Try wp_mail first
        $sent = wp_mail( $to, $subject, $message, $headers );

        // Fallback to PHP mail if wp_mail fails
        if ( ! $sent ) {
            $sent = mail( $to, $subject, $message, implode( "\r\n", $headers ) );
        }

        if ( ! $sent ) {
            return new WP_Error( 'email_failed', __( 'Failed to send OTP email.', 'vapt-security' ) );
        }

        return true;
    }

    /**
     * Validate a submitted OTP.
     *
     * @param int    $user_id The user ID.
     * @param string $input_otp The OTP submitted by the user.
     * @return bool|WP_Error True if valid, WP_Error if invalid or expired.
     */
    public static function verify_otp( $user_id, $input_otp ) {
        $stored_otp    = get_user_meta( $user_id, 'vapt_otp', true );
        $stored_expiry = get_user_meta( $user_id, 'vapt_otp_expiry', true );

        if ( ! $stored_otp || ! $stored_expiry ) {
            return new WP_Error( 'no_otp', __( 'No OTP found. Please request a new one.', 'vapt-security' ) );
        }

        if ( time() > $stored_expiry ) {
            self::clear_otp( $user_id ); // Cleanup expired
            return new WP_Error( 'otp_expired', __( 'OTP has expired. Please request a new one.', 'vapt-security' ) );
        }

        if ( $stored_otp !== $input_otp ) {
            return new WP_Error( 'invalid_otp', __( 'Invalid OTP. Please try again.', 'vapt-security' ) );
        }

        // OTP is valid - clear it so it can't be reused
        self::clear_otp( $user_id );
        
        return true;
    }

    /**
     * Clear OTP data for a user.
     * 
     * @param int $user_id
     */
    public static function clear_otp( $user_id ) {
        delete_user_meta( $user_id, 'vapt_otp' );
        delete_user_meta( $user_id, 'vapt_otp_expiry' );
    }
    /**
     * Generate and send an OTP to a specific email.
     *
     * @param string $email The email to send the OTP to.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function send_otp_to_email( $email ) {
        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'vapt-security' ) );
        }

        // Generate a 6-digit numeric OTP
        $otp = str_pad( rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );

        // Store OTP in transient (5 minutes)
        // Key is hashed to avoid exposing email in DB keys directly if that matters, but mainly for consistent keys
        $key = 'vapt_otp_' . md5( $email );
        set_transient( $key, $otp, 300 ); // 5 minutes

        // Prepare email
        $to      = $email;
        $subject = __( 'Your VAPTSecurity OTP', 'vapt-security' );
        $message = sprintf(
            __( 'Your OTP for VAPTSecurity configuration is: %s. It is valid for 5 minutes.', 'vapt-security' ),
            $otp
        );
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

        // Try wp_mail first
        $sent = wp_mail( $to, $subject, $message, $headers );

        // Fallback to PHP mail if wp_mail fails
        if ( ! $sent ) {
            $sent = mail( $to, $subject, $message, implode( "\r\n", $headers ) );
        }

        if ( ! $sent ) {
            return new WP_Error( 'email_failed', __( 'Failed to send OTP email.', 'vapt-security' ) );
        }

        return true;
    }

    /**
     * Validate an OTP for a specific email.
     *
     * @param string $email     The email address.
     * @param string $input_otp The OTP submitted by the user.
     * @return bool|WP_Error True if valid, WP_Error if invalid or expired.
     */
    public static function verify_otp_for_email( $email, $input_otp ) {
        $key = 'vapt_otp_' . md5( $email );
        $stored_otp = get_transient( $key );

        if ( ! $stored_otp ) {
            return new WP_Error( 'otp_expired', __( 'OTP has expired or does not exist. Please request a new one.', 'vapt-security' ) );
        }

        if ( $stored_otp !== $input_otp ) {
            return new WP_Error( 'invalid_otp', __( 'Invalid OTP. Please try again.', 'vapt-security' ) );
        }

        // OTP is valid - clear it
        self::clear_otp_for_email( $email );
        
        return true;
    }

    /**
     * Clear OTP data for an email.
     * 
     * @param string $email
     */
    public static function clear_otp_for_email( $email ) {
        $key = 'vapt_otp_' . md5( $email );
        delete_transient( $key );
    }
}
