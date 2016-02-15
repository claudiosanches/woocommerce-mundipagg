<?php
/**
 * Banking Ticket - HTML email instructions.
 *
 * @package WooCommerce_MundiPagg/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<h2><?php _e( 'Payment', 'woocommerce-mundipagg' ); ?></h2>

<p class="order_details"><?php _e( 'Please use the link below to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-mundipagg' ); ?><br /><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-mundipagg' ); ?></a><br /><?php _e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woocommerce-mundipagg' ); ?></p>
