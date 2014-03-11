<?php
/**
 * WC MundiPagg Gateway Class.
 *
 * Built the MundiPagg method.
 *
 * @since 2.2.1
 */
class WC_MundiPagg_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id                 = WC_MundiPagg::get_gateway_id();
		$this->plugin_slug        = WC_MundiPagg::get_plugin_slug();
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'MundiPagg', $this->plugin_slug );
		$this->method_description = '';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Actions.
		// add_action( 'woocommerce_api_wc_mundipagg_gateway', array( $this, 'check_ipn_response' ) );
		// add_action( 'valid_mundipagg_ipn_request', array( $this, 'successful_request' ) );
		// add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $this->woocommerce_instance()->logger();
			}
		}

		// Display admin notices.
		$this->admin_notices();
	}

	/**
	 * Backwards compatibility with version prior to 2.1.
	 *
	 * @return object Returns the main instance of WooCommerce class.
	 */
	protected function woocommerce_instance() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 *
	 * @return void
	 */
	protected function admin_notices() {
		if ( is_admin() ) {
			// Checks if email is not empty.
			if ( empty( $this->email ) ) {
				add_action( 'admin_notices', array( $this, 'mail_missing_message' ) );
			}

			// Checks if token is not empty.
			if ( empty( $this->token ) ) {
				add_action( 'admin_notices', array( $this, 'token_missing_message' ) );
			}

			// Checks that the currency is supported
			if ( ! $this->using_supported_currency() ) {
				add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
			}
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return in_array( get_woocommerce_currency(), array( 'BRL' ) );
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = ( 'yes' == $this->settings['enabled'] ) &&
					! empty( $this->email ) &&
					! empty( $this->token ) &&
					$this->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

	}

	/**
	 * Generate the payment xml.
	 *
	 * @param object  $order Order data.
	 *
	 * @return string        Payment xml.
	 */
	protected function generate_payment_xml( $order ) {

	}

	/**
	 * Generate Payment Token.
	 *
	 * @param object $order Order data.
	 *
	 * @return bool
	 */
	public function generate_payment_token( $order ) {

	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int    $order_id Order ID.
	 *
	 * @return array           Redirect.
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		$token = $this->generate_payment_token( $order );

		if ( $token ) {
			// Remove cart.
			$this->woocommerce_instance()->cart->empty_cart();

			return array(
				'result'   => 'success',
				'redirect' => esc_url_raw( $this->payment_url . $token )
			);
		}
	}

	/**
	 * Process the IPN.
	 *
	 * @return bool
	 */
	public function process_ipn_request( $data ) {

	}

	/**
	 * Check API Response.
	 *
	 * @return void
	 */
	public function check_ipn_response() {
		@ob_clean();

		$ipn = $this->process_ipn_request( $_POST );

		if ( $ipn ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'valid_mundipagg_ipn_request', $ipn );
		} else {
			wp_die( __( 'MundiPagg Request Failure', $this->plugin_slug ) );
		}
	}

	/**
	 * Successful Payment!
	 *
	 * @param array $posted MundiPagg post data.
	 *
	 * @return void
	 */
	public function successful_request( $posted ) {

	}

	/**
	 * Gets the admin url.
	 *
	 * @return string
	 */
	protected function admin_url() {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_mundipagg_gateway' );
		}

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_MundiPagg_Gateway' );
	}

	/**
	 * Adds error message when not configured the email.
	 *
	 * @return string Error Mensage.
	 */
	public function mail_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'MundiPagg Disabled', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your email address. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when not configured the token.
	 *
	 * @return string Error Mensage.
	 */
	public function token_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'MundiPagg Disabled', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your token. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when an unsupported currency is used.
	 *
	 * @return string
	 */
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>' . __( 'MundiPagg Disabled', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', $this->plugin_slug ), get_woocommerce_currency() ) . '</p></div>';
	}

}
