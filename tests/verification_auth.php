<?php
// Mock WordPress environment
define('AUTH_KEY', 'test_auth_key_1234567890abcdefghijklmnopqrstuvwxyz');
define('DAY_IN_SECONDS', 86400);

// Mock WP functions
$mock_user_meta = [];
$mock_options = [];

function get_userdata($user_id) {
    if ($user_id == 1) {
        return (object) ['user_email' => 'test@example.com'];
    }
    return false;
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

function wp_mail($to, $subject, $message, $headers = '') {
    echo "[MOCK MAIL] To: $to, Subject: $subject, Body: $message\n";
    return true;
}

function __($text, $domain = 'default') { return $text; }
function _e($text, $domain = 'default') { echo $text; }
class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }
    public function get_error_message() { return $this->message; }
}

// Include classes
require_once dirname(__DIR__) . '/includes/class-encryption.php';
require_once dirname(__DIR__) . '/includes/class-otp.php';
require_once dirname(__DIR__) . '/includes/class-license.php';

// Test Encryption
echo "\n--- Testing Encryption ---\n";
$data = json_encode(['foo' => 'bar']);
$encrypted = VAPT_Encryption::encrypt($data);
echo "Encrypted: $encrypted\n";
$decrypted = VAPT_Encryption::decrypt($encrypted);
echo "Decrypted: $decrypted\n";
if ($data === $decrypted) {
    echo "SUCCESS: Encryption round-trip works.\n";
} else {
    echo "FAILURE: Encryption round-trip failed.\n";
}

// Test OTP
echo "\n--- Testing OTP ---\n";
$res = VAPT_OTP::send_otp(1);
if ($res === true) {
    echo "SUCCESS: OTP Sent.\n";
} else {
    echo "FAILURE: OTP Send failed.\n";
}

$stored_otp = $mock_user_meta[1]['vapt_otp'];
echo "Stored OTP: $stored_otp\n";

// Verify wrong OTP
$verify_bad = VAPT_OTP::verify_otp(1, '000000');
if (is_a($verify_bad, 'WP_Error')) {
    echo "SUCCESS: Invalid OTP rejected.\n";
} else {
    echo "FAILURE: Invalid OTP accepted.\n";
}

// Verify correct OTP
$verify_good = VAPT_OTP::verify_otp(1, $stored_otp);
if ($verify_good === true) {
    echo "SUCCESS: Valid OTP accepted.\n";
} else {
    echo "FAILURE: Valid OTP rejected.\n"; // . $verify_good->get_error_message() . "\n";
}

// Enable re-use check (should act as consumed)
$verify_replay = VAPT_OTP::verify_otp(1, $stored_otp);
if (is_a($verify_replay, 'WP_Error')) {
    echo "SUCCESS: Replayed OTP rejected (consumed).\n";
} else {
    echo "FAILURE: Replayed OTP accepted.\n";
}

// Test License
echo "\n--- Testing License ---\n";
VAPT_License::activate_license();
$lic = VAPT_License::get_license();
print_r($lic);
if ($lic['type'] === 'standard' && $lic['expires'] > time()) {
    echo "SUCCESS: Standard license activated.\n";
} else {
    echo "FAILURE: License activation mismatch.\n";
}

// Update to Pro
VAPT_License::update_license('pro', time() + (365 * DAY_IN_SECONDS), true);
$lic = VAPT_License::get_license();
if ($lic['type'] === 'pro' && $lic['auto_renew'] === true) {
    echo "SUCCESS: License updated to Pro.\n";
} else {
    echo "FAILURE: License update failed.\n";
}

// Renew
VAPT_License::renew();
$lic2 = VAPT_License::get_license();
echo "Expiry after renew: " . date('Y-m-d', $lic2['expires']) . "\n";
if ($lic2['expires'] > $lic['expires']) {
    echo "SUCCESS: License renewed.\n";
} else {
    echo "FAILURE: License renewal failed.\n";
}

?>
