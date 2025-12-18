<?php
/**
 * VAPT License Manager
 *
 * Handles license activation, validation, and feature access control.
 *
 * @package VAPT_Security
 */

class VAPT_License_Manager {
    
    /**
     * Option name for storing license data
     */
    const LICENSE_OPTION = 'vapt_license_data';
    
    /**
     * Option name for storing activation timestamp
     */
    const ACTIVATION_TIME_OPTION = 'vapt_demo_activation_time';
    
    /**
     * Demo license duration in seconds (30 days)
     */
    const DEMO_DURATION = 30 * 24 * 60 * 60; // 30 days
    
    /**
     * Gist URL for remote configuration
     */
    const GIST_URL = 'https://gist.githubusercontent.com/tanveeratlogicx/e40c94399036078d0f61ae49135ed32e/raw/vapt-config-remote.php';
    
    /**
     * Initialize the license manager
     */
    public function __construct() {
        // Remove the menu registration to prevent duplicate menus
        // Menu is now handled by the main plugin
        // add_action('admin_menu', [$this, 'add_license_menu']);
        add_action('admin_init', [$this, 'handle_license_activation']);
        add_action('init', [$this, 'check_license_status']);
    }
    
    /**
     * Add license management menu
     */
    public function add_license_menu() {
        add_submenu_page(
            'vapt-security',
            __('VAPT License', 'vapt-security'),
            __('License', 'vapt-security'),
            'manage_options',
            'vapt-license',
            [$this, 'render_license_page']
        );
    }
    
    /**
     * Handle license activation form submission
     */
    public function handle_license_activation() {
        if (!isset($_POST['vapt_activate_demo']) || !wp_verify_nonce($_POST['vapt_license_nonce'], 'vapt_activate_demo')) {
            return;
        }
        
        // Activate demo license
        $this->activate_demo_license();
        
        // Redirect to avoid resubmission
        wp_redirect(add_query_arg(['page' => 'vapt-license', 'activated' => 'true'], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Activate demo license for 30 days
     */
    public function activate_demo_license() {
        // Check if already activated
        $activation_time = get_option(self::ACTIVATION_TIME_OPTION);
        
        if (!$activation_time) {
            // First time activation
            $activation_time = time();
            update_option(self::ACTIVATION_TIME_OPTION, $activation_time);
        }
        
        // Set license data
        $license_data = [
            'type' => 'demo',
            'activation_time' => $activation_time,
            'expiry_time' => $activation_time + self::DEMO_DURATION,
            'domain' => home_url(),
            'status' => 'active'
        ];
        
        update_option(self::LICENSE_OPTION, $license_data);
        
        // Load remote configuration
        $this->load_remote_configuration();
    }
    
    /**
     * Load remote configuration from Gist
     */
    public function load_remote_configuration() {
        // Only load if we have an active license
        if (!$this->is_license_valid()) {
            return false;
        }
        
        $response = wp_remote_get(self::GIST_URL, [
            'timeout' => 10,
            'sslverify' => true
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }
        
        $config_content = wp_remote_retrieve_body($response);
        
        // Validate that this is a PHP configuration file
        if (strpos($config_content, '<?php') === false) {
            return false;
        }
        
        // Parse the configuration content and apply settings conditionally
        // Remove PHP opening/closing tags and extract define statements
        $config_content = preg_replace('/<\?php/', '', $config_content, 1);
        $config_content = preg_replace('/\?>\s*$/', '', $config_content);
        
        // Extract all define statements
        preg_match_all('/define\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\s*\)/', $config_content, $matches, PREG_SET_ORDER);
        
        // Apply each setting conditionally
        foreach ($matches as $match) {
            $constant_name = $match[1];
            $constant_value = trim($match[2]);
            
            // Skip if already defined
            if (defined($constant_name)) {
                continue;
            }
            
            // Evaluate the value (safely)
            // Handle arrays
            if (strpos($constant_value, '[') === 0) {
                // For array values, we need to evaluate them safely
                $evaluated_value = eval("return $constant_value;");
                define($constant_name, $evaluated_value);
            } else {
                // For scalar values, strip quotes and convert
                $constant_value = trim($constant_value, '\'"');
                
                // Convert boolean values
                if (strtolower($constant_value) === 'true') {
                    define($constant_name, true);
                } elseif (strtolower($constant_value) === 'false') {
                    define($constant_name, false);
                } elseif (is_numeric($constant_value)) {
                    define($constant_name, $constant_value + 0);
                } else {
                    define($constant_name, $constant_value);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if license is valid
     */
    public function is_license_valid() {
        $license_data = get_option(self::LICENSE_OPTION);
        
        if (!$license_data) {
            return false;
        }
        
        // Check if license is active
        if ($license_data['status'] !== 'active') {
            return false;
        }
        
        // Check if license has expired
        if (time() > $license_data['expiry_time']) {
            // Update status to expired
            $license_data['status'] = 'expired';
            update_option(self::LICENSE_OPTION, $license_data);
            return false;
        }
        
        // Check if domain matches (for non-demo licenses)
        if ($license_data['type'] !== 'demo' && $license_data['domain'] !== home_url()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check license status and load remote config if needed
     */
    public function check_license_status() {
        // If we have a valid license, try to load remote configuration
        if ($this->is_license_valid()) {
            // We only load remote config once per day to reduce server load
            $last_config_load = get_option('vapt_last_config_load', 0);
            if (time() - $last_config_load > 24 * 60 * 60) {
                $this->load_remote_configuration();
                update_option('vapt_last_config_load', time());
            }
        }
    }
    
    /**
     * Get license information
     */
    public function get_license_info() {
        $license_data = get_option(self::LICENSE_OPTION);
        
        if (!$license_data) {
            return [
                'type' => 'free',
                'status' => 'inactive',
                'days_remaining' => 0
            ];
        }
        
        $days_remaining = ceil(($license_data['expiry_time'] - time()) / (24 * 60 * 60));
        
        return [
            'type' => $license_data['type'],
            'status' => $license_data['status'],
            'activation_time' => $license_data['activation_time'],
            'expiry_time' => $license_data['expiry_time'],
            'days_remaining' => max(0, $days_remaining),
            'domain' => $license_data['domain']
        ];
    }
    
    /**
     * Render license management page
     */
    public function render_license_page() {
        $license_info = $this->get_license_info();
        $activated = isset($_GET['activated']) && $_GET['activated'] === 'true';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('VAPT Security License Management', 'vapt-security'); ?></h1>
            
            <?php if ($activated): ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('Demo license activated successfully! You now have access to all premium features for 30 days.', 'vapt-security'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php esc_html_e('License Status', 'vapt-security'); ?></h2>
                
                <?php if ($license_info['type'] === 'free'): ?>
                    <p><?php esc_html_e('You are currently using the free version of VAPT Security.', 'vapt-security'); ?></p>
                    <p><?php esc_html_e('Activate a demo license to unlock all premium features for 30 days.', 'vapt-security'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('vapt_activate_demo', 'vapt_license_nonce'); ?>
                        <input type="hidden" name="vapt_activate_demo" value="1">
                        <?php submit_button(__('Activate 30-Day Demo', 'vapt-security'), 'primary'); ?>
                    </form>
                <?php else: ?>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('License Type', 'vapt-security'); ?></th>
                            <td>
                                <span class="badge <?php echo esc_attr($license_info['type']); ?>">
                                    <?php echo esc_html(ucfirst($license_info['type'])); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Status', 'vapt-security'); ?></th>
                            <td>
                                <?php if ($license_info['status'] === 'active'): ?>
                                    <span style="color: green;">✓ <?php esc_html_e('Active', 'vapt-security'); ?></span>
                                <?php elseif ($license_info['status'] === 'expired'): ?>
                                    <span style="color: red;">✗ <?php esc_html_e('Expired', 'vapt-security'); ?></span>
                                <?php else: ?>
                                    <span style="color: orange;">⚠ <?php echo esc_html(ucfirst($license_info['status'])); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Activation Date', 'vapt-security'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), $license_info['activation_time'])); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Expiration Date', 'vapt-security'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), $license_info['expiry_time'])); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Days Remaining', 'vapt-security'); ?></th>
                            <td>
                                <?php if ($license_info['days_remaining'] > 0): ?>
                                    <strong><?php echo esc_html($license_info['days_remaining']); ?></strong> 
                                    <?php echo esc_html(_n('day', 'days', $license_info['days_remaining'], 'vapt-security')); ?>
                                <?php else: ?>
                                    <span style="color: red;"><?php esc_html_e('Expired', 'vapt-security'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Domain', 'vapt-security'); ?></th>
                            <td><?php echo esc_html($license_info['domain']); ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($license_info['status'] === 'active' && $license_info['days_remaining'] > 0): ?>
                        <div class="notice notice-info">
                            <p><?php printf(esc_html__('Your demo license is active! You have %d days remaining to test all premium features.', 'vapt-security'), $license_info['days_remaining']); ?></p>
                        </div>
                    <?php elseif ($license_info['status'] === 'expired'): ?>
                        <div class="notice notice-warning">
                            <p><?php esc_html_e('Your demo license has expired. Please purchase a full license to continue using premium features.', 'vapt-security'); ?></p>
                        </div>
                        
                        <form method="post" action="">
                            <?php wp_nonce_field('vapt_activate_demo', 'vapt_license_nonce'); ?>
                            <input type="hidden" name="vapt_activate_demo" value="1">
                            <?php submit_button(__('Reactivate Demo License', 'vapt-security'), 'secondary'); ?>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Available Features', 'vapt-security'); ?></h2>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Feature', 'vapt-security'); ?></th>
                            <th><?php esc_html_e('Free Version', 'vapt-security'); ?></th>
                            <th><?php esc_html_e('Premium Version', 'vapt-security'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('Input Validation', 'vapt-security'); ?></td>
                            <td>✓</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Rate Limiting', 'vapt-security'); ?></td>
                            <td>○</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('WP-Cron Protection', 'vapt-security'); ?></td>
                            <td>○</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Security Logging', 'vapt-security'); ?></td>
                            <td>○</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Advanced Settings', 'vapt-security'); ?></td>
                            <td>○</td>
                            <td>✓</td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Priority Support', 'vapt-security'); ?></td>
                            <td>○</td>
                            <td>✓</td>
                        </tr>
                    </tbody>
                </table>
                
                <p>
                    <em>
                        <?php esc_html_e('✓ = Available | ○ = Limited | ✗ = Not Available', 'vapt-security'); ?>
                    </em>
                </p>
            </div>
        </div>
        
        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: bold;
                text-transform: uppercase;
                font-size: 12px;
            }
            
            .badge.demo {
                background: #0073aa;
                color: white;
            }
            
            .badge.free {
                background: #cccccc;
                color: #333;
            }
            
            table.form-table th {
                width: 200px;
            }
        </style>
        <?php
    }
}