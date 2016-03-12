<?php
/**
 * Admin View: Notice SSL Required.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="error inline">
	<p><strong><?php _e( 'MundiPagg Disabled', 'woocommerce-mundipagg' ); ?></strong>: <?php printf( __( 'A SSL Certificate is required for the transaction environment. Please verify if a certificate is installed on your server and enable the %s option.', 'woocommerce-mundipagg' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section' ) ) . '">' . __( 'Force secure checkout', 'woocommerce-mundipagg' ) . '</a>' ); ?></p>
</div>
