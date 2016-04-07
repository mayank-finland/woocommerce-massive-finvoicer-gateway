<?php
/* Finvoicer Gateway Class */
class MASSIVE_Finvoicer_Gateway extends WC_Payment_Gateway {

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "massive_finvoicer_gateway";

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "Finvoicer", 'massive-finvoicer-gateway' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "Finvoicer Invoicing Gateway Plug-in for WooCommerce", 'massive-finvoicer-gateway' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( "Finvoicer", 'massive-finvoicer-gateway' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = null;

		// Bool. Can be set to true if you want payment fields to show on the checkout
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = true;

		// Supports the default credit card form
		$this->supports = array( 'default_credit_card_form' );


		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	} // End __construct()

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'massive-finvoicer-gateway' ),
				'label'		=> __( 'Enable this invoicing gateway', 'massive-finvoicer-gateway' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'massive-finvoicer-gateway' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'massive-finvoicer-gateway' ),
				'default'	=> __( 'Finvoicer laskutus', 'massive-finvoicer-gateway' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'massive-finvoicer-gateway' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'massive-finvoicer-gateway' ),
				'default'	=> __( 'Pay securely using your credit card.', 'massive-finvoicer-gateway' ),
				'css'		=> 'max-width:350px;'
			),
			'api_login' => array(
				'title'		=> __( 'Finvoicer API Login', 'massive-finvoicer-gateway' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the API Login provided by Finvoicer when you signed up for an account.', 'massive-finvoicer-gateway' ),
			),
			'trans_key' => array(
				'title'		=> __( 'Finvoicer Transaction Key', 'massive-finvoicer-gateway' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'This is the Transaction Key provided by Authorize.net when you signed up for an account.', 'massive-finvoicer-gateway' ),
			)
		);
	}


	// Adding custom fields if Finvoicer used as payment option
	function payment_fields() {
		$payment_form_fields = ob_start();
		?>

		<div class="form-horizontal">
			<label class="btn btn-success btn-md">
			    <input class="laskutus" type="radio" name="laskutus" autocomplete="off" value="email"/> Sähköpostilasku (pdf)
			</label>
			<label class="btn btn-success btn-md" id="elasku">
			    <input class="laskutus" type="radio" name="laskutus"  autocomplete="off" value="e_invoice"/> Verkkolasku
			</label>
			<label class="btn btn-success btn-md" id="kirjelasku">
				<input class="laskutus" type="radio" name="laskutus"  autocomplete="off" value="kirjelasku" checked/> Kirje postitse
			</label>
			
			<div class="billinfo" id="email">
				<p class="form-row form-row form-row-wide">
					<label for="lasku-sposti">Lasku lähetetään osoitteeseen <abbr class="required" title="required">*</abbr></label>
					<input type="email" class="input-text" id="lasku-sposti" placeholder="lasku@yritys.fi" name="lasku-sposti">
				</p>
			</div>
			<div class="billinfo" id="e_invoice">
				<p class="form-row form-row form-row-wide">
					<label for="ovtiban" class="">OVT tai IBAN<abbr class="required" title="required">*</abbr></label>
					<input type="text" class="input-text nospace" id="ovtiban" placeholder="003712345678 tai FI00111122223333" name="ovtiban"/>
				</p>
				<p class="form-row form-row form-row-wide">
					<label for="ovt-tunnus" class="">Välittäjän (operaattorin) OVT-tunnus</label>
					<input type="text" class="input-text nospace" id="ovt-tunnus" placeholder="0037122345678" name="ovt-tunnus"/>
				</p>
			</div>
			<p class="form-row form-row form-row-wide">
				<label for="viitteenne" class="">Viitteenne</label>
				<input type="text" class="input-text " id="viitteenne" placeholder=" Viitteenne" name="viitteenne"/>
			</p>
		</div>
		<script type="text/javascript">

			jQuery(document).ready(function(){ 
				jQuery("div.billinfo").hide();
				jQuery(".billinfo input").prop('required', false);
			    jQuery('input.nospace').keydown(function(e) {
				    if (e.keyCode == 32) {
				        return false;
				    }
				});
			    jQuery('input[name=laskutus]').on('click', function(){       
			       var val = jQuery("input[name=laskutus]:checked").val();
				   if (val == 'email') {
					   jQuery('div[id$='+jQuery(this).val()+']').show();
					   jQuery("input#lasku-sposti").prop('required', true);
					   jQuery("input#ovtiban").prop('required', false);
				       jQuery('#e_invoice').hide();
				    } else if (val == 'e_invoice') {
				        jQuery('div[id$='+jQuery(this).val()+']').show();
				        jQuery('#email').hide();
				        jQuery("input#lasku-sposti").prop('required', false);
					   jQuery("input#ovtiban").prop('required', true);
				    } else {
				        jQuery("div.billinfo").hide();
				        jQuery("input#lasku-sposti").prop('required', false);
					   jQuery("input#ovtiban").prop('required', false);
				    }
			    });
	    
	    	});

		</script>

		<?php
		$payment_form_fields = ob_get_clean();
		echo $payment_form_fields;
	}


	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;

		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );
		$items = $customer_order->get_items();
		foreach ( $items as $item ) {
			$product_name = $item['name'];
			$product_id = $item['item_meta']['_product_id'][0];
			break;
		}
		$product_price = get_post_meta( get_the_ID(), '_regular_price');
		$product = new WC_Product( $product_id );
		$sku = $product->get_sku();

		// Order total
		$order_total = $customer_order->order_total;

		// Customer details
		$customer_order_number	= str_replace( "#", "", $customer_order->get_order_number() );
		$customer_id = $customer_order->user_id;
		$customer_fname = $customer_order->billing_first_name;
		$customer_lname = $customer_order->billing_last_name;
		$customer_billing_address = $customer_order->billing_address_1;
		$customer_city = $customer_order->billing_city;
		$customer_state = $customer_order->billing_state;
		$customer_postcode = $customer_order->billing_postcode;
		$customer_country = $customer_order->billing_country;
		$customer_phone = $customer_order->billing_phone;
		$customer_email = $customer_order->billing_email;
		$customer_ytunnus = $customer_order->billing_ytunnus;

		$laskutus			= $_POST['laskutus'];
		$lasku_sposti		= $_POST['lasku-sposti'];
		$lasku_ovt_iban		= str_replace( " ", "", $_POST['ovtiban'] );
		$lasku_ovt_tunnus	= str_replace( " ", "", $_POST['ovt-tunnus'] );
		$viitteenne			= str_replace( " ", "", $_POST['viitteenne'] );

		$time            = date("Y-m-d");

		// generating XML to push to Finvoicer
		$xml = ob_start(); // output buffering;
	    echo '<?xml version="1.0" encoding="utf-8"?>';
	    ?>
			<request>
				<responsetype>json</responsetype>
				<payload type="invoice">
					<confirmed>0</confirmed>
					<custom_data>
						<alternative_company>1</alternative_company>
						<alternative_logo>1</alternative_logo>
						<company_abbreviation>TK</company_abbreviation>
					</custom_data>
					<invoice_date><?php echo esc_html( $time ); ?></invoice_date>
					<duedays>14</duedays>
					<refer_to><?php echo esc_html( $viitteenne ); ?></refer_to>
					<referred_from>3010 <?php echo esc_html( $sku ); ?></referred_from>
					<language>FI</language>
					<allow_collection>1</allow_collection>
					<interest_rate>16.0</interest_rate>
					<text>
						<?php echo esc_html( $product_name );?>
					</text>
					<recipient type="organization">
						<person_id/>
						<vat_id><?php echo esc_html( $customer_ytunnus ); ?></vat_id>
						<name><?php echo esc_html( $customer_fname );?> <?php echo esc_html( $customer_lname );?></name>
						<address><?php echo esc_html( $customer_billing_address ); ?></address>
						<zip><?php echo esc_html( $customer_postcode ); ?></zip>
						<city><?php echo esc_html( $customer_city ); ?></city>
						<country>FI</country>
						<?php if ( isset( $_POST['laskutus'] ) ) {
							
							switch ( $_POST['laskutus'] ) {
								case 'e_invoice':
									?>
									<delivery type="e_invoice">
										<email/>
										<address><?php echo esc_html( $lasku_ovt_iban ); ?></address>
										<intermediator><?php echo esc_html( $lasku_ovt_tunnus ); ?></intermediator>
									</delivery><?php
									break;
								case 'kirjelasku':
									?>
									<delivery type="post">
									</delivery><?php
									break;
								case 'email':
									?>
									<delivery type="email">
										<email><?php echo esc_html( $lasku_sposti); ?></email>
									</delivery><?php
									break;
							}
						}
						?>												
					</recipient>
					<items>
						<item>
							<name><?php echo esc_html( $product_name );?></name>
							<quantity>1</quantity>
							<unit>KPL</unit>
							<unit_price><?php echo esc_html( $order_total / 1.24 ); ?></unit_price>
							<vat_rate>24</vat_rate>
							<total><?php echo esc_html( $order_total ); ?></total>
						</item>
					</items>
				</payload>
			</request><?php
	    $xml = ob_get_clean();
/*
	    print_r ($xml);
	    return;
*/
	    

  		// Decide which URL to post to
		$environment_url = 'http://api.onecapital.fi/save_invoice.php';
		$key             =  $this->trans_key;
		$gateway_id      = $this->api_login;

		// Send this payload to Finvoicer for processing

		$checksum = hash('sha256', $gateway_id.'&'.$xml.'&'.$key);

		$response = wp_remote_post( $environment_url, array(
			'method'    => 'POST',
			'body' => array( 'id' => $gateway_id, 'request' => $xml, 'checksum' => $checksum),
			'timeout'   => 90,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'massive-finvoicer-gateway' ) );
		}

		if ( empty( $response['body'] ) ) {
			throw new Exception( __( 'Finvoicer\'s Response was empty.', 'massive-finvoicer-gateway' ) );
		}

		// Retrieve the body's resopnse if no errors found
		$response_body = wp_remote_retrieve_body( $response );

		$response_json = json_decode( $response_body );

		// Test the code to know if the transaction went through or not.
		// 1 or 4 means the transaction was a success
		if ( $response_json->result['code'] == 0) {
			// Payment has been successful
			$customer_order->add_order_note( __( 'Finvoicer process completed.', 'massive-finvoicer-gateway' ) );

			// Mark order as Paid
			$customer_order->payment_complete();
			
			// Empty the cart (Very important step)
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			// Transaction was not succesful
			// Add notice to the cart
			wc_add_notice( $r['response_reason_text'], 'error' );
			// Add note to the order for your reference
			$customer_order->add_order_note( 'Error: asdfasdf' );
		}

	}

	// Validate fields
	public function validate_fields() {
		return true;
	}

} // End of Finvoicer class
