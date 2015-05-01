<?php
/**
 * Plugin Name: WooCommerce MundiPagg
 * Plugin URI: https://claudiosmweb.com
 * Description: MundiPagg gateway for WooCommerce
 * Author: Claudio Sanches
 * Author URI: https://claudiosmweb.com/
 * Version: 2.0.1
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
	const VERSION = '2.0.1';

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
		add_action( 'init', array( __CLASS__, 'add_return_endpoint' ), 0 );

		if ( class_exists( 'SoapClient' ) && class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			$this->includes();

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			add_action( 'parse_request', array( $this, 'handle_return_requests' ), 0 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
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

	/**
	 * Created the return endpoint.
	 */
	public static function add_return_endpoint() {
		add_rewrite_endpoint( 'wc-mundipagg-return', EP_ROOT );
	}

	/**
	 * Plugin activate method.
	 */
	public static function activate() {
		self::add_return_endpoint();

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivate method.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Handle with return requests.
	 */
	public function handle_return_requests() {
		global $wp;

		// wc-mundipagg-return endpoint requests
		if ( isset( $wp->query_vars['wc-mundipagg-return'] ) || isset( $_GET['wc-mundipagg-return'] ) ) {
			ob_start();

			if ( isset( $_POST['xmlStatusNotification'] ) ) {
				try {
					$data = html_entity_decode( urldecode( $_POST['xmlStatusNotification'] ) );
					$xml  = @new SimpleXMLElement( $data, LIBXML_NOCDATA );

					// Banking ticket.
					if ( isset( $xml->BoletoTransaction ) ) {
						WC_Mundipagg_API::notification_handler( $xml, 'banking-ticket' );
					}

					// Credit card.
					if ( isset( $xml->CreditCardTransaction ) ) {
						WC_Mundipagg_API::notification_handler( $xml, 'credit-card' );
					}
				} catch ( Exception $e ) {
					wp_die( __( 'Invalid return data', 'woocommerce-mundipagg' ), __( 'Invalid return data', 'woocommerce-mundipagg' ), array( 'response' => 400 ) );
				}
			}

			ob_end_clean();
			die( '1' );
		}
	}

	/**
	 * Action links.
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_mundipagg_banking_ticket_gateway' ) ) . '">' . __( 'Banking Ticket Settings', 'woocommerce-mundipagg' ) . '</a>';

		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_mundipagg_credit_card_gateway' ) ) . '">' . __( 'Credit Card Settings', 'woocommerce-mundipagg' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}
}

/**
 * Plugin activation and deactivation methods.
 */
register_activation_hook( __FILE__, array( 'WC_Mundipagg', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WC_Mundipagg', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WC_Mundipagg', 'get_instance' ) );

endif;
