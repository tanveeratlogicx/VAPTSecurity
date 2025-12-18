<?php
// Mock WordPress environment
define('AUTH_KEY', 'test_auth_key_1234567890abcdefghijklmnopqrstuvwxyz');
define('DAY_IN_SECONDS', 86400);

// Mock WP Globals
$mock_user_meta = [];
$mock_transients = [];
$current_user = null;

// Mock Functions
function wp_get_current_user() { global $current_user; return $current_user; }
function get_transient($t) { global $mock_transients; return $mock_transients[$t] ?? false; }
class MockUser {
    public $ID, $user_login, $user_email;
    public function __construct($id, $l, $e) { $this->ID=$id; $this->user_login=$l; $this->user_email=$e; }
}

// Logic to verify
// We are mimicking the logic inside templates/admin-settings.php

echo "\n--- Test: Superadmin Gateway Logic ---\n";

// Case 1: Regular Admin
echo "Case 1: Regular Admin... ";
$current_user = new MockUser(2, 'admin', 'admin@example.com');
$is_superadmin = ( $current_user->user_login === 'tanmalik786' && $current_user->user_email === 'tanmalik786@gmail.com' );
if (!$is_superadmin) {
    echo "SUCCESS (Not detected as superadmin)\n";
} else {
    echo "FAILURE (Detected as superadmin)\n";
}

// Case 2: Superadmin (Unverified)
echo "Case 2: Superadmin (Unverified)... ";
$current_user = new MockUser(1, 'tanmalik786', 'tanmalik786@gmail.com');
$is_superadmin = ( $current_user->user_login === 'tanmalik786' && $current_user->user_email === 'tanmalik786@gmail.com' );
$is_verified_super = $is_superadmin ? get_transient( 'vapt_auth_' . $current_user->ID ) : false;

if ($is_superadmin && !$is_verified_super) {
    echo "SUCCESS (Detected superadmin, unverified)\n";
    echo "Effect: Would show OTP Form.\n";
} else {
    echo "FAILURE.\n";
}

// Case 3: Superadmin (Verified)
echo "Case 3: Superadmin (Verified)... ";
$mock_transients['vapt_auth_1'] = true; // Simulate OTP success
$is_verified_super = $is_superadmin ? get_transient( 'vapt_auth_' . $current_user->ID ) : false;

if ($is_superadmin && $is_verified_super) {
    echo "SUCCESS (Verified)\n";
    echo "Effect: Would show 'Manage Domain Features' Link.\n";
} else {
    echo "FAILURE.\n";
}
?>
