(function( $ ) {
	'use strict';

	$( function() {
		/**
		 * Hide and display the credit card form.
		 *
		 * @param  {string} method
		 * @return {void}
		 */
		function wcMundiPaggMeFormSwitch( method ) {
			var creditCardForm = $( '#mundipagg-cc-form' ),
				ticketMessage  = $( '#mundipagg-ticket-info' );

			if ( 'credit-card' === method ) {
				creditCardForm.slideDown( 200 );
				ticketMessage.slideUp( 200 );
			} else {
				creditCardForm.slideUp( 200 );
				ticketMessage.slideDown( 200 );
			}
		}

		/**
		 * Controls the credit card display.
		 *
		 * @return {void}
		 */
		function wcMundiPaggMeFormDisplay() {
			var method = $( '#mundipagg-payment-methods input[name="mundipagg_payment_type"]' ).val();

			wcMundiPaggMeFormSwitch( method );
		}

		wcMundiPaggMeFormDisplay();

		/**
		 * Display or hide the credit card for when change the payment method.
		 */
		$( 'body' ).on( 'click', 'li.payment_method_mundipagg input[name="mundipagg_payment_type"]', function() {
			wcMundiPaggMeFormSwitch( $( this ).val() );
		});

		/**
		 * Display or hide the credit card for when change the payment gateway.
		 */
		$( 'body' ).on( 'updated_checkout', function() {
			wcMundiPaggMeFormDisplay();
		});
	});

}( jQuery ));
