<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce MundiPagg API class.
 *
 * @package WooCommerce_MundiPagg/API
 */
class WC_Mundipagg_API {

	/**
	 * Gateway class.
	 *
	 * @var object
	 */
	protected $gateway;

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $method = '';

	/**
	 * Constructor.
	 *
	 * @param object $gateway
	 * @param string $method
	 */
	public function __construct( $gateway = null, $method = '' ) {
		$this->gateway = $gateway;
		$this->method  = $method;
	}

	/**
	 * Get the WSDL URL.
	 *
	 * @return string
	 */
	protected function get_wsdl_url() {
		return 'https://' . sanitize_text_field( $this->gateway->environment ) . '.mundipaggone.com/mundipaggservice.svc?wsdl';
	}

	/**
	 * Check the environment.
	 *
	 * @return bool
	 */
	public function check_environment() {
		if ( 'staging' == $this->gateway->environment ) {
			return true;
		}

		// Check for SSL enabled.
		return 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) && is_ssl();
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		$supported_currencies = apply_filters( 'woocommerce_mundipagg_supported_currencies', array(
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

		return in_array( get_woocommerce_currency(), $supported_currencies );
	}

	/**
	 * Extract cents of money valey.
	 *
	 * @param float $value
	 *
	 * @return int
	 */
	protected function extract_cents( $value ) {
		return intval( number_format( $value, 2, '', '' ) );
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

		if ( isset( $countries[ $code ] ) ) {
			return $countries[ $code ];
		}

		return $code;
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
		return preg_replace( '/\D/', '', $string );
	}

	/**
	 * Get valid value.
	 * Prevents users from making shit!
	 *
	 * @param  string|int|float $value
	 *
	 * @return int|float
	 */
	protected function get_valid_value( $value ) {
		$value = str_replace( '%', '', $value );
		$value = str_replace( ',', '.', $value );

		return $value;
	}

	/**
	 * Get credit card brand.
	 *
	 * @param  string $number
	 *
	 * @return string
	 */
	protected function get_card_brand( $number ) {
		$brand = '';

		// https://gist.github.com/arlm/ceb14a05efd076b4fae5
		$supported_brands = array(
			'Visa'       => '/^4\d{12}(\d{3})?$/',
			'Mastercard' => '/^(5[1-5]\d{4}|677189)\d{10}$/',
			'Hipercard'  => '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
			'Amex'       => '/^3[47]\d{13}$/',
			'Diners'     => '/^3(0[0-5]|[68]\d)\d{11}$/',
			'Elo'        => '/^((((636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/'
		);

		foreach ( $supported_brands as $key => $value ) {
			if ( preg_match( $value, $number ) ) {
				$brand = $key;
				break;
			}
		}

		return $brand;
	}

	/**
	 * Generate the payment data.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return string          Payment data.
	 */
	protected function generate_payment_data( $order ) {
		// Order total in cents.
		$order_total  = $this->extract_cents( (float) $order->get_total() );
		$installments = isset( $_POST['mundipagg_installments'] ) ? intval( $_POST['mundipagg_installments'] ) : 1;

		// Set the order total with interest.
		if ( 'credit-card' == $this->method && isset( $this->gateway->interest ) && ( $installments >= $this->gateway->interest && 0 != $this->gateway->interest ) ) {
			$order_total = $order_total * ( ( 100 + $this->get_valid_value( $this->gateway->interest_rate ) ) / 100 );
		}

		// Order request.
		$request = array(
			'createOrderRequest' => array(
				'MerchantKey'                     => $this->gateway->merchant_key,
				'OrderReference'                  => $this->gateway->invoice_prefix . $order->id,
				'AmountInCents'                   => $order_total,
				'AmountInCentsToConsiderPaid'     => $order_total,
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
			'HomePhone'                 => $this->get_only_numbers( $order->billing_phone ),
			'WorkPhone'                 => '',
			'MobilePhone'               => isset( $order->billing_cellphone ) ? $this->get_only_numbers( $order->billing_cellphone ) : '',
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
				),
				array(
					'City'            => $order->shipping_city,
					'Complement'      => $order->shipping_address_2,
					'CountryEnum'     => $this->get_country( $order->shipping_country ),
					'District'        => isset( $order->shipping_neighborhood ) ? $order->shipping_neighborhood : '',
					'Number'          => isset( $order->shipping_number ) ? $order->shipping_number : '',
					'State'           => $order->shipping_state,
					'Street'          => $order->shipping_address_1,
					'ZipCode'         => $order->shipping_postcode,
					'AddressTypeEnum' => 'Shipping',
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

		// Shop cart.
		if ( 0 < sizeof( $order->get_items() ) ) {
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
					if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.4.0', '<' ) ) {
						$item_meta = new WC_Order_Item_Meta( $order_item['item_meta'] );
					} else {
						$item_meta = new WC_Order_Item_Meta( $order_item );
					}

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

		// Payment type.
		if ( 'credit-card' == $this->method ) {
			$credit_cards = array();
			$expiry       = $this->get_credit_card_expiry_date( sanitize_text_field( $_POST['mundipagg_card_expiry'] ) );
			$card_number  = sanitize_text_field( $this->get_only_numbers( $_POST['mundipagg_card_number'] ) );
			$card_brand   = $this->get_card_brand( $card_number );
			$credit_card  = array(
				'AmountInCents'           => $order_total,
				'CreditCardNumber'        => $card_number,
				'InstallmentCount'        => $installments,
				'HolderName'              => sanitize_text_field( $_POST['mundipagg_holder_name'] ),
				'SecurityCode'            => sanitize_text_field( $_POST['mundipagg_card_cvc'] ),
				'ExpMonth'                => $expiry['month'],
				'ExpYear'                 => $expiry['year'],
				'CreditCardBrandEnum'     => $card_brand,
				'PaymentMethodCode'       => ( 'staging' == $this->gateway->environment ) ? 1 : null,
				'CreditCardOperationEnum' => sanitize_text_field( $this->gateway->auth_capture ),
			);

			if ( 'AuthAndCaptureWithDelay' == $this->gateway->auth_capture ) {
				$capture_delay = asbint( $this->gateway->capture_delay );
				$credit_card['CaptureDelayInMinutes'] = ( 7200 >= $capture_delay ) ? $capture_delay : 7200;
			}

			update_post_meta( $order->id, '_mundipagg_credit_card_data', array(
				'brand'        => $card_brand,
				'installments' => $installments
			) );

			$credit_cards[] = $credit_card;

			$request['createOrderRequest']['CreditCardTransactionCollection'] = array(
				'CreditCardTransaction' => $credit_cards
			);
		} else if ( 'banking-ticket' == $this->method ) {
			$tickets = array();
			$ticket  = array(
				'AmountInCents'                   => $order_total,
				'Instructions'                    => $this->gateway->instructions,
				'NossoNumero'                     => $this->gateway->our_number,
				'DaysToAddInBoletoExpirationDate' => $this->gateway->days_to_pay,
				'TransactionReference'            => sprintf( __( 'Payment for the order %s', 'woocommerce-mundipagg' ), $order->get_order_number() ),
				'BankNumber'                      => $this->gateway->bank_number,
			);

			$tickets[] = $ticket;

			$request['createOrderRequest']['BoletoTransactionCollection'] = array(
				'BoletoTransaction' => $tickets
			);
		}

		$request = apply_filters( 'woocommerce_mundipagg_payment_data', $request, $order );

		return $request;
	}

	/**
	 * Generate Payment.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return array
	 */
	public function generate_payment( $order ) {
		$data     = $this->generate_payment_data( $order );
		$response = array();

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Requesting payment for order ' . $order->get_order_number() . ' with the following data: ' . print_r( $data, true ) );
		}

		$soap_opt = array(
			'encoding'   => 'UTF-8',
			'trace'      => true,
			'exceptions' => true,
			'cache_wsdl' => false,
		);

		try {
			$soap          = new SoapClient( $this->get_wsdl_url(), $soap_opt );
			$soap_response = $soap->CreateOrder( $data );
			$response[]    = $soap_response;

			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'MundiPagg response for the order ' . $order->get_order_number() . ': ' . print_r( $soap_response, true ) );
			}
		} catch ( Exception $e ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Error while generate the payment for order ' . $order->get_order_number() . ', MundiPagg response: ' . print_r( $e->getMessage(), true ) );
			}
		}

		return $response;
	}

	/**
	 * Get installments HTML.
	 *
	 * @param  float  $order_total Order total.
	 *
	 * @return string
	 */
	public function get_installments_html( $order_total = 0 ) {
		$html         = '';
		$installments = apply_filters( 'woocommerce_mundipagg_max_installments', $this->gateway->installments, $order_total );

		if ( '1' == $installments ) {
			return $html;
		}

		$html .= '<select id="mundipagg-installments" name="mundipagg_installments" style="font-size: 1.5em; padding: 4px; width: 100%;">';

		for ( $i = 1; $i <= $installments; $i++ ) {
			$credit_total    = $order_total / $i;
			$credit_interest = sprintf( __( 'no interest. Total: %s', 'woocommerce-mundipagg' ), sanitize_text_field( wc_price( $order_total ) ) );
			$smallest_value  = ( 5 <= $this->gateway->smallest_installment ) ? $this->gateway->smallest_installment : 5;

			if ( $i >= $this->gateway->interest && 0 != $this->gateway->interest ) {
				$interest_rate        = $this->get_valid_value( $this->gateway->interest_rate ) / 100;
				$interest_total       = $order_total * ( $interest_rate / ( 1 - ( 1 / pow( 1 + $interest_rate, $i ) ) ) );
				$interest_order_total = $interest_total * $i;

				if ( $credit_total < $interest_total ) {
					$credit_total    = $interest_total;
					$credit_interest = sprintf(__( 'with interest of %s%% a.m. Total: %s', 'woocommerce-mundipagg' ), $this->get_valid_value( $this->gateway->interest_rate ), sanitize_text_field( wc_price( $interest_order_total ) ) );
				}
			}

			if ( 1 != $i && $credit_total < $smallest_value ) {
				continue;
			}

			$html .= '<option value="' . $i . '">' . esc_html( sprintf( __( '%sx of %s %s', 'woocommerce-mundipagg' ), $i, sanitize_text_field( wc_price( $credit_total ) ), $credit_interest ) ) . '</option>';
		}

		$html .= '</select>';

		return $html;
	}

	/**
	 * Validate card fields.
	 *
	 * @param  array $posted
	 *
	 * @return bool
	 */
	public function validate_card_fields( $posted ) {
		try {
			// Validate the card number.
			if ( ! isset( $posted['mundipagg_card_number'] ) || '' === $posted['mundipagg_card_number'] ) {
				throw new Exception( __( 'Please type the card number.', 'woocommerce-mundipagg' ) );
			}

			// Validate name typed for the card.
			if ( ! isset( $posted['mundipagg_holder_name'] ) || '' === $posted['mundipagg_holder_name'] ) {
				throw new Exception( __( 'Please type the name of the card holder.', 'woocommerce-mundipagg' ) );
			}

			// Validate the expiration date.
			if ( ! isset( $posted['mundipagg_card_expiry'] ) || '' === $posted['mundipagg_card_expiry'] ) {
				throw new Exception( __( 'Please type the card expiry date.', 'woocommerce-mundipagg' ) );
			}

			// Validate the cvv for the card.
			if ( ! isset( $posted['mundipagg_card_cvc'] ) || '' === $posted['mundipagg_card_cvc'] ) {
				throw new Exception( __( 'Please type the cvc code for the card', 'woocommerce-mundipagg' ) );
			}

		} catch ( Exception $e ) {
			wc_add_notice( '<strong>' . esc_html( $this->gateway->title ) . '</strong>: ' . esc_html( $e->getMessage() ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Validate installments.
	 *
	 * @param  array $posted
	 * @param  float $order_total
	 *
	 * @return bool
	 */
	public function validate_installments( $posted, $order_total ) {
		// Stop if don't have installments.
		if ( ! isset( $posted['mundipagg_installments'] ) && 1 == $this->gateway->installments ) {
			return true;
		}

		try {

			// Validate the installments field.
			if ( ! isset( $posted['mundipagg_installments'] ) || '' === $posted['mundipagg_installments'] ) {
				throw new Exception( __( 'Please select a number of installments.', 'woocommerce-mundipagg' ) );
			}

			$installments      = absint( $posted['mundipagg_installments'] );
			$installment_total = $order_total / $installments;
			$_installments     = apply_filters( 'wc_cielo_max_installments', $this->gateway->installments, $order_total );

			if ( $installments >= $this->gateway->interest && 0 != $this->gateway->interest ) {
				$interest_rate     = $this->get_valid_value( $this->gateway->interest_rate ) / 100;
				$interest_total    = $order_total * ( $interest_rate / ( 1 - ( 1 / pow( 1 + $interest_rate, $installments ) ) ) );
				$installment_total = ( $installment_total < $interest_total ) ? $interest_total : $installment_total;
			}
			$smallest_value = ( 5 <= $this->gateway->smallest_installment ) ? $this->gateway->smallest_installment : 5;

			if ( $installments > $_installments || 1 != $installments && $installment_total < $smallest_value ) {
				throw new Exception( __( 'Invalid number of installments!', 'woocommerce-mundipagg' ) );
			}
		} catch ( Exception $e ) {
			wc_add_notice( '<strong>' . esc_html( $this->gateway->title ) . '</strong>: ' . esc_html( $e->getMessage() ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Update order status!
	 *
	 * @param  string $reference.
	 * @param  string $status.
	 * @param  string $invoice_prefix
	 *
	 * @return bool
	 */
	public static function update_order_status( $reference, $status, $invoice_prefix ) {
		$valid = false;

		$order_id = intval( str_replace( $invoice_prefix, '', $reference ) );
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
					if ( 'paid' == $order_status ) {
						$order->add_order_note( __( 'MundiPagg: Transaction approved.', 'woocommerce-mundipagg' ) );
					} else if ( 'captured' == $order_status ) {
						$order->add_order_note( __( 'MundiPagg: Payment captured.', 'woocommerce-mundipagg' ) );
					} else if ( 'overpaid' == $order_status ) {
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
	 * Process the notification data.
	 *
	 * @param  string           $id
	 * @param  string           $merchant_key
	 * @param  SimpleXMLElement $xml
	 * @param  string           $debug
	 *
	 * @return array
	 */
	public static function process_notification_data( $id, $merchant_key, $xml, $debug = 'no' ) {
		if ( 'yes' == $debug ) {
			$log = new WC_Logger();
		}

		if ( 'yes' == $debug ) {
			$log->add( $id, 'IPN request: ' . print_r( $xml, true ) );
		}

		try {
			if ( ! isset( $xml->OrderStatus ) ) {
				throw new Exception( 'Missing OrderStatus param' );
			}

			if ( ! isset( $xml->OrderReference ) ) {
				throw new Exception( 'Missing OrderReference param' );
			}

			if ( ! isset( $xml->MerchantKey ) ) {
				throw new Exception( 'Missing MerchantKey param' );
			}

			$_merchant_key = (string) $xml->MerchantKey;
			if ( strtoupper( $merchant_key ) !== strtoupper( $_merchant_key ) ) {
				throw new Exception( 'Invalid MerchantKey returned' );
			}

			return array(
				'reference' => (string) $xml->OrderReference,
				'status'    => (string) $xml->OrderStatus
			);
		} catch ( Exception $e ) {
			if ( 'yes' == $debug ) {
				$log->add( $id, 'IPN error: ' . print_r( $e->getMessage(), true ) );
			}

			return array();
		}
	}

	/**
	 * Notification handler.
	 *
	 * @param SimpleXMLElement $xml
	 * @param string           $method
	 */
	public static function notification_handler( $xml, $method ) {
		$method         = sanitize_text_field( $method );
		$id             = 'mundipagg-' . $method;
		$options        = get_option( 'woocommerce_' . $id . '_settings', array() );
		$merchant_key   = isset( $options['merchant_key'] ) ? $options['merchant_key'] : '';
		$invoice_prefix = isset( $options['invoice_prefix'] ) ? $options['invoice_prefix'] : 'WC-';
		$debug          = isset( $options['debug'] ) ? $options['debug'] : '';
		$data           = self::process_notification_data( $id, $merchant_key, $xml, $debug );

		if ( ! empty( $data ) ) {
			header( 'HTTP/1.1 200 OK' );

			self::update_order_status( $data['reference'], $data['status'], $invoice_prefix );

			exit;
		}
	}
}
