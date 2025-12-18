# License Management Admin Interface

This document describes how to implement a license management interface in the WordPress admin for the VAPT Security plugin.

## Admin Menu Integration

Add a license management section to the existing plugin admin menu:

```php
// Add to main plugin file
function vapt_add_license_submenu() {
    add_submenu_page(
        'vapt-security',
        'License Management',
        'License',
        'manage_options',
        'vapt-license',
        'vapt_license_management_page'
    );
}
add_action('admin_menu', 'vapt_add_license_submenu');

function vapt_license_management_page() {
    // Handle form submission
    if (isset($_POST['vapt_license_key'])) {
        $license_key = sanitize_text_field($_POST['vapt_license_key']);
        update_option('vapt_license_key', $license_key);
        
        // Validate license
        if (vapt_validate_license($license_key)) {
            update_option('vapt_license_valid', true);
            echo '<div class="notice notice-success"><p>License activated successfully!</p></div>';
        } else {
            update_option('vapt_license_valid', false);
            echo '<div class="notice notice-error"><p>Invalid license key.</p></div>';
        }
    }
    
    $current_license = get_option('vapt_license_key', '');
    $is_valid = get_option('vapt_license_valid', false);
    ?>
    <div class="wrap">
        <h1>VAPT Security License Management</h1>
        
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">License Key</th>
                    <td>
                        <input type="text" name="vapt_license_key" value="<?php echo esc_attr($current_license); ?>" class="regular-text" />
                        <p class="description">Enter your VAPT Security license key</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">License Status</th>
                    <td>
                        <?php if ($is_valid): ?>
                            <span style="color: green;">✓ Active</span>
                        <?php else: ?>
                            <span style="color: red;">✗ Inactive</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button('Activate License'); ?>
        </form>
        
        <h2>License Information</h2>
        <?php if ($is_valid): ?>
            <table class="widefat">
                <tr>
                    <th>Feature</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Input Validation</td>
                    <td><span style="color: green;">Enabled</span></td>
                </tr>
                <tr>
                    <td>Rate Limiting</td>
                    <td><span style="color: green;">Enabled</span></td>
                </tr>
                <tr>
                    <td>WP-Cron Protection</td>
                    <td><span style="color: green;">Enabled</span></td>
                </tr>
                <tr>
                    <td>Security Logging</td>
                    <td><span style="color: green;">Enabled</span></td>
                </tr>
            </table>
        <?php else: ?>
            <p>Activate your license to unlock all premium features:</p>
            <ul>
                <li>Advanced Rate Limiting</li>
                <li>WP-Cron Protection</li>
                <li>Comprehensive Security Logging</li>
                <li>Priority Support</li>
            </ul>
            <p><a href="https://yourcompany.com/vapt-security-license" class="button button-primary">Get License Key</a></p>
        <?php endif; ?>
    </div>
    <?php
}
```

## License Status Widget

Add a license status widget to the main plugin dashboard:

```php
// Add to the main settings page
function vapt_add_license_status_widget() {
    $is_valid = get_option('vapt_license_valid', false);
    ?>
    <div class="postbox">
        <h3 class="hndle"><span>License Status</span></h3>
        <div class="inside">
            <?php if ($is_valid): ?>
                <p style="color: green;">✓ Your license is active and all features are unlocked.</p>
            <?php else: ?>
                <p style="color: orange;">⚠ License not activated. Some features are disabled.</p>
                <p><a href="<?php echo admin_url('admin.php?page=vapt-license'); ?>">Activate License</a> to unlock all features.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
// Call this function in your main settings page template
```

## Feature Access Control

Implement feature access control based on license status:

```php
// In your main plugin file, modify feature registration
function register_premium_features() {
    // Always register free features
    add_settings_section(/* Free feature registration */);
    
    // Only register premium features if licensed
    if (get_option('vapt_license_valid', false)) {
        add_settings_section(/* Premium feature registration */);
    } else {
        // Show upgrade notice instead
        add_settings_section(
            'vapt_upgrade_notice',
            'Premium Features (Upgrade Required)',
            'vapt_render_upgrade_notice',
            'vapt_security_upgrade'
        );
    }
}

function vapt_render_upgrade_notice() {
    echo '<p>Unlock premium features with a valid license:</p>';
    echo '<ul>';
    echo '<li>Advanced Rate Limiting</li>';
    echo '<li>WP-Cron Protection</li>';
    echo '<li>Comprehensive Security Logging</li>';
    echo '</ul>';
    echo '<p><a href="' . admin_url('admin.php?page=vapt-license') . '" class="button button-primary">Activate License</a></p>';
}
```

## License Expiration Handling

Handle license expiration gracefully:

```php
function vapt_check_license_expiration() {
    $license_data = get_option('vapt_license_data', array());
    
    if (empty($license_data)) {
        return false;
    }
    
    $expiry_date = isset($license_data['expiry']) ? $license_data['expiry'] : 0;
    
    if (time() > $expiry_date) {
        // License expired
        update_option('vapt_license_valid', false);
        return false;
    }
    
    // Check if license expires within 30 days
    $warning_period = $expiry_date - (30 * 24 * 60 * 60); // 30 days
    
    if (time() > $warning_period) {
        // Add admin notice for upcoming expiration
        add_action('admin_notices', 'vapt_license_expiration_warning');
    }
    
    return true;
}

function vapt_license_expiration_warning() {
    echo '<div class="notice notice-warning">';
    echo '<p>Your VAPT Security license expires soon. <a href="https://yourcompany.com/renew">Renew now</a> to continue receiving updates and support.</p>';
    echo '</div>';
}
```

## Admin Notices

Add informative admin notices:

```php
// Add to plugin initialization
add_action('admin_notices', 'vapt_admin_notices');

function vapt_admin_notices() {
    // Only show notices on VAPT Security pages
    $screen = get_current_screen();
    if (strpos($screen->id, 'vapt-security') === false) {
        return;
    }
    
    $is_valid = get_option('vapt_license_valid', false);
    
    if (!$is_valid) {
        echo '<div class="notice notice-info">';
        echo '<p>🔒 Unlock all VAPT Security features with a premium license. <a href="' . admin_url('admin.php?page=vapt-license') . '">Activate now</a></p>';
        echo '</div>';
    }
}
```

This admin interface provides users with a clear way to manage their license and understand what features are available to them based on their license status.