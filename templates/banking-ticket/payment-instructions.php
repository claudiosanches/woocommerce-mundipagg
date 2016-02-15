<?php
/**
 * Banking Ticket - Payment instructions.
 *
 * @package WooCommerce_MundiPagg/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank" style="display: block !important; visibility: visible !important;"><?php _e( 'Pay the Banking Ticket', 'woocommerce-mundipagg' ); ?></a><?php _e( 'Please click in the following button to view your Banking Ticket.', 'woocommerce-mundipagg' ); ?><br /><?php _e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-mundipagg' ); ?><br /><?php _e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woocommerce-mundipagg' ); ?></span>
</div>
