<?php
/**
 * Payment instructions.
 *
 * @author  Claudio_Sanches
 * @package WooCommerce_MundiPagg/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $ticket_url ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-mundipagg' ); ?></a><?php _e( 'Please click in the following button to view your Banking Ticket.', 'woocommerce-mundipagg' ); ?><br /><?php _e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-mundipagg' ); ?><br /><?php _e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woocommerce-mundipagg' ); ?></span>
</div>
