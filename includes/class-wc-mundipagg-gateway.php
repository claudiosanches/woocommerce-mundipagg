<?php
/**
 * WC MundiPagg Gateway Class.
 *
 * Built the MundiPagg method.
 */
class WC_MundiPagg_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'mundipagg';
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = __( 'MundiPagg', 'woocommerce-mundipagg' );
		$this->method_description = '';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title               = $this->get_option( 'title' );
		$this->description         = $this->get_option( 'description' );
		$this->merchant_key        = $this->get_option( 'merchant_key' );
		$this->invoice_prefix      = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->payment_methods     = $this->get_option( 'payment_methods' );
		$this->ticket_our_number   = $this->get_option( 'ticket_our_number' );
		$this->ticket_bank_number  = $this->get_option( 'ticket_bank_number' );
		$this->ticket_instructions = $this->get_option( 'ticket_instructions' );
		$this->ticket_days         = $this->get_option( 'ticket_days', '5' );
		$this->staging             = $this->get_option( 'staging' );
		$this->debug               = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Actions.
		add_action( 'woocommerce_api_wc_mundipagg_gateway', array( $this, 'check_ipn_response' ) );
		add_action( 'woocommerce_mundipagg_order_status_change', array( $this, 'update_order_status' ), 10, 2 );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 */
	public function admin_notices() {
		if ( 'yes' == $this->get_option( 'enabled' ) ) {
			// Checks if merchant_key is not empty.
			if ( empty( $this->merchant_key ) ) {
				include_once 'views/html-notice-merchant-key-missing.php';
			}

			// Checks that the currency is supported
			if ( ! $this->using_supported_currency() && ! class_exists( 'woocommerce_wpml' ) ) {
				include_once 'views/html-notice-currency-not-supported.php';
			}
		}
	}

	/**
	 * Get the supported currencies.
	 *
	 * @return array
	 */
	public function get_supported_currencies() {
		return apply_filters( 'woocommerce_mundipagg_supported_currencies', array(
			'ARS',
			'BOB',
			'BRL',
			'CLP',
			'COP',
			'MXN',
			'PYG',
			'UYU',
			'EUR',
			'USD'
		) );
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return in_array( get_woocommerce_currency(), $this->get_supported_currencies() );
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
		$available = parent::is_available() && ! empty( $this->merchant_key ) && $this->using_supported_currency();

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
				'label'   => __( 'Enable MundiPagg gateway', 'woocommerce-mundipagg' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => __( 'MundiPagg', 'woocommerce-mundipagg' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-mundipagg' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-mundipagg' ),
				'default'     => __( 'Pay with credit cart or ticket via MundiPagg', 'woocommerce-mundipagg' )
			),
			'merchant_key' => array(
				'title'       => __( 'MundiPagg Merchant Key', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Please enter your MundiPagg Merchant Key address. This is needed in order to take payment.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your MundiPagg account for multiple stores ensure this prefix is unqiue as MundiPagg will not allow orders with the same invoice number.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => 'WC-'
			),
			'payment_methods' => array(
				'title'       => __( 'Payment methods', 'woocommerce-mundipagg' ),
				'type'        => 'select',
				'default'     => 'all',
				'options'     => array(
					'all'         => __( 'Credit card and ticket', 'woocommerce-mundipagg' ),
					'credit_card' => __( 'Credit card only', 'woocommerce-mundipagg' ),
					'ticket'      => __( 'Ticket only', 'woocommerce-mundipagg' )
				)
			),
			'ticket_data' => array(
				'title'       => __( 'Ticket data', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'ticket_our_number' => array(
				'title'       => __( 'Our Number', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'default'     => ''
			),
			'ticket_bank_number' => array(
				'title'       => __( 'Bank Number', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'default'     => ''
			),
			'ticket_instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-mundipagg' ),
				'type'        => 'textarea',
				'default'     => ''
			),
			'ticket_days' => array(
				'title'       => __( 'Deadline to pay the Ticket', 'woocommerce-mundipagg' ),
				'type'        => 'text',
				'description' => __( 'Days will be added to the current date to the expiry date.', 'woocommerce-mundipagg' ),
				'desc_tip'    => true,
				'default'     => '5'
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-mundipagg' ),
				'type'        => 'title',
				'description' => ''
			),
			'staging' => array(
				'title'       => __( 'Staging Environment', 'woocommerce-mundipagg' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Staging Environment', 'woocommerce-mundipagg' ),
				'default'     => 'yes',
				'description' => __( 'Disable this option when the plugin was used for the production environment.', 'woocommerce-mundipagg' )
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-mundipagg' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-mundipagg' ),
				'default'     => 'no',
				'description' => __( 'Log MundiPagg events, such as API requests.', 'woocommerce-mundipagg' )
			)
		);
	}

	/**
	 * Get the API URL.
	 *
	 * @return string
	 */
	protected function get_api_url() {
		$environment = ( 'yes' == $this->staging ) ? 'staging' : 'transaction';

		return 'https://' . $environment . '.mundipaggone.com/mundipaggservice.svc?wsdl';
	}

	/**
	 * Extract cents of money valey.
	 *
	 * @param mixed $money
	 *
	 * @return int
	 */
	protected function extract_cents( $money ) {
		$cents = number_format( $money, 2, '', '' );
		$cents = intval( $cents );

		return $cents;
	}

	/**
	 * Format the phone number.
	 *
	 * @param  string $value
	 *
	 * @return string
	 */
	protected function phone_format( $value ) {
		if ( ! empty( $value ) ) {
			return preg_replace( '/\D/', '', $value );
		}

		return $value;
	}

	/**
	 * Get the country name.
	 *
	 * @param  string $code
	 *
	 * @return string
	 */
	protected function get_country( $code ) {
		$countries = array(
			'BR' => 'Brazil',
			'US' => 'USA',
			'AR' => 'Argentina',
			'BO' => 'Bolivia',
			'CL' => 'Chile',
			'CO' => 'Colombia',
			'UY' => 'Uruguay',
			'MX' => 'Mexico',
			'PY' => 'Paraguay'
		);

		if ( ! isset( $countries[ $code ] ) ) {
			return $countries['BR'];
		}

		return $countries[ $code ];
	}

	/**
	 * Get the gender.
	 *
	 * @param  string $value
	 *
	 * @return string
	 */
	protected function get_gender( $value ) {
		$gender = substr( strtoupper( $value ), 0, 1 );

		return $gender;
	}

	/**
	 * Get the credit cards.
	 *
	 * @return array
	 */
	protected function get_credit_cards() {
		$cards = apply_filters( 'woocommerce_mundipagg_availables_credit_cards', array(
			'Visa'        => __( 'Visa', 'woocommerce-mundipagg' ),
			'Mastercard'  => __( 'MasterCard', 'woocommerce-mundipagg' ),
			'Hipercard'   => __( 'Hipercard', 'woocommerce-mundipagg' ),
			'Amex'        => __( 'Amex', 'woocommerce-mundipagg' ),
			'Diners'      => __( 'Diners', 'woocommerce-mundipagg' ),
			'Elo'         => __( 'Elo', 'woocommerce-mundipagg' )
		) );

		return $cards;
	}

	/**
	 * Get the credit card expiry date.
	 *
	 * @param  string $value
	 *
	 * @return array
	 */
	protected function get_credit_card_expiry_date( $value ) {
		$month = '';
		$year  = '';

		$value = explode( '/', $value );
		$month = isset( $value[0] ) ? trim( $value[0] ) : '';
		$year  = isset( $value[1] ) ? trim( $value[1] ) : '';

		return array(
			'month' => $month,
			'year'  => $year,
		);
	}

	/**
	 * Get only numbers.
	 *
	 * @param  string $string
	 *
	 * @return string
	 */
	protected function get_only_numbers( $string ) {
		return preg_replace( '([^0-9])', '', $string );
	}

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ( is_checkout() && $this->is_available() ) {
			wp_enqueue_script( 'wc-mundipagg-payment', plugins_url( 'assets/js/frontend/payment.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WC_MundiPagg::VERSION, true );
			wp_enqueue_style( 'mundipagg-payment', plugins_url( 'assets/css/frontend/payment.css', plugin_dir_path( __FILE__ ) ), array(), WC_MundiPagg::VERSION, 'all' );
		}
	}

	/**
	 * Payment fields.
	 *
	 * @return string
	 */
	public function payment_fields() {
		global $woocommerce;

		wp_enqueue_script( 'wc-credit-card-form' );

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		woocommerce_get_template(
			'checkout-form.php', array(
				'cart_total'      => $this->get_order_total(),
				'payment_methods' => $this->payment_methods
			),
			'woocommerce/mundipagg/',
			WC_MundiPagg::get_templates_path()
		);
	}

	/**
	 * Generate the payment data.
	 *
	 * @param object  $order Order data.
	 *
	 * @return string        Payment data.
	 */
	protected function generate_payment_data( $order ) {
		// Order total in cents.
		$total = $this->extract_cents( $order->order_total );

		// Order request.
		$request = array(
			'createOrderRequest' => array(
				'MerchantKey'                     => $this->merchant_key,
				'OrderReference'                  => $this->invoice_prefix . $order->id,
				'AmountInCents'                   => $total,
				'AmountInCentsToConsiderPaid'     => $total,
				'EmailUpdateToBuyerEnum'          => 'No',
				'CurrencyIsoEnum'                 => get_woocommerce_currency(),
				// 'RequestKey'                      => 1234,
				// 'Retries'                      => 0, // Default in one plataform.
				'Buyer'                           => null,
				'CreditCardTransactionCollection' => null,
				'BoletoTransactionCollection'     => null,
				'ShoppingCartCollection'          => null
			)
		);

		// Buyer.
		$request['createOrderRequest']['Buyer'] = array(
			// 'BuyerKey'                  => '00000000-0000-0000-0000-000000000000',
			'BuyerReference'            => $order->customer_user,
			// 'CreateDateInMerchant'      => '',
			// 'LastBuyerUpdateInMerchant' => '',
			'Email'                     => $order->billing_email,
			// 'FacebookId'                => '',
			'GenderEnum'                => isset( $order->billing_sex ) ? $this->get_gender( $order->billing_sex ) : 'M', // M or F
			'IpAddress'                 => $order->customer_ip_address,
			'Name'                      => $order->billing_first_name . ' ' . $order->billing_last_name,
			'PersonTypeEnum'            => 'Person', // Person or Company
			'TaxDocumentNumber'         => '',
			'TaxDocumentTypeEnum'       => 'CPF', // CPF or CNPJ
			// 'TwitterId'                 => '',
			'HomePhone'                 => $this->phone_format( $order->billing_phone ),
			'WorkPhone'                 => '',
			'MobilePhone'               => isset( $order->billing_cellphone ) ? $this->phone_format( $order->billing_cellphone ) : '',
			'BuyerAddressCollection'    => array(
				array(
					'City'            => $order->billing_city,
					'Complement'      => $order->billing_address_2,
					'CountryEnum'     => $this->get_country( $order->billing_country ),
					'District'        => isset( $order->billing_neighborhood ) ? $order->billing_neighborhood : '',
					'Number'          => isset( $order->billing_number ) ? $order->billing_number : '',
					'State'           => $order->billing_state,
					'Street'          => $order->billing_address_1,
					'ZipCode'         => $order->billing_postcode,
					'AddressTypeEnum' => 'Billing', // Billing, Shipping, Comercial or Residential.
				)
			)
		);

		// Buyer person type, document number and document type enum.
		if ( isset( $order->billing_persontype ) ) {
			if ( 1 == $order->billing_persontype ) {
				$request['createOrderRequest']['Buyer']['PersonTypeEnum']      = 'Person';
				$request['createOrderRequest']['Buyer']['TaxDocumentNumber']   = str_replace( array( '-', '.' ), '', $order->billing_cpf );
				$request['createOrderRequest']['Buyer']['TaxDocumentTypeEnum'] = 'CPF';
			}

			if ( 2 == $order->billing_persontype ) {
				$request['createOrderRequest']['Buyer']['PersonTypeEnum']      = 'Company';
				$request['createOrderRequest']['Buyer']['TaxDocumentNumber']   = str_replace( array( '-', '.' ), '', $order->billing_cnpj );
				$request['createOrderRequest']['Buyer']['TaxDocumentTypeEnum'] = 'CNPJ';
			}
		}

		// Buyer shipping address.
		if ( isset( $_POST['ship_to_different_address'] ) ) {
			$request['createOrderRequest']['Buyer']['BuyerAddressCollection'][] = array(
				'City'            => $order->shipping_city,
				'Complement'      => $order->shipping_address_2,
				'CountryEnum'     => $this->get_country( $order->shipping_country ),
				'District'        => isset( $order->shipping_neighborhood ) ? $order->shipping_neighborhood : '',
				'Number'          => isset( $order->shipping_number ) ? $order->shipping_number : '',
				'State'           => $order->shipping_state,
				'Street'          => $order->shipping_address_1,
				'ZipCode'         => $order->shipping_postcode,
				'AddressTypeEnum' => 'Shipping',
			);
		}

		// Shop cart.
		if ( sizeof( $order->get_items() ) > 0 ) {
			$cart_items = array();

			foreach ( $order->get_items() as $order_item ) {
				if ( $order_item['qty'] ) {
					// Get product data.
					$product = $order->get_product_from_item( $order_item );

					// Product description.
					if ( ! empty( $product->post->post_excerpt ) ) {
						$item_description = $product->post->post_excerpt;
					} else {
						$item_description = $product->post->post_content;
					}

					// Format the product name.
					$item_name = $order_item['name'];
					$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );

					if ( $meta = $item_meta->display( true, true ) ) {
						$item_name .= ' - ' . $meta;
					}

					// Get the prouct sku/id.
					$_product_sku = $product->get_sku();
					if ( ! empty( $_product_sku ) ) {
						$item_sku = $_product_sku;
					} else if ( $order_item['variation_id'] > 0 ) {
						$item_sku = $order_item['variation_id'];
					} else {
						$item_sku = $order_item['product_id'];
					}

					// Get the total price.
					$item_total = $order->get_item_total( $order_item, false );

					$item = array();
					$item['ItemReference']    = $item_sku;
					$item['Description']      = wp_trim_words( sanitize_text_field( $item_description ), 20, '...' );
					$item['Name']             = substr( sanitize_text_field( $item_name ), 0, 95 );
					$item['Quantity']         = $order_item['qty'];
					$item['TotalCostInCents'] = $this->extract_cents( $item_total * $order_item['qty'] );
					$item['UnitCostInCents']  = $this->extract_cents( $item_total );

					$cart_items[] = $item;
				}
			}

			$request['createOrderRequest']['ShoppingCartCollection'] = array(
				'ShoppingCart' => array(
					array(
						'FreightCostInCents'         => $this->extract_cents( $order->get_total_shipping() ),
						'ShoppingCartItemCollection' => array(
							'ShoppingCartItem'       => $cart_items
						)
					)
				)
			);
		}

		// Payment type
		if ( isset( $_POST['mundipagg_payment_type'] ) ) {
			switch ( $_POST['mundipagg_payment_type'] ) {
				case 'credit-card' :
					$credit_cards = array();
					$expiry       = $this->get_credit_card_expiry_date( sanitize_text_field( $_POST['mundipagg_card_expiry'] ) );
					$credit_card  = array(
						'AmountInCents'           => $total,
						'CreditCardNumber'        => sanitize_text_field( $this->get_only_numbers( $_POST['mundipagg_card_number'] ) ),
						'InstallmentCount'        => intval( $_POST['mundipagg_installments'] ),
						'HolderName'              => sanitize_text_field( $_POST['mundipagg_holder_name'] ),
						'SecurityCode'            => sanitize_text_field( $_POST['mundipagg_card_cvc'] ),
						'ExpMonth'                => $expiry['month'],
						'ExpYear'                 => $expiry['year'],
						'CreditCardBrandEnum'     => 'Visa',
						'PaymentMethodCode'       => ( 'yes' == $this->staging ) ? 1 : null,
						// 'PaymentMethodCode'       => null,
						'CreditCardOperationEnum' => 'AuthAndCapture',
					);

					$credit_cards[] = $credit_card;

					$request['createOrderRequest']['CreditCardTransactionCollection'] = array(
						'CreditCardTransaction' => $credit_cards
					);
					break;

				case 'ticket' :
					$tickets = array();
					$ticket  = array(
						'AmountInCents'                   => $total,
						'Instructions'                    => $this->ticket_instructions,
						'NossoNumero'                     => $this->ticket_our_number,
						'DaysToAddInBoletoExpirationDate' => $this->ticket_days,
						'TransactionReference'            => sprintf( __( 'Payment for the order %s', 'woocommerce-mundipagg' ), $order->get_order_number() ),
						'BankNumber'                      => $this->ticket_bank_number,
					);

					$tickets[] = $ticket;

					$request['createOrderRequest']['BoletoTransactionCollection'] = array(
						'BoletoTransaction' => $tickets
					);
					break;

				default :
					break;
			}
		}

		$request = apply_filters( 'woocommerce_mundipagg_payment_data', $request, $order );

		return $request;
	}

	/**
	 * Generate Payment Token.
	 *
	 * @param object $order Order data.
	 *
	 * @return array
	 */
	public function generate_payment_token( $order ) {
		$data     = $this->generate_payment_data( $order );
		$response = array();

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Requesting payment for order ' . $order->get_order_number() . ' with the following data: ' . print_r( $data, true ) );
		}

		$soap_opt = array(
			'encoding'   => 'UTF-8',
			'trace'      => true,
			'exceptions' => true,
			'cache_wsdl' => false,
		);

		try {
			$soap          = new SoapClient( $this->get_api_url(), $soap_opt );
			$soap_response = $soap->CreateOrder( $data );
			$response[]    = $soap_response;

			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'MundiPagg response for the order ' . $order->get_order_number() . ': ' . print_r( $soap_response, true ) );
			}
		} catch ( Exception $e ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while generate the payment for order ' . $order->get_order_number() . ', MundiPagg response: ' . print_r( $e->getMessage(), true ) );
			}
		}

		return $response;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int    $order_id Order ID.
	 *
	 * @return array           Redirect.
	 */
	public function process_payment( $order_id ) {
		$order    = wc_get_order( $order_id );
		$response = $this->generate_payment_token( $order );

		if ( ! empty( $response ) ) {
			$response = $response[0]->CreateOrderResult;

			// Processes the errors.
			if ( 1 != $response->Success ) {
				if ( isset( $response->ErrorReport->ErrorItemCollection->ErrorItem ) ) {
					if ( is_array( $response->ErrorReport->ErrorItemCollection->ErrorItem ) ) {
						foreach ( $response->ErrorReport->ErrorItemCollection->ErrorItem as $error ) {
							wc_add_notice( '<strong>' . __( 'MundiPagg', 'woocommerce-mundipagg' ) . '</strong>: ' . esc_attr( $error->Description ), 'error' );
						}
					} else {
						wc_add_notice( '<strong>' . __( 'MundiPagg', 'woocommerce-mundipagg' ) . '</strong>: ' . esc_attr( $response->ErrorReport->ErrorItemCollection->ErrorItem->Description ), 'error' );
					}
				} else {
					wc_add_notice( '<strong>' . __( 'MundiPagg', 'woocommerce-mundipagg' ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-mundipagg' ), 'error' );
				}
			} else {
				if ( isset( $response->OrderKey ) ) {
					add_post_meta( $order->id, '_transaction_id', (string) sanitize_text_field( $response->OrderKey ), true );
				}

				// Save ticket URL.
				if ( isset( $response->BoletoTransactionResultCollection->BoletoTransactionResult->BoletoUrl ) ) {
					$ticket_url = sanitize_text_field( $response->BoletoTransactionResultCollection->BoletoTransactionResult->BoletoUrl );

					update_post_meta( $order->id, '_mundipagg_ticket_url', (string) $ticket_url );
				}

				$updated = $this->update_order_status( (string) $response->OrderReference, (string) $response->OrderStatusEnum );

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
			wc_add_notice( '<strong>' . __( 'MundiPagg', 'woocommerce-mundipagg' ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-mundipagg' ), 'error' );
		}

		// The request failed.
		return array(
			'result'   => 'fail',
			'redirect' => ''
		);
	}

	/**
	 * Process the IPN.
	 *
	 * @return array
	 */
	public function process_ipn_request( $data ) {
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'IPN request: ' . print_r( $data, true ) );
		}

		try {
			$xml = @new SimpleXMLElement( $data, LIBXML_NOCDATA );

			if ( ! isset( $xml->OrderStatus ) ) {
				throw new Exception( 'Missing OrderStatus param' );
			}

			if ( ! isset( $xml->OrderReference ) ) {
				throw new Exception( 'Missing OrderReference param' );
			}

			if ( ! isset( $xml->MerchantKey ) ) {
				throw new Exception( 'Missing MerchantKey param' );
			}

			$merchant_key = (string) $xml->MerchantKey;
			if ( $this->merchant_key != $merchant_key ) {
				throw new Exception( 'Invalid MerchantKey returned' );
			}

			return array(
				'reference' => (string) $xml->OrderReference,
				'status'    => (string) $xml->OrderStatus
			);
		} catch ( Exception $e ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'IPN error: ' . print_r( $e->getMessage(), true ) );
			}

			return array();
		}
	}

	/**
	 * Check API Response.
	 */
	public function check_ipn_response() {
		@ob_clean();

		$ipn = $this->process_ipn_request( $_POST );

		if ( $ipn ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'woocommerce_mundipagg_order_status_change', $ipn['reference'], $ipn['status'] );
		} else {
			wp_die( __( 'MundiPagg Request Failure', 'woocommerce-mundipagg' ) );
		}
	}

	/**
	 * Update order status!
	 *
	 * @param  string $reference.
	 * @param  string $status.
	 *
	 * @return bool
	 */
	public function update_order_status( $reference, $status ) {
		$valid = false;

		$order_id = (int) str_replace( $this->invoice_prefix, '', $reference );
		$order    = wc_get_order( $order_id );

		// Checks whether the invoice number matches the order.
		// If true processes the payment.
		if ( $order->id === $order_id ) {
			$order_status = strtolower( sanitize_text_field( $status ) );

			switch ( $order_status ) {
				case 'opened' :
					$order->update_status( 'on-hold', __( 'MundiPagg: This order has transactions that have not yet been fully processed.', 'woocommerce-mundipagg' ) );
					$valid = true;

					break;
				case 'captured' :
				case 'paid' :
				case 'overpaid' :
					$order->add_order_note( __( 'MundiPagg: Transaction approved.', 'woocommerce-mundipagg' ) );

					if ( 'overpaid' == $order_status ) {
						$order->add_order_note( __( 'MundiPagg: This order was paid with a higher value than expected.', 'woocommerce-mundipagg' ) );
					}

					$order->payment_complete();

					$valid = true;

					break;
				case 'canceled' :
					$order->update_status( 'cancelled', __( 'MundiPagg: All transactions were canceled.', 'woocommerce-mundipagg' ) );
					$valid = true;

					break;
				case 'partialpaid' :
				case 'underpaid' :
					$order->update_status( 'on-hold', __( 'MundiPagg: Only a few transactions have been paid to date.', 'woocommerce-mundipagg' ) );
					$valid = true;

					break;

				default :
					break;
			}
		}

		return $valid;
	}

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page( $order_id ) {
		$ticket_url = get_post_meta( $order_id, '_mundipagg_ticket_url', true );

		if ( $ticket_url ) {
			woocommerce_get_template(
				'payment-instructions.php',
				array(
					'ticket_url' => $ticket_url
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
	public function email_instructions( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( $sent_to_admin || 'on-hold' !== $order->status || $this->id !== $order->payment_method ) {
			return;
		}

		$ticket_url = get_post_meta( $order->id, '_mundipagg_ticket_url', true );

		if ( $ticket_url ) {
			if ( $plain_text ) {
				woocommerce_get_template(
					'emails/plain-instructions.php',
					array(
						'ticket_url' => $ticket_url
					),
					'woocommerce/mundipagg/',
					WC_MundiPagg::get_templates_path()
				);
			} else {
				woocommerce_get_template(
					'emails/html-instructions.php',
					array(
						'ticket_url' => $ticket_url
					),
					'woocommerce/mundipagg/',
					WC_MundiPagg::get_templates_path()
				);
			}
		}
	}
}
