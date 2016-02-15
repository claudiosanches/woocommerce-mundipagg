<?php
/**
 * Credit Card - HTML email instructions.
 *
 * @package WooCommerce_MundiPagg/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php _e( 'Payment', 'woocommerce-mundipagg' ); ?></h2>

<p class="order_details"><?php printf( __( 'Payment successfully made with %s in %sx.', 'woocommerce-mundipagg' ), '<strong>' . $brand . '</strong>', '<strong>' . $installments . '</strong>' ); ?></p>
