<?php
/**
 * Plain email instructions.
 *
 * @author  Claudio_Sanches
 * @package WooCommerce_MundiPagg/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

_e( 'Payment', 'woocommerce-mundipagg' );

echo "\n\n";

_e( 'Please use the link below to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-mundipagg' );

echo "\n";

echo esc_url( $ticket_url );

echo "\n";

_e( 'After we receive the ticket payment confirmation, your order will be processed.', 'woocommerce-mundipagg' );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
