<?php
/**
 * Plugin Name: WooCommerce MundiPagg
 * Plugin URI: http://claudiosmweb.com
 * Description: MundiPagg gateway for WooCommerce
 * Author: Claudio Sanches
 * Author URI: http://claudiosmweb.com/
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-mundipagg
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_MundiPagg' ) ) :

/**
 * WooCommerce MundiPagg main class.
 */
class WC_MundiPagg {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin actions.
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Initialize the plugin actions.
		$this->init();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path() {
		return plugin_dir_path( __FILE__ ) . 'templates/';
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-mundipagg' );

		load_textdomain( 'woocommerce-mundipagg', trailingslashit( WP_LANG_DIR ) . 'woocommerce-mundipagg/woocommerce-mundipagg-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-mundipagg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Initialize the plugin public actions.
	 */
	protected function init() {
		if ( class_exists( 'SoapClient' ) ) {
			// Checks with WooCommerce is installed.
			if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
				// Include the WC_MundiPagg_Gateway class.
				include_once 'includes/class-wc-mundipagg-gateway.php';

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'soap_missing_notice' ) );
		}
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with MundiPagg.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_MundiPagg_Gateway';

		return $methods;
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf(
			__( '%s depends on the last version of the %s and the %s to work!', 'woocommerce-mundipagg' ),
			'<strong>' . __( 'WooCommerce MundiPagg Gateway', 'woocommerce-mundipagg' ) . '</strong>',
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'woocommerce-mundipagg' ) . '</a>',
			'<a href="http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">' . __( 'WooCommerce Extra Checkout Fields for Brazil', 'woocommerce-mundipagg' ) . '</a>'
		) . '</p></div>';
	}

	/**
	 * Soap fallback notice.
	 *
	 * @return string
	 */
	public function soap_missing_notice() {
		echo '<div class="error"><p>' . sprintf(
			__( '%s needs to have installed on your server the SOAP module to works!', 'woocommerce-mundipagg' ),
			'<strong>' . __( 'WooCommerce MundiPagg Gateway', 'woocommerce-mundipagg' ) . '</strong>'
		) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_MundiPagg', 'get_instance' ) );

endif;
