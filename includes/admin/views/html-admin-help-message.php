<?php
/**
 * Admin help message.
 *
 * @package WooCommerce_MundiPagg/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( apply_filters( 'woocommerce_mundipagg_help_message', 'yes' === get_option( 'wc_mundipagg_new_installation' ) ) ) : ?>
	<div class="updated inline woocommerce-message">
		<p><?php echo esc_html( sprintf( __( 'Help us keep the %s plugin free making a donation or rate %s on WordPress.org. Thank you in advance!', 'woocommerce-mundipagg' ), __( 'WooCommerce MundiPagg', 'woocommerce-mundipagg' ), '&#9733;&#9733;&#9733;&#9733;&#9733;' ) ); ?></p>
		<p><a href="http://claudiosmweb.com/doacoes/" target="_blank" class="button button-primary"><?php esc_html_e( 'Make a donation', 'woocommerce-mundipagg' ); ?></a> <a href="https://wordpress.org/support/view/plugin-reviews/woocommerce-mundipagg?filter=5#postform" target="_blank" class="button button-secondary"><?php esc_html_e( 'Make a review', 'woocommerce-mundipagg' ); ?></a></p>
	</div>
<?php endif;
