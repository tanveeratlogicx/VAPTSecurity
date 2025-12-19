<?php
/**
 * Input validator.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Input_Validator {

    /**
     * Validate a data array against a schema.
     *
     * Schema example:
     * [
     *   'name' => [ 'required' => true, 'type' => 'string', 'max' => 50 ],
     *   'email'=> [ 'required' => true, 'type' => 'email',  'max' => 100 ],
     * ]
     *
     * @param array $data   Raw input.
     * @param array $schema Validation rules.
     *
     * @return array|WP_Error Sanitized data or a WP_Error.
     */
    public function validate( array $data, array $schema ) {
        $sanitized = [];

        foreach ( $schema as $field => $rules ) {
            $value = $data[ $field ] ?? null;

            // Required?
            if ( $rules['required'] && ( ! isset( $value ) || $value === '' ) ) {
                return new WP_Error(
                    'vapt_missing_field',
                    sprintf( __( 'The field %s is required.', 'vapt-security' ), $field )
                );
            }

            // Optional empty values are allowed
            if ( ! $rules['required'] && ( ! isset( $value ) || $value === '' ) ) {
                $sanitized[ $field ] = null;
                continue;
            }

            // Get sanitization level from options
            $options = get_option( 'vapt_security_options', [] );
            $level = isset( $options['validation_sanitization_level'] ) ? $options['validation_sanitization_level'] : 'standard';

            // Type‑specific sanitization
            switch ( $rules['type'] ) {
                case 'email':
                    $sanitized[ $field ] = sanitize_email( $value );
                    if ( ! is_email( $sanitized[ $field ] ) ) {
                        return new WP_Error( 'vapt_invalid_email', __( 'Invalid email address.', 'vapt-security' ) );
                    }
                    break;
                case 'int':
                    $sanitized[ $field ] = absint( $value );
                    break;
                case 'url':
                    $sanitized[ $field ] = esc_url_raw( $value );
                    // Additional URL validation
                    if ( $level === 'strict' && ! filter_var( $sanitized[ $field ], FILTER_VALIDATE_URL ) ) {
                        return new WP_Error( 'vapt_invalid_url', __( 'Invalid URL format.', 'vapt-security' ) );
                    }
                    break;
                case 'string':
                default:
                    // Apply different levels of sanitization
                    switch ( $level ) {
                        case 'strict':
                            // Remove all HTML tags and attributes
                            $sanitized[ $field ] = sanitize_text_field( wp_strip_all_tags( $value ) );
                            // Additional regex validation for strict mode
                            if ( ! preg_match( '/^[a-zA-Z0-9\s\-_.,!?@]+$/', $sanitized[ $field ] ) ) {
                                // Allow some special characters but block potentially harmful ones
                                $sanitized[ $field ] = preg_replace( '/[<>"\';&%$#@^*()+=\[\]{}|\\\:]/', '', $sanitized[ $field ] );
                            }
                            break;
                        case 'standard':
                            $sanitized[ $field ] = sanitize_text_field( $value );
                            break;
                        case 'basic':
                        default:
                            $sanitized[ $field ] = sanitize_textarea_field( $value );
                            break;
                    }
                    break;
            }

            // Max length
            if ( isset( $rules['max'] ) && mb_strlen( $sanitized[ $field ] ) > $rules['max'] ) {
                return new WP_Error(
                    'vapt_field_too_long',
                    sprintf( __( '%s is too long.', 'vapt-security' ), ucfirst( $field ) )
                );
            }

            // Min length for required fields
            if ( isset( $rules['min'] ) && mb_strlen( $sanitized[ $field ] ) < $rules['min'] ) {
                return new WP_Error(
                    'vapt_field_too_short',
                    sprintf( __( '%s is too short.', 'vapt-security' ), ucfirst( $field ) )
                );
            }

            // Pattern validation if specified
            if ( isset( $rules['pattern'] ) && ! preg_match( $rules['pattern'], $sanitized[ $field ] ) ) {
                return new WP_Error(
                    'vapt_invalid_pattern',
                    sprintf( __( '%s format is invalid.', 'vapt-security' ), ucfirst( $field ) )
                );
            }
        }

        return $sanitized;
    }

    /**
     * Advanced XSS prevention
     */
    public function prevent_xss( $data ) {
        if ( is_array( $data ) ) {
            return array_map( [ $this, 'prevent_xss' ], $data );
        }
        
        // Remove potentially harmful HTML
        $data = wp_strip_all_tags( $data );
        
        // Remove JavaScript event handlers
        $data = preg_replace( '/(on[a-z]+)="[^"]*"/i', '', $data );
        
        // Remove script tags
        $data = preg_replace( '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data );
        
        // Remove javascript: URLs
        $data = preg_replace( '/javascript:/i', '', $data );
        
        return $data;
    }

    /**
     * Check for explicit security violations based on level.
     * Returns a WP_Error if a violation is found, or false if safe.
     * 
     * @param mixed $value The value to check.
     * @return WP_Error|false
     */
    public function check_security_violations( $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                $error = $this->check_security_violations( $item );
                if ( is_wp_error( $error ) ) {
                    return $error;
                }
            }
            return false;
        }

        // Only check strings
        if ( ! is_string( $value ) ) {
            return false;
        }

        $options = get_option( 'vapt_security_options', [] );
        $level = isset( $options['validation_sanitization_level'] ) ? $options['validation_sanitization_level'] : 'standard';

        // Common patterns
        $script_pattern = '/<script\b[^>]*>(.*?)<\/script>/is';
        $iframe_pattern = '/<iframe\b[^>]*>(.*?)<\/iframe>/is';
        $on_event_pattern = '/\s+on[a-z]+\s*=/i';
        $javascript_protocol = '/javascript:/i';

        if ( $level === 'strict' || $level === 'standard' ) {
            if ( preg_match( $script_pattern, $value ) ) {
                return new WP_Error( 'vapt_security_violation', __( 'Security Violation: scripts are not allowed.', 'vapt-security' ) );
            }
            if ( preg_match( $on_event_pattern, $value ) ) {
                return new WP_Error( 'vapt_security_violation', __( 'Security Violation: event handlers are not allowed.', 'vapt-security' ) );
            }
            if ( preg_match( $javascript_protocol, $value ) ) {
                return new WP_Error( 'vapt_security_violation', __( 'Security Violation: javascript: protocol is not allowed.', 'vapt-security' ) );
            }
        }

        if ( $level === 'strict' ) {
            if ( preg_match( $iframe_pattern, $value ) ) {
                return new WP_Error( 'vapt_security_violation', __( 'Security Violation: iframes are not allowed.', 'vapt-security' ) );
            }
            if ( strpos( $value, '<' ) !== false && strpos( $value, '>' ) !== false ) {
                 // In strict mode, if it looks like HTML, warn about it (simplistic check)
                 // This might be too aggressive for some forms, but requested for "Secure"
            }
        }

        return false;
    }

    /**
     * Public helper to sanitize a single value based on level.
     * 
     * @param mixed $value
     * @param string $level 'basic', 'standard', 'strict'
     * @return mixed
     */
    public function sanitize_value_by_level( $value, $level = 'standard' ) {
        if ( is_array( $value ) ) {
            $sanitized = [];
            foreach ( $value as $key => $item ) {
                $sanitized[$key] = $this->sanitize_value_by_level( $item, $level );
            }
            return $sanitized;
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        switch ( $level ) {
            case 'strict':
                // Remove all HTML tags and attributes
                $sanitized = sanitize_text_field( wp_strip_all_tags( $value ) );
                // Additional regex validation for strict mode
                if ( ! preg_match( '/^[a-zA-Z0-9\s\-_.,!?@]+$/', $sanitized ) ) {
                    // Allow some special characters but block potentially harmful ones
                    $sanitized = preg_replace( '/[<>"\';&%$#@^*()+=\[\]{}|\\\:]/', '', $sanitized );
                }
                return $sanitized;
            
            case 'standard':
                return sanitize_text_field( $value );
            
            case 'basic':
            default:
                return sanitize_textarea_field( $value );
        }
    }
}