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
		$this->has_fields         = true;
		$this->method_title       = __( 'MundiPagg', $this->plugin_slug );
		$this->method_description = '';

		// API.
		$this->api_url = 'https://transaction.mundipaggone.com/MundiPaggService.svc?wsdl';

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
		// add_action( 'valid_mundipagg_ipn_request', array( $this, 'update_order_status' ) );
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
	 * Add error message in checkout.
	 *
	 * @param string $message Error message.
	 *
	 * @return string         Displays the error message.
	 */
	protected function add_error( $message ) {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			wc_add_notice( $message, 'error' );
		} else {
			$this->woocommerce_instance()->add_error( $message );
		}
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

	protected function phone_format( $value ) {
		if ( ! empty( $value ) ) {
			return preg_replace( '/\D/', '', $value );
		}

		return $value;
	}

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

	protected function get_gender( $value ) {
		$gender = substr( strtoupper( $value ), 0, 1 );

		return $gender;
	}

	protected function get_credit_cards() {
		$cards = apply_filters( 'woocommerce_mundipagg_availables_credit_cards', array(
			'Visa'        => __( 'Visa', $this->plugin_slug ),
			'Mastercard'  => __( 'MasterCard', $this->plugin_slug ),
			'Hipercard'   => __( 'Hipercard', $this->plugin_slug ),
			'Amex'        => __( 'Amex', $this->plugin_slug ),
			'Diners'      => __( 'Diners', $this->plugin_slug ),
			'Elo'         => __( 'Elo', $this->plugin_slug )
		) );

		return $cards;
	}

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
	 * Payment fields.
	 *
	 * @return string
	 */
	public function payment_fields() {
		wp_enqueue_script( 'wc-credit-card-form' );
		$html = '';
		if ( $description = $this->get_description() ) {
			$html .= wpautop( wptexturize( $description ) );
		}

		$html .= '<input type="hidden" name="' . $this->id . '_payment_type" value="credit-card" />';

		$html .= '<fieldset id="' . $this->id . '-cc-form">';

			// Credit card holder name.
			$html .= '<p class="form-row form-row-wide">';
				$html .= '<label for="' . esc_attr( $this->id ) . '-holder-name">' . __( 'Holder Name', $this->plugin_slug ) . ' <span class="required">*</span></label>';
				$html .= '<input id="' . esc_attr( $this->id ) . '-holder-name" class="input-text wc-credit-card-form-holder-name" type="text" autocomplete="off" name="' . $this->id . '_holder_name" />';
			$html .= '</p>';

			// Credit card number.
			$html .= '<p class="form-row form-row-wide">';
				$html .= '<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', $this->plugin_slug ) . ' <span class="required">*</span></label>';
				$html .= '<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . $this->id . '_card_number" />';
			$html .= '</p>';

			// Credit card expiry.
			$html .= '<p class="form-row form-row-first">';
				$html .= '<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', $this->plugin_slug ) . ' <span class="required">*</span></label>';
				$html .= '<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="MM / YY" name="' . $this->id . '_card_expiry" />';
			$html .= '</p>';

			// Credit card CVC.
			$html .= '<p class="form-row form-row-last">';
				$html .= '<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', $this->plugin_slug ) . ' <span class="required">*</span></label>';
				$html .= '<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="CVC" name="' . $this->id . '_card_cvc" />';
			$html .= '</p>';

			// Installments.
			$html .= '<p class="form-row form-row-wide">';
				$html .= '<label for="' . esc_attr( $this->id ) . '-installments">' . __( 'Installments', $this->plugin_slug ) . '</label>';
				$html .= '<select id="' . esc_attr( $this->id ) . '-installments" class="input-text wc-credit-card-form-installments" name="' . $this->id . '_installments">';

					// Get the cart total.
					$cart_total = $this->woocommerce_instance()->cart->total;

					// Create the installments.
					for ( $installment = 1; $installment <= 12; $installment++ ) {
						$installment_value = $cart_total / $installment;

						// Stops if the installment is less than 5.
						if ( $installment_value <= 5 ) {
							break;
						}
						$html .= '<option value="' . $installment . '">' . sprintf( '%dx of %s', $installment, strip_tags( wc_price( $installment_value ) ) ) . '</option>';
					}
				$html .= '</select>';
			$html .= '</p>';

			$html .= '<div class="clear"></div>';
		$html .= '</fieldset>';

		echo $html;
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
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
				$shipping_total = $this->extract_cents( $order->get_total_shipping() );
			} else {
				$shipping_total = $this->extract_cents( $order->get_shipping() );
			}

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
					if ( ! empty( $product->get_sku() ) ) {
						$item_sku = $product->get_sku();
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
						'FreightCostInCents'         => $shipping_total,
						'ShoppingCartItemCollection' => array(
							'ShoppingCartItem'       => $cart_items
						)
					)
				)
			);
		}

		// Credit card.
		// if ( isset( $_POST['mundipagg_payment_type'] ) && 'credit-card' == $_POST['mundipagg_payment_type'] ) {
		if ( isset( $_POST['mundipagg_holder_name'] ) ) {
			$credit_cards = array();

			$expiry = $this->get_credit_card_expiry_date( sanitize_text_field( $_POST['mundipagg_card_expiry'] ) );
			$credit_card = array(
				'AmountInCents'           => $total,
				'CreditCardNumber'        => sanitize_text_field( $_POST['mundipagg_card_number'] ),
				'InstallmentCount'        => intval( $_POST['mundipagg_installments'] ),
				'HolderName'              => sanitize_text_field( $_POST['mundipagg_holder_name'] ),
				'SecurityCode'            => sanitize_text_field( $_POST['mundipagg_card_cvc'] ),
				'ExpMonth'                => $expiry['month'],
				'ExpYear'                 => $expiry['year'],
				'CreditCardBrandEnum'     => 'Visa',
				'PaymentMethodCode'       => ( 'yes' == $this->staging ) ? 1 : null,
				// 'PaymentMethodCode'       => null,
				'CreditCardOperationEnum' => 'AuthOnly',
			);

			$credit_cards[] = $credit_card;

			$request['createOrderRequest']['CreditCardTransactionCollection'] = array(
				'CreditCardTransaction' => $credit_cards
			);
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
			$soap          = new SoapClient( $this->api_url, $soap_opt );
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
		$order    = new WC_Order( $order_id );
		$response = $this->generate_payment_token( $order );

		if ( ! empty( $response ) ) {
			$response = $response[0]->CreateOrderResult;

			// Processes the errors.
			if ( 1 != $response->Success ) {
				if ( isset( $response->ErrorReport->ErrorItemCollection->ErrorItem ) ) {
					if ( is_array( $response->ErrorReport->ErrorItemCollection->ErrorItem ) ) {
						foreach ( $response->ErrorReport->ErrorItemCollection->ErrorItem as $error ) {
							$this->add_error( '<strong>' . __( 'MundiPagg', $this->plugin_slug ) . '</strong>: ' . esc_attr( $error->Description ) );
						}
					} else {
						$this->add_error( '<strong>' . __( 'MundiPagg', $this->plugin_slug ) . '</strong>: ' . esc_attr( $response->ErrorReport->ErrorItemCollection->ErrorItem->Description ) );
					}
				} else {
					$this->add_error( '<strong>' . __( 'MundiPagg', $this->plugin_slug ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', $this->plugin_slug ) );
				}
			} else {
				$updated = $this->update_order_status( $response );

				if ( $updated ) {
					// Remove cart.
					$this->woocommerce_instance()->cart->empty_cart();

					if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
						$url = $order->get_checkout_order_received_url();
					} else {
						$url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
					}

					// Go to thankyou page.
					return array(
						'result'   => 'success',
						'redirect' => $url
					);
				}
			}
		} else {
			$this->add_error( '<strong>' . __( 'MundiPagg', $this->plugin_slug ) . '</strong>: ' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', $this->plugin_slug ) );
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
	 * Update order status!
	 *
	 * @param  object $data MundiPagg order data.
	 *
	 * @return bool
	 */
	public function update_order_status( $data ) {
		$valid = false;

		if ( isset( $data->OrderReference ) ) {
			$order_id = (int) str_replace( $this->invoice_prefix, '', $data->OrderReference );
			$order    = new WC_Order( $order_id );

			// Checks whether the invoice number matches the order.
			// If true processes the payment.
			if ( $order->id === $order_id ) {
				update_post_meta( $order->id, 'MundiPagg OrderKey', sanitize_text_field( $data->OrderKey ) );
				$order_status = sanitize_text_field( $data->OrderStatusEnum );

				// Ref: http://mundipagg.freshdesk.com/support/solutions/articles/175822-status-
				switch ( $order_status ) {
					case 'Opened':
						$order->update_status( 'on-hold', __( 'MundiPagg: This order has transactions that have not yet been fully processed.', $this->plugin_slug ) );
						$valid = true;

						break;
					case 'Paid':
						$order->add_order_note( __( 'MundiPagg: Transaction approved.', $this->plugin_slug ) );
						$order->payment_complete();
						$valid = true;

						break;
					case 'OverPaid':
						$order->add_order_note( __( 'MundiPagg: This order was paid with a higher value than expected.', $this->plugin_slug ) );
						$order->payment_complete();
						$valid = true;

						break;
					case 'Canceled':
						$order->update_status( 'cancelled', __( 'MundiPagg: All transactions were canceled.', $this->plugin_slug ) );
						$valid = true;

						break;
					case 'PartialPaid':
						$order->update_status( 'on-hold', __( 'MundiPagg: Only a few transactions have been paid to date.', $this->plugin_slug ) );
						$valid = true;

						break;

					default:
						// No action xD.
						break;
				}
			}
		}

		return $valid;
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
