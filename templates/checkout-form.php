<?php
/**
 * Checkout form.
 *
 * @author  Claudio_Sanches
 * @package WooCommerce_MundiPagg/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<fieldset id="mundipagg-payment-methods">
	<?php if ( 'all' == $payment_methods ) : ?>
	<label id="mundipagg-credit-cart-type"><input type="radio" name="mundipagg_payment_type" value="credit-card" checked="checked" /> <?php _e( 'Credit Card', 'woocommerce-mundipagg' ); ?></label>
	<label id="mundipagg-ticket-cart-type"><input type="radio" name="mundipagg_payment_type" value="ticket" /> <?php _e( 'Ticket', 'woocommerce-mundipagg' ); ?></label>
	<?php elseif ( 'credit_card' == $payment_methods ) : ?>
		<input type="hidden" name="mundipagg_payment_type" value="credit-card" checked="checked" />
	<?php elseif ( 'ticket' == $payment_methods ) : ?>
		<input type="hidden" name="mundipagg_payment_type" value="ticket" checked="checked" />
	<?php endif; ?>
</fieldset>

<fieldset id="mundipagg-cc-form">

	<p class="form-row form-row-wide">
		<label for="mundipagg-holder-name"><?php _e( 'Holder Name', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-holder-name" class="input-text wc-credit-card-form-holder-name" type="text" autocomplete="off" name="mundipagg_holder_name" />
	</p>

	<p class="form-row form-row-wide">
		<label for="mundipagg-card-number"><?php _e( 'Card Number', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" name="mundipagg_card_number" />
	</p>

	<p class="form-row form-row-first">
		<label for="mundipagg-card-expiry"><?php _e( 'Expiry (MM/YY)', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="mundipagg_card_expiry" />
	</p>

	<p class="form-row form-row-last">
		<label for="mundipagg-card-cvc"><?php _e( 'Card Code', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="mundipagg_card_cvc" />
	</p>

	<p class="form-row form-row-wide">
		<label for="mundipagg-installments"><?php _e( 'Installments', 'woocommerce-mundipagg' ); ?></label>
		<select id="mundipagg-installments" class="input-text wc-credit-card-form-installments" name="mundipagg_installments">
			<?php
				// Create the installments.
				for ( $installment = 1; $installment <= 12; $installment++ ) {
					$installment_value = $cart_total / $installment;

					// Stops if the installment is less than 5.
					if ( $installment_value <= 5 ) {
						break;
					}
				?>
					<option value="<?php echo $installment; ?>"><?php echo sprintf( __( '%dx of %s', 'woocommerce-mundipagg' ), $installment, strip_tags( wc_price( $installment_value ) ) ); ?></option>
			<?php } ?>
		</select>
	</p>

	<div class="clear"></div>
</fieldset>

<div id="mundipagg-ticket-info">
	<p><?php _e( 'The order will be confirmed only after the ticket payment approval.', 'woocommerce-mundipagg' ); ?></p>
</div>
