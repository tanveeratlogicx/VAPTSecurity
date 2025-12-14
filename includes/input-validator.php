<?php
/**
 * Strict server‑side validation helper.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Input_Validator {

    /**
     * Validate input data against a schema array.
     *
     * @param array $data   Raw POST data
     * @param array $schema Array of fields => rules
     *
     * @return array|WP_Error  Sanitized data on success, WP_Error on failure
     */
    public function validate( array $data, array $schema ) {
        $sanitized = [];
        foreach ( $schema as $field => $rules ) {
            $value = $data[ $field ] ?? null;

            // Required check
            if ( $rules['required'] && ( ! isset( $value ) || $value === '' ) ) {
                return new WP_Error( 'vapt_missing_field', sprintf( __( 'The field %s is required.', 'vapt-security' ), $field ) );
            }

            // Skip optional empty values
            if ( ! $rules['required'] && ( ! isset( $value ) || $value === '' ) ) {
                $sanitized[ $field ] = null;
                continue;
            }

            // Type validation & sanitization
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
                    break;
                case 'string':
                default:
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    break;
            }

            // Max length check
            if ( isset( $rules['max'] ) && mb_strlen( $sanitized[ $field ] ) > $rules['max'] ) {
                return new WP_Error( 'vapt_field_too_long', sprintf( __( '%s is too long.', 'vapt-security' ), ucfirst( $field ) ) );
            }
        }
        return $sanitized;
    }
}
