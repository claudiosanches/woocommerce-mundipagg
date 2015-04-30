<?php
/**
 * Admin View: Notice - Missing dependencies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SoapClient' ) ) :
	?>

	<div class="error">
		<p><strong><?php _e( 'WooCommerce MundiPagg', 'woocommerce-mundipagg' ); ?></strong>: <?php _e( 'Needs to have installed on your server the SOAP module to works!', 'woocommerce-mundipagg' ); ?></p>
	</div>

<?php
endif;

if ( ! class_exists( 'WC_Payment_Gateway' ) ) :
	if ( current_user_can( 'install_plugins' ) ) {
		$woocommerce_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
	} else {
		$woocommerce_url = 'http://wordpress.org/plugins/woocommerce/';
	}

	?>

	<div class="error">
		<p><strong><?php _e( 'WooCommerce MundiPagg', 'woocommerce-mundipagg' ); ?></strong>: <?php printf( __( 'Depends on the last version of %s to work!', 'woocommerce-mundipagg' ), '<a href="' . esc_url( $woocommerce_url ) . '">' . __( 'WooCommerce', 'woocommerce-mundipagg' ) . '</a>' ); ?></p>
	</div>

<?php
endif;

if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) :
	if ( current_user_can( 'install_plugins' ) ) {
		$wc_ecfb_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil' ), 'install-plugin_woocommerce-extra-checkout-fields-for-brazil' );
	} else {
		$wc_ecfb_url = 'http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/';
	}

	?>

	<div class="error">
		<p><strong><?php _e( 'WooCommerce MundiPagg', 'woocommerce-mundipagg' ); ?></strong>: <?php printf( __( 'Depends on the last version of %s to work!', 'woocommerce-mundipagg' ), '<a href="' . esc_url( $wc_ecfb_url ) . '">' . __( 'WooCommerce Extra Checkout Fields for Brazil', 'woocommerce-mundipagg' ) . '</a>' ); ?></p>
	</div>

<?php
endif;
