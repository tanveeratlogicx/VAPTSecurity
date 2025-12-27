<?php
/**
 * Integrations Manager
 *
 * Handles third-party form plugin integrations for Input Validation.
 *
 * @package VAPT_Security
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VAPT_Integrations_Manager {

    /**
     * Initialize integrations based on settings.
     */
    public function init() {
        $options = get_option( 'vapt_security_options', [] );
        
        // Only proceed if Input Validation feature is globally enabled
        if ( ! defined( 'VAPT_FEATURE_INPUT_VALIDATION' ) || ! VAPT_FEATURE_INPUT_VALIDATION ) {
            return;
        }

        // Contact Form 7
        if ( ! empty( $options['vapt_integration_cf7'] ) ) {
            $this->setup_cf7();
        }

        // Elementor Forms
        if ( ! empty( $options['vapt_integration_elementor'] ) ) {
            $this->setup_elementor();
        }

        // WPForms
        if ( ! empty( $options['vapt_integration_wpforms'] ) ) {
            $this->setup_wpforms();
        }

        // Gravity Forms
        if ( ! empty( $options['vapt_integration_gravity'] ) ) {
            $this->setup_gravity();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Contact Form 7 Integration                                         */
    /* ------------------------------------------------------------------ */

    private function setup_cf7() {
        // Hook into WPCF7 validation
        add_filter( 'wpcf7_validate', [ $this, 'handle_cf7_validation' ], 10, 2 );
    }

    /**
     * Validate CF7 submission.
     * 
     * @param WPCF7_Validation $result
     * @param WPCF7_FormTagsManager $tags
     */
    public function handle_cf7_validation( $result, $tags ) {
        $validator = new VAPT_Input_Validator();
        
        // Iterate through all submitted fields
        foreach ( $_POST as $key => $value ) {
            // Skip WP specific fields that start with _
            if ( strpos( $key, '_' ) === 0 ) {
                continue;
            }

            $error = $validator->check_security_violations( $value );
            
            if ( is_wp_error( $error ) ) {
                $result->invalidate( $key, $error->get_error_message() );
            }
        }
        
        return $result;
    }

    /* ------------------------------------------------------------------ */
    /* Elementor Forms Integration                                        */
    /* ------------------------------------------------------------------ */

    private function setup_elementor() {
        add_action( 'elementor_pro/forms/validation', [ $this, 'handle_elementor_validation' ], 10, 2 );
    }

    /**
     * Validate Elementor Form submission.
     * 
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler
     */
    public function handle_elementor_validation( $record, $handler ) {
        $fields = $record->get( 'fields' );
        $validator = new VAPT_Input_Validator();

        foreach ( $fields as $field_id => $field ) {
            $value = $field['value'] ?? '';
            $error = $validator->check_security_violations( $value );

            if ( is_wp_error( $error ) ) {
                $handler->add_error( $field_id, $error->get_error_message() );
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* WPForms Integration                                                */
    /* ------------------------------------------------------------------ */

    private function setup_wpforms() {
        add_filter( 'wpforms_process_validate', [ $this, 'handle_wpforms_validation' ], 10, 2 );
    }

    /**
     * Validate WPForms submission.
     * 
     * @param array $entry
     * @param array $form_data
     * @return array
     */
    public function handle_wpforms_validation( $entry, $form_data ) {
        $validator = new VAPT_Input_Validator();

        if ( empty( $entry['fields'] ) ) {
            return $entry;
        }

        foreach ( $entry['fields'] as $field_id => $field_value ) {
            $error = $validator->check_security_violations( $field_value );

            if ( is_wp_error( $error ) ) {
                // Add error to specific field
                \wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = $error->get_error_message();
            }
        }
        
        return $entry;
    }

    /* ------------------------------------------------------------------ */
    /* Gravity Forms Integration                                         */
    /* ------------------------------------------------------------------ */

    private function setup_gravity() {
        add_filter( 'gform_validation', [ $this, 'handle_gravity_validation' ] );
    }

    /**
     * Validate Gravity Forms submission.
     * 
     * @param array $validation_result
     * @return array
     */
    public function handle_gravity_validation( $validation_result ) {
        $form = $validation_result['form'];
        $validator = new VAPT_Input_Validator();
        $has_error = false;

        foreach ( $form['fields'] as &$field ) {
            // Get value from POST
            $input_name = 'input_' . $field->id;
            $value = rgpost( $input_name );

            if ( empty( $value ) ) {
                continue;
            }

            $error = $validator->check_security_violations( $value );

            if ( is_wp_error( $error ) ) {
                $field->failed_validation = true;
                $field->validation_message = $error->get_error_message();
                $has_error = true;
            }
        }

        $validation_result['form'] = $form;
        $validation_result['is_valid'] = ! $has_error && $validation_result['is_valid'];

        return $validation_result;
    }
}
