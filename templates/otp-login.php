<?php
/**
 * Template for VAPT Security OTP Login
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e( 'VAPT Security - Superadmin Access', 'vapt-security' ); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f0f0f1;
            color: #3c434a;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .vapt-login-container {
            background: #fff;
            padding: 40px;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .vapt-logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2271b1;
        }
        .vapt-message {
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .vapt-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .vapt-button {
            background: #2271b1;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .vapt-button:hover {
            background: #135e96;
        }
        .vapt-error {
            color: #d63638;
            margin-bottom: 15px;
            border-left: 4px solid #d63638;
            background: #fff;
            padding: 12px;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
            text-align: left;
        }
        .vapt-notice {
            color: #00a32a;
            margin-bottom: 15px;
            border-left: 4px solid #00a32a;
            background: #fff;
            padding: 12px;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
            text-align: left;
        }
    </style>
</head>
<body>

<div class="vapt-login-container">
    <div class="vapt-logo"><?php esc_html_e( 'VAPT Security', 'vapt-security' ); ?></div>
    
    <?php if ( ! empty( $error ) ) : ?>
        <div class="vapt-error"><?php echo esc_html( $error ); ?></div>
    <?php endif; ?>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="vapt-notice"><?php echo esc_html( $message ); ?></div>
    <?php endif; ?>

    <?php if ( empty( $otp_sent ) ) : ?>
        <p class="vapt-message">
            <?php esc_html_e( 'Access to this page is restricted. Please request an OTP to your Superadmin email address.', 'vapt-security' ); ?>
        </p>
        <form method="post">
            <input type="hidden" name="vapt_request_otp" value="1">
            <button type="submit" class="vapt-button"><?php esc_html_e( 'Send OTP', 'vapt-security' ); ?></button>
        </form>
    <?php else : ?>
        <p class="vapt-message">
            <?php esc_html_e( 'An OTP has been sent to your email. Please enter it below.', 'vapt-security' ); ?>
        </p>
        <form method="post">
            <input type="hidden" name="vapt_verify_otp" value="1">
            <input type="text" name="vapt_otp" class="vapt-input" placeholder="Enter 6-digit OTP" required autofocus>
            <button type="submit" class="vapt-button"><?php esc_html_e( 'Verify OTP', 'vapt-security' ); ?></button>
        </form>
        <div style="margin-top: 15px;">
            <form method="post" style="display:inline;">
                <input type="hidden" name="vapt_request_otp" value="1">
                <button type="submit" style="background:none; border:none; color:#2271b1; text-decoration:underline; cursor:pointer;">
                    <?php esc_html_e( 'Resend OTP', 'vapt-security' ); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
