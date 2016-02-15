<?php
/**
 * Admin View: Notice Merchant Key Missing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="error inline">
	<p><strong><?php _e( 'MundiPagg Disabled', 'woocommerce-mundipagg' ); ?></strong>: <?php printf( __( 'You should inform your Merchant Key. %s', 'woocommerce-mundipagg' ), '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_mundipagg_gateway">' . __( 'Click here to configure!', 'woocommerce-mundipagg' ) . '</a>' ); ?></p>
</div>
