<?php
/**
 * Simple front‑end form that posts to the plugin’s AJAX handler.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form id="vapt-security-form" method="post" action="">
    <?php wp_nonce_field( 'vapt_form_action', 'nonce' ); ?>

    <label for="vapt_name"><?php esc_html_e( 'Name', 'vapt-security' ); ?></label>
    <input type="text" name="name" id="vapt_name" required maxlength="50" />

    <label for="vapt_email"><?php esc_html_e( 'Email', 'vapt-security' ); ?></label>
    <input type="email" name="email" id="vapt_email" required maxlength="100" />

    <label for="vapt_message"><?php esc_html_e( 'Message', 'vapt-security' ); ?></label>
    <textarea name="message" id="vapt_message" required maxlength="500"></textarea>

    <!-- Optional CAPTCHA field -->
    <label for="vapt_captcha"><?php esc_html_e( 'CAPTCHA (Type 1234)', 'vapt-security' ); ?></label>
    <input type="text" name="captcha" id="vapt_captcha" maxlength="10" autocomplete="off" />

    <button type="submit"><?php esc_html_e( 'Send', 'vapt-security' ); ?></button>
</form>
<div id="vapt-security-response"></div>
