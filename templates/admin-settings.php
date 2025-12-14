<?php
/**
 * Settings page markup with jQuery UI tabs.
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
        // Print all settings sections (defined below).
        do_settings_sections( 'vapt-security-settings' );
        ?>

        <!-- Tabs container -->
        <div id="vapt-security-tabs">
            <ul>
                <li><a href="#tab-general">General</a></li>
                <li><a href="#tab-rate-limiter">Rate Limiter</a></li>
                <li><a href="#tab-validation">Input Validation</a></li>
                <li><a href="#tab-cron">WP‑Cron Protection</a></li>
            </ul>

            <div id="tab-general">
                <?php
                // The fields that belong to this tab will be rendered here.
                do_settings_sections( 'vapt_security_general' );
                ?>
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
