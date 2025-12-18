<?php
/**
 * VAPT Encryption Helper
 *
 * Provides static methods to encrypt and decrypt data using OpenSSL.
 * The encryption key is derived from the WordPress AUTH_KEY constant.
 */
class VAPT_Encryption {
    /**
     * Get the encryption key.
     *
     * @return string 32-byte key for AES-256.
     */
    private static function get_key() {
        // Use the first 32 bytes of AUTH_KEY (or pad if shorter).
        $key = defined('AUTH_KEY') ? AUTH_KEY : '';
        if (strlen($key) < 32) {
            $key = str_pad($key, 32, '0');
        } else {
            $key = substr($key, 0, 32);
        }
        return $key;
    }

    /**
     * Encrypt data.
     *
     * @param string $data Plain text data.
     * @return string Base64 encoded ciphertext with IV.
     */
    public static function encrypt($data) {
        $key = self::get_key();
        $iv_len = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($iv_len);
        $cipher = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        // Store IV + cipher, then base64 encode.
        return base64_encode($iv . $cipher);
    }

    /**
     * Decrypt data.
     *
     * @param string $enc Base64 encoded ciphertext with IV.
     * @return string|false Decrypted plain text or false on failure.
     */
    public static function decrypt($enc) {
        $key = self::get_key();
        $data = base64_decode($enc);
        if ($data === false) {
            return false;
        }
        $iv_len = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($data, 0, $iv_len);
        $cipher = substr($data, $iv_len);
        return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
?>
