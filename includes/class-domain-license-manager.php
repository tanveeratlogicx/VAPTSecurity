<?php
/**
 * VAPT Domain License Manager
 *
 * Handles hidden domain-based license files for pre-activation and domain-specific configuration.
 *
 * @package VAPT_Security
 */

class VAPT_Domain_License_Manager {
    
    /**
     * Hidden license file path (outside web root for security)
     */
    const HIDDEN_LICENSE_FILE = 'vapt-domain-license.json';
    
    /**
     * Special admin endpoint for license management
     */
    const ADMIN_ENDPOINT = 'vapt-domain-license-manager';
    
    /**
     * Initialize the domain license manager
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'maybe_add_hidden_menu']);
        add_action('admin_post_vapt_manage_domain_license', [$this, 'handle_license_management']);
        add_action('init', [$this, 'check_hidden_license']);
    }
    
    /**
     * Check for hidden license file and apply configuration
     */
    public function check_hidden_license() {
        $license_file = $this->get_hidden_license_path();
        
        if (file_exists($license_file)) {
            $license_data = json_decode(file_get_contents($license_file), true);
            
            if ($license_data && $this->validate_domain_license($license_data)) {
                // Apply domain-specific configuration
                $this->apply_domain_configuration($license_data);
            }
        }
    }
    
    /**
     * Validate domain license data
     */
    private function validate_domain_license($license_data) {
        // Check if license is for this domain
        if (isset($license_data['domain']) && $license_data['domain'] !== home_url()) {
            return false;
        }
        
        // Check if license is expired
        if (isset($license_data['expiry']) && time() > strtotime($license_data['expiry'])) {
            return false;
        }
        
        // Check if license is active
        if (isset($license_data['status']) && $license_data['status'] !== 'active') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Apply domain-specific configuration
     */
    private function apply_domain_configuration($license_data) {
        // Apply feature toggles
        if (isset($license_data['features'])) {
            foreach ($license_data['features'] as $feature => $enabled) {
                $constant_name = 'VAPT_FEATURE_' . strtoupper($feature);
                if (!defined($constant_name)) {
                    define($constant_name, $enabled);
                }
            }
        }
        
        // Apply other configuration settings
        if (isset($license_data['config'])) {
            foreach ($license_data['config'] as $key => $value) {
                $constant_name = 'VAPT_' . strtoupper($key);
                if (!defined($constant_name)) {
                    define($constant_name, $value);
                }
            }
        }
    }
    
    /**
     * Get hidden license file path (outside web root)
     */
    private function get_hidden_license_path() {
        // Place file outside web root for security
        return WP_CONTENT_DIR . '/vapt-licenses/' . md5(home_url()) . '_' . self::HIDDEN_LICENSE_FILE;
    }
    
    /**
     * Maybe add hidden menu item (only visible to admins with special capability)
     */
    public function maybe_add_hidden_menu() {
        // Only show menu to administrators
        if (current_user_can('manage_options')) {
            // Add hidden menu item that's not visible in normal menu
            add_submenu_page(
                null, // Parent slug - null makes it hidden
                __('VAPT Domain License', 'vapt-security'),
                __('VAPT Domain License', 'vapt-security'),
                'manage_options',
                self::ADMIN_ENDPOINT,
                [$this, 'render_domain_license_page']
            );
        }
    }
    
    /**
     * Render domain license management page
     */
    public function render_domain_license_page() {
        // Check if user accessed through special URL
        if (!isset($_GET['page']) || $_GET['page'] !== self::ADMIN_ENDPOINT) {
            wp_die(__('Access denied.', 'vapt-security'));
        }
        
        // Handle form submission
        if (isset($_POST['vapt_save_domain_license']) && wp_verify_nonce($_POST['vapt_domain_license_nonce'], 'save_domain_license')) {
            $this->save_domain_license();
        }
        
        $license_file = $this->get_hidden_license_path();
        $license_data = [];
        
        if (file_exists($license_file)) {
            $license_data = json_decode(file_get_contents($license_file), true);
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VAPT Domain License Manager', 'vapt-security'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_domain_license', 'vapt_domain_license_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Domain', 'vapt-security'); ?></th>
                        <td>
                            <input type="text" name="vapt_license_domain" value="<?php echo esc_attr($license_data['domain'] ?? home_url()); ?>" class="regular-text" readonly />
                            <p class="description"><?php esc_html_e('License domain (auto-detected)', 'vapt-security'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('License Status', 'vapt-security'); ?></th>
                        <td>
                            <select name="vapt_license_status">
                                <option value="active" <?php selected($license_data['status'] ?? 'inactive', 'active'); ?>><?php esc_html_e('Active', 'vapt-security'); ?></option>
                                <option value="inactive" <?php selected($license_data['status'] ?? 'inactive', 'inactive'); ?>><?php esc_html_e('Inactive', 'vapt-security'); ?></option>
                                <option value="expired" <?php selected($license_data['status'] ?? 'inactive', 'expired'); ?>><?php esc_html_e('Expired', 'vapt-security'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Expiry Date', 'vapt-security'); ?></th>
                        <td>
                            <input type="date" name="vapt_license_expiry" value="<?php echo esc_attr($license_data['expiry'] ?? date('Y-m-d', strtotime('+1 year'))); ?>" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php esc_html_e('Features', 'vapt-security'); ?></th>
                        <td>
                            <?php
                            $features = [
                                'wp_cron_protection' => __('WP Cron Protection', 'vapt-security'),
                                'rate_limiting' => __('Rate Limiting', 'vapt-security'),
                                'input_validation' => __('Input Validation', 'vapt-security'),
                                'security_logging' => __('Security Logging', 'vapt-security')
                            ];
                            
                            foreach ($features as $feature_key => $feature_name) {
                                $enabled = isset($license_data['features'][$feature_key]) ? $license_data['features'][$feature_key] : true;
                                ?>
                                <label>
                                    <input type="checkbox" name="vapt_license_features[<?php echo esc_attr($feature_key); ?>]" value="1" <?php checked($enabled, true); ?> />
                                    <?php echo esc_html($feature_name); ?>
                                </label><br/>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Domain License', 'vapt-security'), 'primary', 'vapt_save_domain_license'); ?>
            </form>
            
            <hr/>
            
            <h2><?php esc_html_e('Hidden License File', 'vapt-security'); ?></h2>
            <p><?php esc_html_e('License file location:', 'vapt-security'); ?> <code><?php echo esc_html($license_file); ?></code></p>
            
            <?php if (file_exists($license_file)): ?>
                <h3><?php esc_html_e('Current License Data', 'vapt-security'); ?></h3>
                <pre><?php echo esc_html(json_encode($license_data, JSON_PRETTY_PRINT)); ?></pre>
                
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('delete_domain_license', 'vapt_delete_license_nonce'); ?>
                    <input type="hidden" name="vapt_delete_domain_license" value="1" />
                    <?php submit_button(__('Delete Domain License', 'vapt-security'), 'secondary', 'vapt_delete_domain_license', false); ?>
                </form>
            <?php endif; ?>
        </div>
        
        <style>
            .form-table th {
                width: 200px;
            }
            pre {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 10px;
                border-radius: 4px;
                overflow-x: auto;
            }
        </style>
        <?php
    }
    
    /**
     * Save domain license
     */
    private function save_domain_license() {
        if (!isset($_POST['vapt_save_domain_license']) || !wp_verify_nonce($_POST['vapt_domain_license_nonce'], 'save_domain_license')) {
            return;
        }
        
        $license_data = [
            'domain' => home_url(),
            'status' => sanitize_text_field($_POST['vapt_license_status']),
            'expiry' => sanitize_text_field($_POST['vapt_license_expiry']),
            'features' => [],
            'created' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
        
        // Process features
        if (isset($_POST['vapt_license_features']) && is_array($_POST['vapt_license_features'])) {
            foreach ($_POST['vapt_license_features'] as $feature => $enabled) {
                $license_data['features'][sanitize_key($feature)] = (bool) $enabled;
            }
        }
        
        // Ensure license directory exists
        $license_dir = dirname($this->get_hidden_license_path());
        if (!file_exists($license_dir)) {
            wp_mkdir_p($license_dir);
        }
        
        // Save license file
        file_put_contents($this->get_hidden_license_path(), json_encode($license_data, JSON_PRETTY_PRINT));
        
        // Show success message
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . esc_html__('Domain license saved successfully.', 'vapt-security') . '</p></div>';
        });
    }
    
    /**
     * Handle license management via admin post
     */
    public function handle_license_management() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access.', 'vapt-security'));
        }
        
        if (isset($_POST['vapt_delete_domain_license']) && wp_verify_nonce($_POST['vapt_delete_license_nonce'], 'delete_domain_license')) {
            $license_file = $this->get_hidden_license_path();
            if (file_exists($license_file)) {
                unlink($license_file);
                wp_redirect(add_query_arg(['page' => self::ADMIN_ENDPOINT, 'deleted' => 'true'], admin_url('admin.php')));
                exit;
            }
        }
    }
    
    /**
     * Generate sample domain license file
     */
    public function generate_sample_license() {
        $sample_license = [
            'domain' => home_url(),
            'status' => 'active',
            'expiry' => date('Y-m-d', strtotime('+1 year')),
            'features' => [
                'wp_cron_protection' => true,
                'rate_limiting' => false,
                'input_validation' => true,
                'security_logging' => true
            ],
            'created' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
        
        return $sample_license;
    }
}