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

// Pre-calculate future expiries for JS
$future_expiries = [
    'standard' => date_i18n( get_option( 'date_format' ), time() + ( 30 * DAY_IN_SECONDS ) ),
    'pro'      => date_i18n( get_option( 'date_format' ), time() + ( 365 * DAY_IN_SECONDS ) - DAY_IN_SECONDS ),
    'developer' => __( 'Never', 'vapt-security' )
];
?>
<div class="wrap">
    <h1><?php esc_html_e( 'VAPT Security - Domain Admin', 'vapt-security' ); ?></h1>
    
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
                    <span id="vapt-otp-timer-container"><?php esc_html_e( 'Resend in', 'vapt-security' ); ?> <span id="vapt-otp-timer">120</span>s</span>
                    <a href="#" id="vapt-resend-otp" style="display:none;"><?php esc_html_e( 'Resend OTP', 'vapt-security' ); ?></a>
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
            
            <style>
                .vapt-feature-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* Responsive columns */
                    gap: 10px;
                    margin-bottom: 20px;
                }
                .vapt-feature-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    padding: 8px 15px; /* Reduced height/padding */
                    border-radius: 4px;
                }
                .vapt-feature-item h4 {
                    margin: 0;
                    font-size: 13px;
                    font-weight: 600;
                }
            </style>
            
            <form id="vapt-domain-features-form">
                <?php if ( count($all_features) > 5 ) : ?>
                    <!-- Grid Layout for many features -->
                    <div class="vapt-feature-grid">
                        <?php foreach( $all_features as $slug => $default ) : ?>
                        <div class="vapt-feature-item">
                            <h4><?php echo esc_html( ucwords( str_replace( '_', ' ', $slug ) ) ); ?></h4>
                            <label class="switch">
                                <input type="checkbox" name="features[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( VAPT_Features::is_enabled( $slug ) ); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <!-- Standard Table for few features -->
                    <table class="form-table">
                        <?php foreach( $all_features as $slug => $default ) : ?>
                        <tr>
                            <th scope="row" style="padding: 10px 0;"><?php echo esc_html( ucwords( str_replace( '_', ' ', $slug ) ) ); ?></th>
                            <td style="padding: 10px 0;">
                                <label class="switch">
                                    <input type="checkbox" name="features[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( VAPT_Features::is_enabled( $slug ) ); ?>>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                
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
                    <th scope="row"><?php esc_html_e( 'Auto Renewal', 'vapt-security' ); ?></th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" id="vapt-license-auto-renew" <?php checked( $license_auto_renew ); ?>>
                            <span class="slider round"></span>
                        </label>
                        <p class="description"><?php esc_html_e( 'Extend license automatically upon expiry.', 'vapt-security' ); ?></p>
                    </td>
                </tr>
                <?php if ( isset( $license['renewal_count'] ) ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Terms Renewed', 'vapt-security' ); ?></th>
                    <td>
                        <span class="badge" style="background: #2271b1; color: white; padding: 5px 10px; border-radius: 10px;">
                            <?php echo esc_html( $license['renewal_count'] ); ?>
                        </span>
                    </td>
                </tr>
                <?php endif; ?>
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
        
        <!-- Locked Configuration Generator -->
        <div class="card" style="margin-top: 20px; padding: 20px;">
            <h2><?php esc_html_e( 'Locked Configuration Generator', 'vapt-security' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Generate a portable configuration file locked to a specific domain pattern.', 'vapt-security' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Target Domain Pattern', 'vapt-security' ); ?></th>
                    <td>
                        <input type="text" id="vapt-lock-domain" class="regular-text" placeholder="*.example.com" value="<?php echo esc_attr( $_SERVER['HTTP_HOST'] ); ?>">
                        <p class="description"><?php esc_html_e( 'Use * for wildcards (e.g., *.example.com matches staging.example.com).', 'vapt-security' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Include Current Settings', 'vapt-security' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="vapt-lock-include-settings" checked>
                            <?php esc_html_e( 'Export current plugin configuration', 'vapt-security' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <button type="button" id="vapt-generate-locked-config" class="button button-primary"><?php esc_html_e( 'Generate Config', 'vapt-security' ); ?></button>
                        <button type="button" id="vapt-generate-client-zip" class="button button-secondary" style="margin-left: 10px;">
                            <?php esc_html_e( 'Download Client Zip', 'vapt-security' ); ?>
                        </button>
                        <span id="vapt-generate-msg" style="margin-left: 10px;"></span>
                        <p class="description" style="margin-top: 10px;">
                            <?php esc_html_e( 'Client Zip includes the plugin files and the locked configuration, but excludes documentation and dev files.', 'vapt-security' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    let timerInterval;

    function startOtpTimer() {
        let timeLeft = 120;
        $('#vapt-otp-timer').text(timeLeft);
        $('#vapt-otp-timer-container').show();
        $('#vapt-resend-otp').hide();
        
        clearInterval(timerInterval);
        timerInterval = setInterval(function() {
            timeLeft--;
            $('#vapt-otp-timer').text(timeLeft);
            if(timeLeft <= 0) {
                clearInterval(timerInterval);
                $('#vapt-otp-timer-container').hide();
                $('#vapt-resend-otp').show();
            }
        }, 1000);
    }

    // OTP Logic (Same endpoints, generic)
    $('#vapt-send-otp, #vapt-resend-otp').click(function(e){
        e.preventDefault();
        
        // Disable buttons temporarily
        $(this).prop('disabled', true);
        
        $.post(ajaxurl, {action:'vapt_send_otp'}, function(r){
             // Re-enable (for send btn)
            $('#vapt-send-otp').prop('disabled', false);

            if(r.success) {
                $('#vapt-otp-step-1').hide();
                $('#vapt-otp-step-2').show();
                $('#vapt-otp-message').html('<span style="color:green">'+r.data.message+'</span>');
                startOtpTimer();
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
    
    // Immediate Frontend Update
    var futureExpiries = <?php echo json_encode( $future_expiries ); ?>;
    
    function toggleRenewButton() {
        var isChecked = $('#vapt-license-auto-renew').is(':checked');
        $('#vapt-renew-license').prop('disabled', !isChecked);
    }

    // Initial state
    toggleRenewButton();

    $('#vapt-license-auto-renew').change(function(){
        toggleRenewButton();
    });

    $('#vapt-license-type').change(function(){
        var type = $(this).val();
        if ( futureExpiries[type] ) {
            $('#vapt-license-expiry').val( futureExpiries[type] );
        }
        
        // Developer Constraint
        if ( type === 'developer' ) {
            $('#vapt-license-auto-renew').prop('checked', false).prop('disabled', true);
        } else {
            $('#vapt-license-auto-renew').prop('disabled', false);
        }
        toggleRenewButton();
    });

    $('#vapt-update-license').click(function(){
        $.post(ajaxurl, {
            action:'vapt_update_license', 
            type:$('#vapt-license-type').val(),
            auto_renew: $('#vapt-license-auto-renew').is(':checked') ? 1 : 0
        }, function(r){
             if(r.success) {
                 $('#vapt-license-msg').html('<span style="color:green">'+r.data.message+'</span>');
                 if(r.data.expires_formatted) $('#vapt-license-expiry').val(r.data.expires_formatted);
             }
             else $('#vapt-license-msg').html('<span style="color:red">'+r.data.message+'</span>');
        });
    });
    // Locked Config Generator
    $('#vapt-generate-locked-config').click(function(){
        var btn = $(this);
        btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'vapt-security' ); ?>');
        
        $.post(ajaxurl, {
            action: 'vapt_generate_locked_config',
            domain: $('#vapt-lock-domain').val(),
            include_settings: $('#vapt-lock-include-settings').is(':checked') ? 1 : 0,
            nonce: '<?php echo wp_create_nonce( "vapt_locked_config" ); ?>' // We should ideally pass this via wp_localize_script
        }, function(r){
            btn.prop('disabled', false).text('<?php esc_html_e( 'Generate Config', 'vapt-security' ); ?>');
            if(r.success) {
                $('#vapt-generate-msg').html('<span style="color:green">'+r.data.message+'</span>');
            } else {
                $('#vapt-generate-msg').html('<span style="color:red">'+r.data.message+'</span>');
            }
        });
    });

    // Client Zip Generator
    $('#vapt-generate-client-zip').click(function(){
        var btn = $(this);
        var originalText = btn.text();
        btn.prop('disabled', true).text('<?php esc_html_e( 'Building Zip...', 'vapt-security' ); ?>');
        
        $.post(ajaxurl, {
            action: 'vapt_generate_client_zip',
            domain: $('#vapt-lock-domain').val(),
            include_settings: $('#vapt-lock-include-settings').is(':checked') ? 1 : 0,
            nonce: '<?php echo wp_create_nonce( "vapt_locked_config" ); ?>' 
        }, function(r){
            btn.prop('disabled', false).text(originalText);
            if(r.success) {
                $('#vapt-generate-msg').html('<span style="color:green"><?php esc_html_e( 'Zip generated!', 'vapt-security' ); ?></span>');
                // Download
                var byteCharacters = atob(r.data.base64);
                var byteNumbers = new Array(byteCharacters.length);
                for (var i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                var byteArray = new Uint8Array(byteNumbers);
                var blob = new Blob([byteArray], {type: "application/zip"});
                
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = r.data.filename;
                link.click();
            } else {
                $('#vapt-generate-msg').html('<span style="color:red">'+r.data.message+'</span>');
            }
        });
    });
});
</script>
