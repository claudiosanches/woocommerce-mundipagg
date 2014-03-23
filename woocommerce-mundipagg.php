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
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	const VERSION = '1.0.0';

	/**
	 * Integration id.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	protected static $gateway_id = 'mundipagg';

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @var   string
	 */
	protected static $plugin_slug = 'woocommerce-mundipagg';

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var   object
	 */
	protected static $instance = null;

	public function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Initialize the plugin actions.
		$this->init();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  1.0.0
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
	 * Return the plugin slug.
	 *
	 * @since  1.0.0
	 *
	 * @return string Plugin slug variable.
	 */
	public static function get_plugin_slug() {
		return self::$plugin_slug;
	}

	/**
	 * Return the gateway id/slug.
	 *
	 * @since  1.0.0
	 *
	 * @return string Gateway id/slug variable.
	 */
	public static function get_gateway_id() {
		return self::$gateway_id;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$domain = self::$plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Initialize the plugin public actions.
	 *
	 * @since  1.0.0
	 *
	 * @return  void
	 */
	protected function init() {
		if ( class_exists( 'SoapClient' ) ) {
			// Checks with WooCommerce is installed.
			if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
				// Include the WC_MundiPagg_Gateway class.
				include_once 'includes/class-wc-mundipagg-gateway.php';

				add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
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
	 * @version 1.0.0
	 *
	 * @param   array $methods WooCommerce payment methods.
	 *
	 * @return  array          Payment methods with MundiPagg.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_MundiPagg_Gateway';

		return $methods;
	}

	/**
	 * Load scripts.
	 *
	 * @return void
	 */
	public function load_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( 'mundipagg-payment', plugins_url( 'assets/js/frontend/payment.js', __FILE__ ), array( 'jquery', 'jquery-payment' ), self::VERSION, true );
			wp_enqueue_style( 'mundipagg-payment', plugins_url( 'assets/css/frontend/payment.css', __FILE__ ), array(), self::VERSION, 'all' );
		}
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @version 1.0.0
	 *
	 * @return  string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf(
			__( '%s depends on the last version of the %s and the %s to work!', self::$plugin_slug ),
			'<strong>' . __( 'WooCommerce MundiPagg Gateway', self::$plugin_slug ) . '</strong>',
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', self::$plugin_slug ) . '</a>',
			'<a href="http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">' . __( 'WooCommerce Extra Checkout Fields for Brazil', self::$plugin_slug ) . '</a>'
		) . '</p></div>';
	}

	/**
	 * Soap fallback notice.
	 *
	 * @version 1.0.0
	 *
	 * @return  string
	 */
	public function soap_missing_notice() {
		echo '<div class="error"><p>' . sprintf(
			__( '%s needs to have installed on your server the SOAP module to works!', self::$plugin_slug ),
			'<strong>' . __( 'WooCommerce MundiPagg Gateway', self::$plugin_slug ) . '</strong>'
		) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_MundiPagg', 'get_instance' ), 0 );

endif;
