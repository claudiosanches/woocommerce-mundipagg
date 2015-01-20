<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>


<fieldset id="<?php echo esc_attr( $this->id ); ?>-cc-form">
	<label><input type="radio" name="<?php echo esc_attr( $this->id ); ?>_payment_type" value="credit-card" style="display: inline; vertical-align: middle;" checked="checked" /> <?php _e( 'Credit Card', 'woocommerce-mundipagg' ); ?></label>
	<label style="margin-left: 20px;"><input type="radio" name="<?php echo esc_attr( $this->id ); ?>_payment_type" value="ticket" style="display: inline; vertical-align: middle;" /> <?php _e( 'Billet', 'woocommerce-mundipagg' ); ?></label>
</fieldset>

<fieldset id="<?php echo esc_attr( $this->id ); ?>-cc-form">

	<p class="form-row form-row-wide">
		<label for="<?php esc_attr( $this->id ); ?>-holder-name"><?php _e( 'Holder Name', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="<?php esc_attr( $this->id ); ?>-holder-name" class="input-text wc-credit-card-form-holder-name" type="text" autocomplete="off" name="<?php echo esc_attr( $this->id ); ?>_holder_name" style="font-size: 1.5em; padding: 8px;" />
	</p>

	<p class="form-row form-row-wide">
		<label for="<?php esc_attr( $this->id ); ?>-card-number"><?php _e( 'Card Number', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="<?php esc_attr( $this->id ); ?>-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="<?php echo esc_attr( $this->id ); ?>_card_number" style="font-size: 1.5em; padding: 8px;" />
	</p>

	<p class="form-row form-row-first">
		<label for="<?php esc_attr( $this->id ); ?>-card-expiry"><?php _e( 'Expiry (MM/YY)', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="<?php esc_attr( $this->id ); ?>-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="<?php echo esc_attr( $this->id ); ?>_card_expiry" style="font-size: 1.5em; padding: 8px;" />
	</p>

	<p class="form-row form-row-last">
		<label for="<?php esc_attr( $this->id ); ?>-card-cvc"><?php _e( 'Card Code', 'woocommerce-mundipagg' ); ?> <span class="required">*</span></label>
		<input id="<?php esc_attr( $this->id ); ?>-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="<?php echo esc_attr( $this->id ); ?>_card_cvc" style="font-size: 1.5em; padding: 8px;" />
	</p>

	<p class="form-row form-row-wide">
		<label for="<?php esc_attr( $this->id ); ?>-installments"><?php _e( 'Installments', 'woocommerce-mundipagg' ); ?></label>
		<select id="<?php esc_attr( $this->id ); ?>-installments" class="input-text wc-credit-card-form-installments" name="<?php echo esc_attr( $this->id ); ?>_installments" style="font-size: 1.5em; padding: 8px; width: 100%;">
			<?php
				// Get the cart total.
				$cart_total = WC()->cart->total;

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

<div id="<?php echo esc_attr( $this->id ); ?>-ticket-info">
	<p><?php _e( 'The order will be confirmed only after the ticket payment approval.', 'woocommerce-mundipagg' ); ?></p>
</div>
