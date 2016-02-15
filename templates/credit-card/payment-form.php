<?php
/**
 * Credit Card - Checkout form.
 *
 * @package WooCommerce_MundiPagg/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<fieldset id="mundipagg-credit-payment-form" class="mundipagg-payment-form">
	<p class="form-row form-row-first">
		<label for="mundipagg-card-number"><?php _e( 'Card Number', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-number" name="mundipagg_card_number" class="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row form-row-last">
		<label for="mundipagg-card-holder-name"><?php _e( 'Name Printed on the Card', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-holder-name" name="mundipagg_holder_name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<div class="clear"></div>
	<p class="form-row form-row-first">
		<label for="mundipagg-card-expiry"><?php _e( 'Expiry (MM/YYYY)', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-expiry" name="mundipagg_card_expiry" class="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="<?php _e( 'MM / YYYY', 'woocommerce-mundipagg' ); ?>" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row form-row-last">
		<label for="mundipagg-card-cvc"><?php _e( 'Security Code', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="mundipagg-card-cvc" name="mundipagg_card_cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="<?php _e( 'CVC', 'woocommerce-mundipagg' ); ?>" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<?php if ( ! empty( $installments ) ) : ?>
		<p class="form-row form-row-wide">
			<label for="mundipagg-installments"><?php _e( 'Installments', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
			<?php echo $installments; ?>
		</p>
	<?php endif; ?>
	<div class="clear"></div>
</fieldset>
