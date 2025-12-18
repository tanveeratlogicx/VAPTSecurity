<?php
/**
 * VAPT License Helper
 *
 * Handles license creation, validation, renewal, and type management.
 */
class VAPT_License {

    const TYPE_STANDARD  = 'standard';
    const TYPE_PRO       = 'pro';
    const TYPE_DEVELOPER = 'developer';

    const OPTION_NAME = 'vapt_license';

    /**
     * Activate the initial license.
     * Should be called on plugin activation.
     */
    public static function activate_license() {
        $license = get_option( self::OPTION_NAME );
        if ( ! $license ) {
            // Create default Standard license (30 days)
            $start   = time();
            $expires = $start + ( 30 * DAY_IN_SECONDS );
            
            $license_data = [
                'type'          => self::TYPE_STANDARD,
                'start'         => $start,
                'expires'       => $expires,
                'auto_renew'    => false,
                'renewal_count' => 0,
            ];
            
            update_option( self::OPTION_NAME, $license_data );
        }
    }

    /**
     * Get current license data.
     *
     * @return array|false License data or false if not found.
     */
    public static function get_license() {
        return get_option( self::OPTION_NAME, false );
    }

    /**
     * update the license details.
     *
     * @param string $type License type (standard, pro, developer).
     * @param int|null $expires expiry timestamp (null for no change/calc automatically based on type if new).
     * @param bool|null $auto_renew Auto renew status.
     * @return bool True on success.
     */
    public static function update_license( $type, $expires = null, $auto_renew = null ) {
        $current = self::get_license();
        if ( ! $current ) {
            // Should exist, but if not, activate first
            self::activate_license();
            $current = self::get_license();
        }

        // Validate type
        if ( ! in_array( $type, [ self::TYPE_STANDARD, self::TYPE_PRO, self::TYPE_DEVELOPER ] ) ) {
            return false;
        }

        $current['type'] = $type;

        // Update expiry?
        if ( $expires !== null ) {
            $current['expires'] = (int) $expires;
        } else {
            // If type changed and no expiry provided, maybe recalculate?
            // For now, we trust the caller to provide expiry if needed, or we keep existing.
            // But if switching to Developer, expiry is irrelevant (or far future).
            if ( $type === self::TYPE_DEVELOPER ) {
                $current['expires'] = 0; // 0 means never expires
            }
        }

        // Update auto_renew?
        if ( $auto_renew !== null ) {
            $current['auto_renew'] = (bool) $auto_renew;
        }
        
        // Ensure count exists
        if ( ! isset( $current['renewal_count'] ) ) {
            $current['renewal_count'] = 0;
        }

        return update_option( self::OPTION_NAME, $current );
    }

    /**
     * Check if license is valid.
     *
     * @return bool
     */
    public static function is_valid() {
        $license = self::get_license();
        if ( ! $license ) {
            return false;
        }

        if ( $license['type'] === self::TYPE_DEVELOPER ) {
            return true;
        }

        if ( ! empty( $license['expires'] ) && time() > $license['expires'] ) {
            // Check Auto Renew
            if ( ! empty( $license['auto_renew'] ) ) {
                self::renew();
                return true; // Renewed successfully
            }
            return false;
        }

        return true;
    }
    
    /**
     * Renew the license for a default term based on its type.
     * 
     * @return bool
     */
    public static function renew() {
        $license = self::get_license();
        if ( ! $license ) {
            return false;
        }
        
        $type = $license['type'];
        $current_expires = !empty($license['expires']) ? $license['expires'] : time();
        // If expired, start from now, else start from expiry
        if ( $current_expires < time() ) {
            $current_expires = time();
        }

        if ( $type === self::TYPE_STANDARD ) {
            $new_expires = $current_expires + ( 30 * DAY_IN_SECONDS );
        } elseif ( $type === self::TYPE_PRO ) {
            $new_expires = $current_expires + ( 365 * DAY_IN_SECONDS );
        } else {
            $new_expires = 0; // Developer
        }
        
        $license['expires'] = $new_expires;
        
        if ( ! isset( $license['renewal_count'] ) ) {
            $license['renewal_count'] = 0;
        }
        $license['renewal_count']++;
        
        return update_option( self::OPTION_NAME, $license );
    }
}
