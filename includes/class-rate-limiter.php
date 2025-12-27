<?php

/**
 * Rate Limiter.
 *
 * @package VAPT_Security
 */

if (! defined('ABSPATH')) {
    exit;
}

class VAPT_Rate_Limiter
{

    const OPTION_KEY          = 'vapt_rate_limit';

    const CRON_OPTION_KEY     = 'vapt_cron_rate_limit';
    const BLOCKED_IPS_KEY     = 'vapt_blocked_ips';
    const WINDOW_MINUTES      = 1;          // 1 minute
    const CRON_WINDOW_HOURS   = 1;          // 1 hour
    const MAX_REQUESTS_PER_MIN = 10;        // default
    const MAX_CRON_REQUESTS_PER_HOUR = 60;  // default

    private $data = [];
    private $cron_data = [];
    private $lock_handle;

    public function __construct()
    {
        $stored = get_option(self::OPTION_KEY, '[]');
        $this->data = json_decode($stored, true);
        if (! is_array($this->data)) {
            $this->data = [];
        }

        $stored_cron = get_option(self::CRON_OPTION_KEY, '[]');
        $this->cron_data = json_decode($stored_cron, true);
        if (! is_array($this->cron_data)) {
            $this->cron_data = [];
        }
    }

    /**
     * Check if a regular request is allowed
     */
    public function allow_request(): bool
    {
        $ip   = $this->get_current_ip();
        $now  = time();

        if (! isset($this->data[$ip])) {
            $this->data[$ip] = [];
        }

        // Get settings
        $options = VAPT_Security::instance()->get_config();
        $window_minutes = isset($options['rate_limit_window']) ? absint($options['rate_limit_window']) : self::WINDOW_MINUTES;
        $max_requests = isset($options['rate_limit_max']) ? absint($options['rate_limit_max']) : self::MAX_REQUESTS_PER_MIN;

        // Keep only timestamps within the window
        $this->data[$ip] = array_filter(
            $this->data[$ip],
            fn($ts) => ($now - $ts) <= $window_minutes * 60
        );

        // Check if limit exceeded
        if (count($this->data[$ip]) >= $max_requests) {
            // Block IP if too many violations
            $this->check_and_block_ip($ip);
            return false;
        }

        $this->data[$ip][] = $now;
        $this->save();
        return true;
    }

    /**
     * Check if a cron request is allowed
     */
    public function allow_cron_request(): bool
    {
        $upload_dir = wp_upload_dir();
        $data_file = $upload_dir['basedir'] . '/vapt_cron_data.json';


        // Use c+ to open for read/write and create if doesn't exist.
        // This is atomic and avoids race conditions between file_exists and fopen.
        // Using retry mechanism for Windows concurrency.
        $fp = $this->open_with_retry($data_file, 'c+');
        if (!$fp) {
            error_log("VAPT Security: FAILED to open cron data file after retries: $data_file");
            return true; // Fallback
        }

        // Exclusive Lock
        if (!flock($fp, LOCK_EX)) {
            error_log("VAPT Security: FAILED to acquire exclusive lock on cron data file.");
            fclose($fp);
            return true;
        }

        // Read Data
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        $cron_data = json_decode($content, true);
        if (!is_array($cron_data)) {
            $cron_data = [];
        }

        $ip   = $this->get_current_ip();
        $now  = time();

        if (!isset($cron_data[$ip])) {
            $cron_data[$ip] = [];
        }

        // Get settings
        $options = VAPT_Security::instance()->get_config();
        $max_cron_requests = isset($options['cron_rate_limit']) ? absint($options['cron_rate_limit']) : self::MAX_CRON_REQUESTS_PER_HOUR;


        // Keep only timestamps within the window (1 hour)
        $cron_data[$ip] = array_filter(
            $cron_data[$ip],
            fn($ts) => ($now - $ts) <= self::CRON_WINDOW_HOURS * 3600
        );

        // Re-index array to prevent JSON becoming an object with numeric keys
        $cron_data[$ip] = array_values($cron_data[$ip]);

        $allowed = true;
        // Check if limit exceeded
        if (count($cron_data[$ip]) >= $max_cron_requests) {
            $allowed = false;
            // Block IP
            $this->block_ip($ip);
        } else {
            $cron_data[$ip][] = $now;
        }

        // Save back to file
        rewind($fp); // Ensure pointer is at start for ftruncate on some systems
        ftruncate($fp, 0);
        rewind($fp); // Ensure pointer is at start for fwrite
        fwrite($fp, json_encode($cron_data));
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);

        return $allowed;
    }



    /**
     * Clean old entries
     */
    public function clean_old_entries()
    {
        $now = time();
        $changed = false;

        // Clean regular rate limit data
        foreach ($this->data as $ip => $timestamps) {
            $new = array_filter(
                $timestamps,
                fn($ts) => ($now - $ts) <= self::WINDOW_MINUTES * 60
            );
            if (count($new) !== count($timestamps)) {
                $changed = true;
                $this->data[$ip] = $new;
            }
        }

        if ($changed) {
            $this->save();
        }

        // Clean cron rate limit data (File based)
        $upload_dir = wp_upload_dir();
        $data_file = $upload_dir['basedir'] . '/vapt_cron_data.json';
        if (file_exists($data_file)) {
            $fp = $this->open_with_retry($data_file, 'r+');
            if ($fp && flock($fp, LOCK_EX)) {
                $content = '';
                while (!feof($fp)) {
                    $chunk = fread($fp, 8192);
                    if ($chunk === false) break;
                    $content .= $chunk;
                }
                $cron_data = json_decode($content, true);

                if (is_array($cron_data)) {
                    $file_changed = false;
                    foreach ($cron_data as $ip => $timestamps) {
                        $new = array_filter(
                            $timestamps,
                            fn($ts) => ($now - $ts) <= self::CRON_WINDOW_HOURS * 3600
                        );
                        if (count($new) !== count($timestamps)) {
                            $file_changed = true;
                            $cron_data[$ip] = $new;
                        }
                    }

                    if ($file_changed) {
                        ftruncate($fp, 0);
                        rewind($fp);
                        fwrite($fp, json_encode($cron_data));
                        fflush($fp);
                    }
                }

                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }

    /**
     * Check if IP should be blocked and block if necessary
     */
    private function check_and_block_ip($ip)
    {
        // Count violations for this IP
        $violations = get_option('vapt_ip_violations', []);

        if (! isset($violations[$ip])) {
            $violations[$ip] = 0;
        }

        $violations[$ip]++;

        // Block IP if too many violations (more than 5 in an hour)
        if ($violations[$ip] > 5) {
            $this->block_ip($ip);
        }

        update_option('vapt_ip_violations', $violations, false);
    }

    /**
     * Block an IP address
     */
    public function block_ip($ip)
    {
        $blocked_ips = get_option(self::BLOCKED_IPS_KEY, []);
        $blocked_ips[$ip] = time(); // Store timestamp of blocking
        update_option(self::BLOCKED_IPS_KEY, $blocked_ips, false);

        // Log the blocking event
        if (class_exists('VAPT_Security_Logger')) {
            $logger = new VAPT_Security_Logger();
            $logger->log_event('ip_blocked', [
                'ip' => $ip,
                'reason' => 'rate_limit_violation'
            ]);
        }
    }

    /**
     * Get current IP address
     */
    public function get_current_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Handle Cloudflare
        if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        // Handle proxy
        elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip  = trim($ips[0]);
        }
        // Handle alternative proxy header
        elseif (! empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_REAL_IP']);
        }

        return $ip;
    }

    /**
     * Save regular rate limit data
     */
    private function save()
    {
        update_option(self::OPTION_KEY, wp_json_encode($this->data), false);
    }

    /**
     * Get rate limit statistics
     */
    public function get_stats()
    {
        // Get Cron Data from file
        $cron_data = [];
        $upload_dir = wp_upload_dir();
        $data_file = $upload_dir['basedir'] . '/vapt_cron_data.json';
        if (file_exists($data_file)) {
            $fp = $this->open_with_retry($data_file, 'r');
            if ($fp && flock($fp, LOCK_SH)) { // Shared lock for reading
                $content = '';
                while (!feof($fp)) {
                    $chunk = fread($fp, 8192);
                    if ($chunk === false) break;
                    $content .= $chunk;
                }
                $cron_data = json_decode($content, true);
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
        if (!is_array($cron_data)) {
            $cron_data = [];
        }

        return [
            'regular_requests' => $this->data,
            'cron_requests' => $cron_data
        ];
    }

    /**
     * Reset rate limit data for an IP
     */
    public function reset_ip_data($ip)
    {
        if (isset($this->data[$ip])) {
            unset($this->data[$ip]);
            $this->save();
        }

        // Reset Cron (File based)
        $upload_dir = wp_upload_dir();
        $data_file = $upload_dir['basedir'] . '/vapt_cron_data.json';
        if (file_exists($data_file)) {
            $fp = $this->open_with_retry($data_file, 'r+');
            if ($fp && flock($fp, LOCK_EX)) {
                $content = '';
                while (!feof($fp)) {
                    $chunk = fread($fp, 8192);
                    if ($chunk === false) break;
                    $content .= $chunk;
                }
                $cron_data = json_decode($content, true);

                if (is_array($cron_data) && isset($cron_data[$ip])) {
                    unset($cron_data[$ip]);
                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, json_encode($cron_data));
                    fflush($fp);
                }

                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }
    /**
     * Helper to open file with retry logic to handle Windows file locking concurrency.
     *
     * @param string $file Path to file.
     * @param string $mode Mode for fopen.
     * @param int $max_retries Number of times to retry.
     * @return resource|false File pointer or false on failure.
     */
    private function open_with_retry($file, $mode, $max_retries = 50)
    {
        $fp = false;
        for ($i = 0; $i < $max_retries; $i++) {
            // Suppress warnings because fopen failure on locked file is expected
            $fp = @fopen($file, $mode);
            if ($fp) {
                return $fp;
            }
            // Sleep for random time between 50ms and 150ms to reduce contention
            usleep(rand(50000, 150000));
        }
        return false;
    }
}
