<?php
/**
 * Admin View: Notice - Currency not supported.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="error">
	<p><strong><?php _e( 'MundiPagg Disabled', 'woocommerce-mundipagg' ); ?></strong>: <?php printf( __( 'Currency <code>%s</code> is not supported. Works with %s.', 'woocommerce-mundipagg' ), get_woocommerce_currency(), '<code>' . implode( ', ', $this->get_supported_currencies() ) . '</code>' ); ?></p>
</div>
