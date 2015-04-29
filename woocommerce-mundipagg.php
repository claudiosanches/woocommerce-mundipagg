<?php
/**
 * Plugin Name: WooCommerce MundiPagg
 * Plugin URI: https://claudiosmweb.com
 * Description: MundiPagg gateway for WooCommerce
 * Author: Claudio Sanches
 * Author URI: https://claudiosmweb.com/
 * Version: 2.0.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-mundipagg
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Mundipagg' ) ) :

/**
 * WooCommerce MundiPagg main class.
 */
class WC_Mundipagg {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.0.0';

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

		if ( class_exists( 'SoapClient' ) && class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			$this->includes();

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'missing_dependencies_notice' ) );
		}
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
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with MundiPagg.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Mundipagg_Banking_Ticket_Gateway';
		$methods[] = 'WC_Mundipagg_Credit_Card_Gateway';

		return $methods;
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once 'includes/class-wc-mundipagg-api.php';
		include_once 'includes/class-wc-mundipagg-banking-ticket-gateway.php';
		include_once 'includes/class-wc-mundipagg-credit-card-gateway.php';
	}

	/**
	 * Missing dependencies notice.
	 *
	 * @return string
	 */
	public function missing_dependencies_notice() {
		include_once 'includes/views/html-notice-missing-dependencies.php';
	}
}

add_action( 'plugins_loaded', array( 'WC_Mundipagg', 'get_instance' ) );

endif;
