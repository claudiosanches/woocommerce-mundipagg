<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce MundiPagg Credit Card Gateway class.
 *
 * @class   WC_Mundipagg_Credit_Card_Gateway
 * @extends WC_Payment_Gateway
 * @version 2.0.0
 * @author  Claudio Sanches
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
		// add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		// add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
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
				'description' => __( 'Select the environment type (staging or production).', 'woocommerce-mundipagg' ),
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
				'description' => sprintf( __( 'Log MundiPagg events, such as API requests, you can check this log in %s.', 'woocommerce-mundipagg' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'iugu-woocommerce' ) . '</a>' )
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
}
