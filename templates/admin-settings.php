<?php
/**
 * Settings page markup that includes jQuery UI tabs.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'VAPT Security Settings', 'vapt-security' ); ?></h1>

    <form method="post" action="options.php">
        <?php
        // Output security fields.
        settings_fields( 'vapt_security_options_group' );
        ?>

        <!-- Tabs container -->
        <div id="vapt-security-tabs">
            <ul>
                <li><a href="#tab-general"><?php esc_html_e( 'General', 'vapt-security' ); ?></a></li>
                <li><a href="#tab-rate-limiter"><?php esc_html_e( 'Rate Limiter', 'vapt-security' ); ?></a></li>
                <li><a href="#tab-validation"><?php esc_html_e( 'Input Validation', 'vapt-security' ); ?></a></li>
                <li><a href="#tab-cron"><?php esc_html_e( 'WP‑Cron Protection', 'vapt-security' ); ?></a></li>
            </ul>

            <div id="tab-general">
                <?php do_settings_sections( 'vapt_security_general' ); ?>
            </div>

            <div id="tab-rate-limiter">
                <?php do_settings_sections( 'vapt_security_rate_limiter' ); ?>
            </div>

            <div id="tab-validation">
                <?php do_settings_sections( 'vapt_security_validation' ); ?>
            </div>

            <div id="tab-cron">
                <?php do_settings_sections( 'vapt_security_cron' ); ?>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery( function( $ ) {
    $( '#vapt-security-tabs' ).tabs();
} );
</script>
