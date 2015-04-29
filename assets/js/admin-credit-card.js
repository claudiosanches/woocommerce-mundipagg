(function( $ ) {
	'use strict';

	$( function() {
		$( '#woocommerce_mundipagg-credit-card_auth_capture' ).on( 'change', function() {
			var fields = $( '#woocommerce_mundipagg-credit-card_capture_delay' ).closest( 'tr' );

			if ( 'AuthAndCaptureWithDelay' === $( this ).val() ) {
				fields.show();
			} else {
				fields.hide();
			}

		}).change();
	});

}( jQuery ));
