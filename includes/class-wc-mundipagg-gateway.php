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

		// API.
		$this->production_url = 'https://transaction.mundipaggone.com/MundiPaggService.svc?wsdl';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->merchant_key   = $this->get_option( 'merchant_key' );
		$this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->staging        = $this->get_option( 'staging' );
		$this->debug          = $this->get_option( 'debug' );

		// Actions.
		// add_action( 'woocommerce_api_wc_mundipagg_gateway', array( $this, 'check_ipn_response' ) );
		// add_action( 'valid_mundipagg_ipn_request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

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
			// Checks if merchant_key is not empty.
			if ( empty( $this->merchant_key ) ) {
				add_action( 'admin_notices', array( $this, 'merchant_key_missing_message' ) );
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
		$supported = apply_filters( 'woocommerce_mundipagg_supported_currencies', array(
			'BRL',
			'EUR',
			'USD',
			'ARS',
			'BOB',
			'CLP',
			'COP',
			'UYU',
			'MXN',
			'PYG'
		) );

		return in_array( get_woocommerce_currency(), $supported );
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
					! empty( $this->merchant_key ) &&
					$this->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', $this->plugin_slug ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable MundiPagg gateway', $this->plugin_slug ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => __( 'MundiPagg', $this->plugin_slug )
			),
			'description' => array(
				'title'       => __( 'Description', $this->plugin_slug ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', $this->plugin_slug ),
				'default'     => __( 'Pay with credit cart or billet via MundiPagg', $this->plugin_slug )
			),
			'merchant_key' => array(
				'title'       => __( 'MundiPagg Merchant Key', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Please enter your MundiPagg Merchant Key address. This is needed in order to take payment.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', $this->plugin_slug ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your MundiPagg account for multiple stores ensure this prefix is unqiue as MundiPagg will not allow orders with the same invoice number.', $this->plugin_slug ),
				'desc_tip'    => true,
				'default'     => 'WC-'
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', $this->plugin_slug ),
				'type'        => 'title',
				'description' => ''
			),
			'staging' => array(
				'title'       => __( 'Staging Environment', $this->plugin_slug ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Staging Environment', $this->plugin_slug ),
				'default'     => 'yes',
				'description' => __( 'Disable this option when the plugin was used for the production environment.', $this->plugin_slug )
			),
			'debug' => array(
				'title'       => __( 'Debug Log', $this->plugin_slug ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', $this->plugin_slug ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log MundiPagg events, such as API requests, inside %s', $this->plugin_slug ), '<code>woocommerce/logs/' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' )
			)
		);
	}

	/**
	 * Money to cents.
	 *
	 * @param  float $value Amount money format.
	 *
	 * @return int          Amount in cents/int.
	 */
	protected function fix_money( $value ) {
		$values = explode( '.', $value );

		if ( 2 == count( $values ) ) {
			if ( 1 == strlen( $values[1] ) ) {
				return $values[0] . $values[1] . '0';
			} else {
				return $values[0] . $values[1];
			}
		} else {
			return $value . '00';
		}
	}

	/**
	 * Generate the payment data.
	 *
	 * @param object  $order Order data.
	 *
	 * @return string        Payment data.
	 */
	protected function generate_payment_data( $order ) {
		$total   = $this->fix_money( (float) $order->order_total );
		$request = array(
			'createOrderRequest' => array(
				'MerchantKey'                     => $this->merchant_key,
				'OrderReference'                  => $this->invoice_prefix . $order->id,
				'AmountInCents'                   => $total,
				'AmountInCentsToConsiderPaid'     => $total,
				'EmailUpdateToBuyerEnum'          => 'No',
				'CurrencyIsoEnum'                 => get_woocommerce_currency(),
				'RequestKey'                      => $this->invoice_prefix . $order->id,
				// 'Retries'                      => 0, // Default in one plataform.
				'Buyer'                           => null,
				'CreditCardTransactionCollection' => null,
				'BoletoTransactionCollection'     => null,
				'ShoppingCartCollection'          => null
			)
		);

		$request = apply_filters( 'woocommerce_mundipagg_payment_data', $request, $order );

		return $request;
	}

	/**
	 * Generate Payment Token.
	 *
	 * @param object $order Order data.
	 *
	 * @return bool
	 */
	public function generate_payment_token( $order ) {
		return $this->generate_payment_data( $order );
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

		$response = $this->generate_payment_token( $order );

		error_log( print_r( $response, true ) );

		return array( 'result' => 'fail', 'redirect' => '' );

		// if ( $response ) {
		// 	// Remove cart.
		// 	$this->woocommerce_instance()->cart->empty_cart();

		// 	return array(
		// 		'result'   => 'success',
		// 		'redirect' => esc_url_raw( $this->payment_url . $token )
		// 	);
		// }
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
	 * Adds error message when not configured the Merchant Key.
	 *
	 * @return string Error Mensage.
	 */
	public function merchant_key_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'MundiPagg Disabled', $this->plugin_slug ) . '</strong>: ' . sprintf( __( 'You should inform your Merchant Key address. %s', $this->plugin_slug ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', $this->plugin_slug ) . '</a>' ) . '</p></div>';
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
