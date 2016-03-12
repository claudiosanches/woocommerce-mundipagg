<?php
/**
 * Admin options screen.
 *
 * @package WooCommerce_MundiPagg/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h3><?php echo esc_html( $this->method_title ); ?></h3>

<?php
	if ( 'yes' == $this->get_option( 'enabled' ) ) {
		if ( ! $this->api->using_supported_currency() && ! class_exists( 'woocommerce_wpml' ) ) {
			include 'html-notice-currency-not-supported.php';
		}

		if ( '' === $this->merchant_key ) {
			include_once 'html-notice-merchant-key-missing.php';
		}

		if ( 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && 'transaction' === $this->environment ) {
			include_once 'html-notice-ssl-required.php';
		}
	}
?>

<?php echo wpautop( $this->method_description ); ?>

<?php include 'html-admin-help-message.php'; ?>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
