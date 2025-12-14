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
                    break;
                case 'string':
                default:
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    break;
            }

            // Max length
            if ( isset( $rules['max'] ) && mb_strlen( $sanitized[ $field ] ) > $rules['max'] ) {
                return new WP_Error(
                    'vapt_field_too_long',
                    sprintf( __( '%s is too long.', 'vapt-security' ), ucfirst( $field ) )
                );
            }
        }

        return $sanitized;
    }
}
