<?php
/**
 * Input validator.
 *
 * @package VAPT_Security_Qoder
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
                    sprintf( __( 'The field %s is required.', 'vapt-security-qoder' ), $field )
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
                        return new WP_Error( 'vapt_invalid_email', __( 'Invalid email address.', 'vapt-security-qoder' ) );
                    }
                    break;
                case 'int':
                    $sanitized[ $field ] = absint( $value );
                    break;
                case 'url':
                    $sanitized[ $field ] = esc_url_raw( $value );
                    // Additional URL validation
                    if ( $level === 'strict' && ! filter_var( $sanitized[ $field ], FILTER_VALIDATE_URL ) ) {
                        return new WP_Error( 'vapt_invalid_url', __( 'Invalid URL format.', 'vapt-security-qoder' ) );
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
                    sprintf( __( '%s is too long.', 'vapt-security-qoder' ), ucfirst( $field ) )
                );
            }

            // Min length for required fields
            if ( isset( $rules['min'] ) && mb_strlen( $sanitized[ $field ] ) < $rules['min'] ) {
                return new WP_Error(
                    'vapt_field_too_short',
                    sprintf( __( '%s is too short.', 'vapt-security-qoder' ), ucfirst( $field ) )
                );
            }

            // Pattern validation if specified
            if ( isset( $rules['pattern'] ) && ! preg_match( $rules['pattern'], $sanitized[ $field ] ) ) {
                return new WP_Error(
                    'vapt_invalid_pattern',
                    sprintf( __( '%s format is invalid.', 'vapt-security-qoder' ), ucfirst( $field ) )
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
}