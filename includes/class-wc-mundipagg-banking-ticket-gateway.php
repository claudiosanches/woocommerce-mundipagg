<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce MundiPagg Banking Ticket Gateway class.
 *
 * @package WooCommerce_MundiPagg/Gateway
 */
class WC_Mundipagg_Banking_Ticket_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'mundipagg-banking-ticket';
		$this->icon               = apply_filters( 'woocommerce_mundipagg_banking_ticket_icon', '' );
		$this->method_title       = __( 'MundiPagg - Banking Ticket', 'woocommerce-mundipagg' );
		$this->method_description = __( 'Accept payments by banking ticket using the MundiPagg.', 'woocommerce-mundipagg' );
		$this->has_fields         = true;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Options.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->environment    = $this->get_option( 'environment' );
		$this->merchant_key   = $this->get_option( 'merchant_key' );
		$this->our_number     = $this->get_option( 'our_number' );
		$this->bank_number    = $this->get_option( 'bank_number' );
		$this->instructions   = $this->get_option( 'instructions' );
		$this->days_to_pay    = $this->get_option( 'days_to_pay' );
		$this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->debug          = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		$this->api = new WC_Mundipagg_API( $this, 'banking-ticket' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
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
				'label'   => __( 'Enable MundiPagg Banking Ticket', 'woocommerce-mundipagg' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => __( 'Banking Ticket', 'woocommerce-mundipagg' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-mundipagg' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-mundipagg' ),
				'default'     => __( 'Pay with banking ticket.', 'woocommerce-mundipagg' )
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
				'title'       => __( 'Banking Ticket Options', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'our_number' => array(
				'title'       => __( 'Our Number', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'default'     => ''
			),
			'bank_number' => array(
				'title'       => __( 'Bank Number', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'default'     => ''
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-mundipagg' ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'days_to_pay' => array(
				'title'       => __( 'Deadline to pay the Ticket', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Days will be added to the current date to the expiry date.', 'woocommerce-mundipagg' ),
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
		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		woocommerce_get_template(
			'banking-ticket/checkout-instructions.php',
			array(),
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
		$order    = wc_get_order( $order_id );
		$response = $this->api->generate_payment( $order );

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
				} else {
					wc_add_notice( '<strong>' . esc_html( $this->title ) . '</strong>: ' . esc_html( __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-mundipagg' ) ), 'error' );
				}
			} else {
				if ( isset( $response->OrderKey ) ) {
					add_post_meta( $order->id, '_transaction_id', (string) sanitize_text_field( $response->OrderKey ), true );
				}

				// Save ticket URL.
				if ( isset( $response->BoletoTransactionResultCollection->BoletoTransactionResult->BoletoUrl ) ) {
					$ticket_url = (string) sanitize_text_field( $response->BoletoTransactionResultCollection->BoletoTransactionResult->BoletoUrl );

					update_post_meta( $order->id, '_mundipagg_banking_ticket_data', array(
						'url' => $ticket_url
					) );
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
		} else {
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
		$data         = get_post_meta( $order_id, '_mundipagg_banking_ticket_data', true );

		if ( isset( $data['url'] ) && 'on-hold' == $order_status ) {
			woocommerce_get_template(
				'banking-ticket/payment-instructions.php',
				array(
					'url' => $data['url']
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
		if ( $sent_to_admin || 'on-hold' != $order->get_status() || $this->id !== $order->payment_method ) {
			return;
		}

		$data = get_post_meta( $order->id, '_mundipagg_banking_ticket_data', true );

		if ( isset( $data['url'] ) ) {
			if ( $plain_text ) {
				woocommerce_get_template(
					'banking-ticket/emails/plain-instructions.php',
					array(
						'url' => $data['url']
					),
					'woocommerce/mundipagg/',
					WC_MundiPagg::get_templates_path()
				);
			} else {
				woocommerce_get_template(
					'banking-ticket/emails/html-instructions.php',
					array(
						'url' => $data['url']
					),
					'woocommerce/mundipagg/',
					WC_MundiPagg::get_templates_path()
				);
			}
		}
	}
}
