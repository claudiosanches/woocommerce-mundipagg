<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Credit Card Gateway class.
 *
 * @package WooCommerce_MundiPagg/Gateway
 */
class WC_Mundipagg_Credit_Card_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'mundipagg-credit-card';
		$this->icon               = apply_filters( 'woocommerce_mundipagg_credit_card_icon', '' );
		$this->method_title       = __( 'MundiPagg - Credit Card', 'woocommerce-mundipagg' );
		$this->method_description = __( 'Accept payments by credit card using the MundiPagg.', 'woocommerce-mundipagg' );
		$this->has_fields         = true;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Options.
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->environment          = $this->get_option( 'environment' );
		$this->merchant_key         = $this->get_option( 'merchant_key' );
		$this->auth_capture         = $this->get_option( 'auth_capture' );
		$this->capture_delay        = $this->get_option( 'capture_delay' );
		$this->installments         = $this->get_option( 'installments' );
		$this->interest_rate        = $this->get_option( 'interest_rate' );
		$this->interest             = $this->get_option( 'interest' );
		$this->smallest_installment = $this->get_option( 'smallest_installment' );
		$this->invoice_prefix       = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->debug                = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		$this->api = new WC_Mundipagg_API( $this, 'credit-card' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}
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
		$available = parent::is_available() && ! empty( $this->merchant_key ) && $this->api->using_supported_currency() && $this->api->check_environment();

		return $available;
	}

	/**
	 * Admin page.
	 */
	public function admin_options() {
		include 'admin/views/html-admin-page.php';
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-mundipagg' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable MundiPagg Credit Card', 'woocommerce-mundipagg' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => __( 'Credit Card', 'woocommerce-mundipagg' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-mundipagg' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-mundipagg' ),
				'default'     => __( 'Pay with credit card.', 'woocommerce-mundipagg' )
			),
			'integration' => array(
				'title'       => __( 'Integration Settings', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'environment' => array(
				'title'       => __( 'Environment', 'woocommerce-mundipagg' ),
				'type'        => 'select',
				'description' => __( 'Select the environment type (staging or transaction).', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'class'       => 'wc-enhanced-select',
				'default'     => 'staging',
				'options'     => array(
					'staging'     => __( 'Staging', 'woocommerce-mundipagg' ),
					'transaction' => __( 'Transaction', 'woocommerce-mundipagg' )
				)
			),
			'merchant_key' => array(
				'title'       => __( 'Merchant Key', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Please enter your MundiPagg Merchant Key. This is needed in order to take payment.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => array(
					'required' => 'required'
				)
			),
			'payment' => array(
				'title'       => __( 'Payment Options', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'auth_capture' => array(
				'title'       => __( 'Authorization and Capture methods', 'woocommerce-mundipagg' ),
				'type'        => 'select',
				'description' => __( 'Choose your authorization and capture methods.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'class'       => 'wc-enhanced-select',
				'default'     => 'AuthAndCapture',
				'options'     => array(
					'AuthOnly'                => __( 'Authorize only', 'woocommerce-mundipagg' ),
					'AuthAndCapture'          => __( 'Authorize and capture', 'woocommerce-mundipagg' ),
					'AuthAndCaptureWithDelay' => __( 'Authorize and capture with delay', 'woocommerce-mundipagg' ),
				)
			),
			'capture_delay' => array(
				'title'       => __( 'Capture Delay', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Enter with the capture delay in minutes. There can be more than 5 days (7200 minutes).', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => '60'
			),
			'installments' => array(
				'title'       => __( 'Installment Within', 'woocommerce-mundipagg' ),
				'type'        => 'select',
				'description' => __( 'Maximum number of installments for orders in your store.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'class'       => 'wc-enhanced-select',
				'default'     => '1',
				'options'     => array(
					'1'  => '1x',
					'2'  => '2x',
					'3'  => '3x',
					'4'  => '4x',
					'5'  => '5x',
					'6'  => '6x',
					'7'  => '7x',
					'8'  => '8x',
					'9'  => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x'
				)
			),
			'interest_rate' => array(
				'title'       => __( 'Interest Rate (%)', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Percentage of interest that will be charged to the customer in the installment where there is interest rate to be charged.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => '2'
			),
			'interest' => array(
				'title'       => __( 'Charge Interest Since', 'woocommerce-mundipagg' ),
				'type'        => 'select',
				'description' => __( 'Indicate from which installment should be charged interest.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'class'       => 'wc-enhanced-select',
				'default'     => '0',
				'options'     => array(
					'0'  => __( 'Do not charge interest', 'woocommerce-mundipagg' ),
					'1'  => '1x',
					'2'  => '2x',
					'3'  => '3x',
					'4'  => '4x',
					'5'  => '5x',
					'6'  => '6x',
					'7'  => '7x',
					'8'  => '8x',
					'9'  => '9x',
					'10' => '10x',
					'11' => '11x',
					'12' => '12x'
				)
			),
			'smallest_installment' => array(
				'title'       => __( 'Smallest Installment', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Smallest value of each installment, cannot be less than 5.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => '5'
			),
			'behavior' => array(
				'title'       => __( 'Integration Behavior', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your MundiPagg account for multiple stores ensure this prefix is unqiue as MundiPagg will not allow orders with the same invoice number.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => 'WC-'
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-mundipagg' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-mundipagg' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log MundiPagg events, such as API requests, you can check this log in %s.', 'woocommerce-mundipagg' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'woocommerce-mundipagg' ) . '</a>' )
			)
		);
	}

	/**
	 * Payment fields.
	 */
	public function payment_fields() {
		wp_enqueue_script( 'wc-credit-card-form' );

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$order_total = $this->get_order_total();

		woocommerce_get_template(
			'credit-card/payment-form.php',
			array(
				'installments' => $this->api->get_installments_html( $order_total )
			),
			'woocommerce/mundipagg/',
			WC_MundiPagg::get_templates_path()
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int   $order_id Order ID.
	 *
	 * @return array           Redirect.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Test the card fields is valid.
		$valid = $this->api->validate_card_fields( $_POST );

		// Test for installments.
		if ( $valid ) {
			$valid = $this->api->validate_installments( $_POST, (float) $order->get_total() );
		}

		// Generate the payment data.
		if ( $valid ) {
			$response = $this->api->generate_payment( $order );
		}

		// Payment response handler.
		if ( ! empty( $response ) ) {
			$response = $response[0]->CreateOrderResult;

			// Processes the errors.
			if ( 1 != $response->Success ) {
				if ( isset( $response->ErrorReport->ErrorItemCollection->ErrorItem ) ) {
					if ( is_array( $response->ErrorReport->ErrorItemCollection->ErrorItem ) ) {
						foreach ( $response->ErrorReport->ErrorItemCollection->ErrorItem as $error ) {
							wc_add_notice( '<strong>' . esc_html( $this->title ) . '</strong>: ' . wc_clean( $error->Description ), 'error' );
						}
					} else {
						wc_add_notice( '<strong>' . esc_html( $this->title ) . '</strong>: ' . wc_clean( $response->ErrorReport->ErrorItemCollection->ErrorItem->Description ), 'error' );
					}
				} else if ( isset( $response->CreditCardTransactionResultCollection->CreditCardTransactionResult->CreditCardTransactionStatusEnum ) && 'NotAuthorized' == $response->CreditCardTransactionResultCollection->CreditCardTransactionResult->CreditCardTransactionStatusEnum ) {
					wc_add_notice( '<strong>' . esc_html( $this->title ) . '</strong>: ' . str_replace( array( 'Redecard|', 'Simulator|' ), '', wc_clean( $response->CreditCardTransactionResultCollection->CreditCardTransactionResult->AcquirerMessage ) ), 'error' );
				} else {
					wc_add_notice( '<strong>' . esc_html( $this->title ) . '</strong>: ' . esc_html( __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-mundipagg' ) ), 'error' );
				}
			} else {
				if ( isset( $response->OrderKey ) ) {
					add_post_meta( $order->id, '_transaction_id', (string) sanitize_text_field( $response->OrderKey ), true );
				}

				$updated = WC_Mundipagg_API::update_order_status( (string) $response->OrderReference, (string) $response->OrderStatusEnum, $this->invoice_prefix );

				if ( $updated ) {

					// Remove cart.
					WC()->cart->empty_cart();

					// Go to thankyou page.
					return array(
						'result'   => 'success',
						'redirect' => $order->get_checkout_order_received_url()
					);
				}
			}
		} else if ( $valid ) {
			wc_add_notice( '<strong>' . esc_html( $this->title ) . '</strong>: ' . esc_html( __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-mundipagg' ) ), 'error' );
		}

		// The request failed.
		return array(
			'result'   => 'fail',
			'redirect' => ''
		);
	}

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page( $order_id ) {
		$order        = new WC_Order( $order_id );
		$order_status = $order->get_status();
		$data         = get_post_meta( $order_id, '_mundipagg_credit_card_data', true );

		if ( isset( $data['installments'] ) && 'processing' == $order_status ) {
			woocommerce_get_template(
				'credit-card/payment-instructions.php',
				array(
					'brand'        => $data['brand'],
					'installments' => $data['installments']
				),
				'woocommerce/mundipagg/',
				WC_MundiPagg::get_templates_path()
			);
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  object $order         Order object.
	 * @param  bool   $sent_to_admin Send to admin.
	 * @param  bool   $plain_text    Plain text or HTML.
	 *
	 * @return string                Payment instructions.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || ! in_array( $order->get_status(), array( 'processing', 'on-hold' ) ) || $this->id !== $order->payment_method ) {
			return;
		}

		$data = get_post_meta( $order->id, '_mundipagg_credit_card_data', true );

		if ( isset( $data['installments'] ) ) {
			if ( $plain_text ) {
				woocommerce_get_template(
					'credit-card/emails/plain-instructions.php',
					array(
						'brand'        => $data['brand'],
						'installments' => $data['installments']
					),
					'woocommerce/mundipagg/',
					WC_MundiPagg::get_templates_path()
				);
			} else {
				woocommerce_get_template(
					'credit-card/emails/html-instructions.php',
					array(
						'brand'        => $data['brand'],
						'installments' => $data['installments']
					),
					'woocommerce/mundipagg/',
					WC_MundiPagg::get_templates_path()
				);
			}
		}
	}

	/**
	 * Admin scripts.
	 *
	 * @param string $hook Page slug.
	 */
	public function admin_scripts( $hook ) {
		if ( 'woocommerce_page_wc-settings' === $hook && ( isset( $_GET['section'] ) && strtolower( get_class( $this ) ) == strtolower( $_GET['section'] ) ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'woocommerce-mundipagg-credit-card-admin', plugins_url( 'assets/js/admin-credit-card' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WC_Mundipagg::VERSION, true );
		}
	}
}
