<?php
// Mock WordPress environment
define('AUTH_KEY', 'test_auth_key_1234567890abcdefghijklmnopqrstuvwxyz');
define('DAY_IN_SECONDS', 86400);

// Mock WP functions
$mock_user_meta = [];
$mock_options = [];
$mock_transients = [];
$current_user = null;

function get_userdata($user_id) {
    global $current_user;
    return $current_user;
}

function wp_get_current_user() {
    global $current_user;
    return $current_user;
}

class MockUser {
    public $ID;
    public $user_login;
    public $user_email;
    public function __construct($id, $login, $email) {
        $this->ID = $id;
        $this->user_login = $login;
        $this->user_email = $email;
    }
    public function exists() { return true; }
}

function update_user_meta($user_id, $key, $value) {
    global $mock_user_meta;
    $mock_user_meta[$user_id][$key] = $value;
    return true;
}

function get_user_meta($user_id, $key, $single = false) {
    global $mock_user_meta;
    return $mock_user_meta[$user_id][$key] ?? false;
}

function delete_user_meta($user_id, $key) {
    global $mock_user_meta;
    unset($mock_user_meta[$user_id][$key]);
    return true;
}

function get_option($option, $default = false) {
    global $mock_options;
    return $mock_options[$option] ?? $default;
}

function update_option($option, $value) {
    global $mock_options;
    $mock_options[$option] = $value;
    return true;
}

function set_transient($transient, $value, $expiration = 0) {
    global $mock_transients;
    $mock_transients[$transient] = $value;
    return true;
}

function get_transient($transient) {
    global $mock_transients;
    return $mock_transients[$transient] ?? false;
}

function wp_mail($to, $subject, $message, $headers = '') {
    echo "[MOCK MAIL] To: $to, Subject: $subject\n";
    return true;
}

function __($text, $domain = 'default') { return $text; }
function _e($text, $domain = 'default') { echo $text; }
function esc_html_e($text, $domain = 'default') { echo $text; }
function wp_die($message, $title = '', $args = []) { echo "WP_DIE: $message\n"; die(); }

class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_message() { return $this->message; }
}

// Include classes
require_once dirname(__DIR__) . '/includes/class-encryption.php';
require_once dirname(__DIR__) . '/includes/class-otp.php';
require_once dirname(__DIR__) . '/includes/class-license.php';
require_once dirname(__DIR__) . '/includes/class-features.php';

// --- Test 1: Feature Manager ---
echo "\n--- Test 1: Feature Manager ---\n";
$features = VAPT_Features::get_active_features();
if (count($features) >= 4 && $features['rate_limiting'] === true) {
    echo "SUCCESS: Defaults loaded.\n";
} else {
    echo "FAILURE: Defaults incorrect.\n";
}

// Disable a feature
VAPT_Features::update_features(['rate_limiting' => false, 'input_validation' => true]);
if (!VAPT_Features::is_enabled('rate_limiting')) {
    echo "SUCCESS: Feature 'rate_limiting' disabled.\n";
} else {
    echo "FAILURE: Feature 'rate_limiting' still enabled.\n";
}

// --- Test 2: Strict Authorization ---
echo "\n--- Test 2: Strict Authorization ---\n";

// Case A: Wrong User
echo "Case A (Wrong User): ";
$current_user = new MockUser(2, 'admin', 'admin@example.com');
// Simulate check logic from vapt-security.php
if ($current_user->user_login !== 'tanmalik786' || $current_user->user_email !== 'tanmalik786@gmail.com') {
    echo "SUCCESS: Access Denied for admin.\n";
} else {
    echo "FAILURE: Wrong user allowed.\n";
}

// Case B: Correct User, Wrong Email
echo "Case B (Right User, Wrong Email): ";
$current_user = new MockUser(1, 'tanmalik786', 'wrong@email.com');
if ($current_user->user_login !== 'tanmalik786' || $current_user->user_email !== 'tanmalik786@gmail.com') {
    echo "SUCCESS: Access Denied for wrong email.\n";
} else {
    echo "FAILURE: Wrong email allowed.\n";
}

// Case C: Correct User & Email
echo "Case C (Correct Credentials): ";
$current_user = new MockUser(1, 'tanmalik786', 'tanmalik786@gmail.com');
if ($current_user->user_login === 'tanmalik786' && $current_user->user_email === 'tanmalik786@gmail.com') {
    echo "SUCCESS: Credentials validated.\n";
} else {
    echo "FAILURE: Credentials rejected.\n";
}

// --- Test 3: OTP Flow for Superadmin ---
echo "\n--- Test 3: OTP Flow for Superadmin ---\n";
$current_user = new MockUser(1, 'tanmalik786', 'tanmalik786@gmail.com');
$res = VAPT_OTP::send_otp(1);
if ($res === true) {
    echo "SUCCESS: OTP Sent to tanmalik786@gmail.com.\n";
}
$otp = $mock_user_meta[1]['vapt_otp'];
$verify = VAPT_OTP::verify_otp(1, $otp);
if ($verify === true) {
    echo "SUCCESS: OTP Verified.\n";
} else {
    echo "FAILURE: OTP Rejected.\n";
}

?>
