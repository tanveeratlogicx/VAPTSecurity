/**
 * Handles AJAX submission for the VAPT Security form.
 *
 * @package VAPT_Security
 */
jQuery( function( $ ) {
    $( '#vapt-security-form' ).on( 'submit', function( e ) {
        e.preventDefault();

        var $form   = $( this ),
            data    = $form.serializeArray(),
            success = function( resp ) {
                $( '#vapt-security-response' ).html( '<p style="color: green;">' + resp.data.message + '</p>' );
                $form[0].reset();
            },
            error   = function( resp ) {
                var msg = resp.responseJSON && resp.responseJSON.message
                          ? resp.responseJSON.message
                          : '<?php esc_js( __( 'An unexpected error occurred.', 'vapt-security' ) ); ?>';
                $( '#vapt-security-response' ).html( '<p style="color: red;">' + msg + '</p>' );
            };

        data.push( { name: 'action', value: 'vapt_form_submit' } );
        data.push( { name: 'nonce', value: VAPT_SECURITY.nonce } );

        $.post( VAPT_SECURITY.ajax_url, data, function( resp ) {
            if ( resp.success ) {
                success( resp );
            } else {
                error( resp );
            }
        } );
    } );
} );
