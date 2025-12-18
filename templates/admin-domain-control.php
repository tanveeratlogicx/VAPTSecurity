<?php
// Superadmin Domain Control Page
// Check transient auth
$user_id = get_current_user_id();
$is_verified = get_transient( 'vapt_auth_' . $user_id );

// License Data
$license = VAPT_License::get_license();
$license_type = $license['type'] ?? 'standard';
$license_expires = $license['expires'] ?? 0;
$license_auto_renew = $license['auto_renew'] ?? false;
$expiry_date = $license_expires ? date_i18n( get_option( 'date_format' ), $license_expires ) : __( 'Never', 'vapt-security' );

// Get Active Features
$features = VAPT_Features::get_active_features();
$all_features = VAPT_Features::get_defined_features();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'VAPT Security - Domain Control', 'vapt-security' ); ?></h1>
    
    <?php if ( ! $is_verified ) : ?>
        <!-- OTP Form (Similar to before but specific to Superadmin flow) -->
        <div class="vapt-otp-container card" style="max-width: 400px; margin-top: 20px; padding: 20px;">
            <h2><?php esc_html_e( 'Superadmin Authentication', 'vapt-security' ); ?></h2>
            <p><?php esc_html_e( 'Verify your identity to manage Domain Features.', 'vapt-security' ); ?></p>
            
            <div id="vapt-otp-step-1">
                <button type="button" id="vapt-send-otp" class="button button-primary button-hero">
                    <?php esc_html_e( 'Send OTP', 'vapt-security' ); ?>
                </button>
            </div>
            
            <div id="vapt-otp-step-2" style="display:none; margin-top: 20px;">
                <input type="text" id="vapt-otp-input" class="regular-text" placeholder="------" maxlength="6" style="width: 100%; text-align: center; letter-spacing: 5px;" />
                <button type="button" id="vapt-verify-otp" class="button button-primary button-hero" style="width: 100%; margin-top: 10px;">
                    <?php esc_html_e( 'Verify', 'vapt-security' ); ?>
                </button>
                <div style="margin-top: 10px; text-align: center;">
                    <a href="#" id="vapt-resend-otp"><?php esc_html_e( 'Resend OTP', 'vapt-security' ); ?></a>
                </div>
            </div>
            <div id="vapt-otp-message" style="margin-top: 15px;"></div>
        </div>

    <?php else : ?>
        <!-- Verified Superadmin UI -->
        
        <!-- Domain Features -->
        <div class="card" style="margin-top: 20px; padding: 20px;">
            <h2><?php esc_html_e( 'Domain Features', 'vapt-security' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Enable or disable features for this domain. Disabled features are hidden from Admins.', 'vapt-security' ); ?></p>
            
            <form id="vapt-domain-features-form">
                <table class="form-table">
                    <?php foreach( $all_features as $slug => $default ) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $slug ) ) ); ?></th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="features[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( VAPT_Features::is_enabled( $slug ) ); ?>>
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p>
                    <button type="button" id="vapt-save-features" class="button button-primary"><?php esc_html_e( 'Save Domain Features', 'vapt-security' ); ?></button>
                    <span id="vapt-features-msg" style="margin-left: 10px;"></span>
                </p>
            </form>
        </div>

        <!-- License Management -->
        <div class="card" style="margin-top: 20px; padding: 20px;">
            <h2><?php esc_html_e( 'License Management', 'vapt-security' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'License Type', 'vapt-security' ); ?></th>
                    <td>
                        <select id="vapt-license-type">
                            <option value="standard" <?php selected( $license_type, 'standard' ); ?>>Standard</option>
                            <option value="pro" <?php selected( $license_type, 'pro' ); ?>>Pro</option>
                            <option value="developer" <?php selected( $license_type, 'developer' ); ?>>Developer</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Expiry', 'vapt-security' ); ?></th>
                    <td><input type="text" id="vapt-license-expiry" value="<?php echo esc_attr( $expiry_date ); ?>" readonly class="regular-text"></td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <button type="button" id="vapt-update-license" class="button button-secondary"><?php esc_html_e( 'Update License', 'vapt-security' ); ?></button>
                        <button type="button" id="vapt-renew-license" class="button button-secondary"><?php esc_html_e( 'Renew', 'vapt-security' ); ?></button>
                        <span id="vapt-license-msg" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // OTP Logic (Same endpoints, generic)
    $('#vapt-send-otp, #vapt-resend-otp').click(function(e){
        e.preventDefault();
        $.post(ajaxurl, {action:'vapt_send_otp'}, function(r){
            if(r.success) {
                $('#vapt-otp-step-1').hide();
                $('#vapt-otp-step-2').show();
                $('#vapt-otp-message').html('<span style="color:green">'+r.data.message+'</span>');
            } else {
                $('#vapt-otp-message').html('<span style="color:red">'+r.data.message+'</span>');
            }
        });
    });

    $('#vapt-verify-otp').click(function(){
        $.post(ajaxurl, {
            action:'vapt_verify_otp', 
            otp: $('#vapt-otp-input').val()
        }, function(r){
            if(r.success) location.reload();
            else $('#vapt-otp-message').html('<span style="color:red">'+r.data.message+'</span>');
        });
    });

    // Save Features
    $('#vapt-save-features').click(function(){
        var data = $('#vapt-domain-features-form').serialize();
        $.post(ajaxurl, {
            action: 'vapt_save_domain_features',
            data: data
        }, function(r){
            if(r.success) $('#vapt-features-msg').html('<span style="color:green">'+r.data.message+'</span>');
            else $('#vapt-features-msg').html('<span style="color:red">'+r.data.message+'</span>');
        });
    });

    // License (Reuse endpoints)
    $('#vapt-update-license').click(function(){
        $.post(ajaxurl, {
            action:'vapt_update_license', 
            type:$('#vapt-license-type').val()
        }, function(r){
             if(r.success) {
                 $('#vapt-license-msg').html('<span style="color:green">'+r.data.message+'</span>');
                 if(r.data.expires_formatted) $('#vapt-license-expiry').val(r.data.expires_formatted);
             }
             else $('#vapt-license-msg').html('<span style="color:red">'+r.data.message+'</span>');
        });
    });
});
</script>
