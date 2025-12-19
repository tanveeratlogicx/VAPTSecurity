<?php
/**
 * Test Integrations Mock - CLI
 * Run with: php test-integrations-mock.php
 */

// MOCK WP Core
function add_action($hook, $callback, $priority = 10, $args = 1) { echo "Added action: $hook\n"; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { echo "Added filter: $hook\n"; }
function get_option($name, $default = []) { 
    return [
        'validation_sanitization_level' => 'strict',
        'vapt_integration_cf7' => 1,
        'vapt_integration_elementor' => 1,
        'vapt_integration_wpforms' => 1,
        'vapt_integration_gravity' => 1,
    ]; 
}
function sanitize_text_field($str) { return strip_tags($str); }
function sanitize_textarea_field($str) { return strip_tags($str); }
function wp_strip_all_tags($str) { return strip_tags($str); }
function __($str) { return $str; }

class WP_Error {
    public $code, $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($thing) { return $thing instanceof WP_Error; }

// MOCK Integration Classes
class WPCF7_Validation {
    public function invalidate($tag, $message) { echo "CF7 Invalidate: $message\n"; }
}
class Elementor_Ajax_Handler {
    public function add_error($id, $message) { echo "Elementor Error [$id]: $message\n"; }
}
function rgpost($key) { return $_POST[$key] ?? ''; }

// DEFINE CONSTANTS
define('ABSPATH', true);
define('VAPT_FEATURE_INPUT_VALIDATION', true);

// INCLUDE CLASSES (Adjust paths if needed for CLI)
require_once 'includes/class-input-validator.php';
require_once 'includes/class-integrations-manager.php';

// START TEST
echo "=== Starting Integration Tests ===\n";

$manager = new VAPT_Integrations_Manager();
$manager->init();

// 1. Test Validator Direct
echo "\n--- Test Input Validator (Strict) ---\n";
$validator = new VAPT_Input_Validator();
$bad_input = '<script>alert(1)</script>';
$clean_input = 'Hello World';

$err = $validator->check_security_violations($bad_input);
if (is_wp_error($err)) { echo "[PASS] Blocked script tag: " . $err->get_error_message() . "\n"; }
else { echo "[FAIL] Did not block script tag\n"; }

$err = $validator->check_security_violations($clean_input);
if ($err === false) { echo "[PASS] Allowed clean input\n"; }
else { echo "[FAIL] Blocked clean input\n"; }

// 2. Test CF7 Integration Logic
echo "\n--- Test CF7 Logic ---\n";
$_POST['your-message'] = '<script>alert("xss")</script>'; // Simulate POST
$cf7_result = new WPCF7_Validation();
$manager->handle_cf7_validation($cf7_result, null);

// 3. Test Elementor Logic
echo "\n--- Test Elementor Logic ---\n";
$record = new class {
    public function get($key) { 
        return ['fields' => ['field_1' => ['value' => '<script>alert(1)</script>']]]; 
    }
};
$handler = new Elementor_Ajax_Handler();
$manager->handle_elementor_validation($record, $handler);

// 4. Test WPForms Logic
echo "\n--- Test WPForms Logic ---\n";
// Mock wpforms() global
class WPForms_Process { public $errors = []; }
class WPForms { public $process; public function __construct() { $this->process = new WPForms_Process(); } }
function wpforms() { static $i; if(!$i) $i = new WPForms(); return $i; }

$entry_fields = ['fields' => [1 => '<script>alert(1)</script>']];
$manager->handle_wpforms_validation($entry_fields, ['id' => 123]);
if (!empty(wpforms()->process->errors[123][1])) {
    echo "WPForms Error: " . wpforms()->process->errors[123][1] . "\n";
}

echo "=== Tests Finished ===\n";
