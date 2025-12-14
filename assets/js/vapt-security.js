/**
 * Handles AJAX form submission.
 *
 * @package VAPT_Security
 */

jQuery( function( $ ) {
    $( '#vapt-security-form' ).on( 'submit', function( e ) {
        e.preventDefault();

        var $form = $( this ),
            data  = $form.serializeArray();

        // Add action and nonce
        data.push( { name: 'action', value: 'vapt_form_submit' } );
        data.push( { name: 'nonce', value: VAPT_SECURITY.nonce } );

        $.post( VAPT_SECURITY.ajax_url, data, function( resp ) {
            var $msg = $( '#vapt-security-response' );
            if ( resp.success ) {
                $msg.html( '<p style="color: green;">' + resp.data.message + '</p>' ).show();
                $form[0].reset();
            } else {
                $msg.html( '<p style="color: red;">' + resp.data.message + '</p>' ).show();
            }
        } );
    } );
} );
