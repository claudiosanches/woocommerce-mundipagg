<?php
/**
 * Credit Card - Plain email instructions.
 *
 * @author  Claudio_Sanches
 * @package WC_MundiPagg/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_e( 'Payment', 'woocommerce-mundipagg' );

echo "\n\n";

printf( __( 'Payment successfully made with %s in %sx.', 'woocommerce-mundipagg' ), $brand, $installments );

echo "\n\n****************************************************\n\n";
