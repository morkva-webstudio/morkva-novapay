<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mrkv_NovaPay_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'mrkv_novapay';
		$this->has_fields         = false;
		$this->method_title       = 'NovaPay by morkva';
		$this->method_description = __( 'NovaPay payment gateway integration for WooCommerce.', 'morkva-novapay' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', 'NovaPay' );
		$this->description = $this->get_option( 'description', '' );
		$this->enabled     = $this->get_option( 'enabled', 'no' );

		$this->supports = [ 'products' ];

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'sync_on_thankyou' ], 5 );
	}

	public function admin_options() {
        $back_link = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
        ?>
        <style>
            #woocommerce_mrkv_novapay_description,#woocommerce_mrkv_novapay_private_key,#woocommerce_mrkv_novapay_novapay_public_key,label[for="woocommerce_mrkv_novapay_debug"],p.description{max-width: 400px;}
            .morkva-settings-main > h2 + p{display: none !important;}
            td label[for="woocommerce_morkva-iban_enabled"]{color: transparent;}
            .morkva-settings-sidebar img{width: 10px;}
            .morkva-settings-sidebar a img{filter: brightness(100);}
            @media(max-width: 768px){
                .morkva-settings-wrapper{flex-direction: column;}
            }
        </style>
        <div class="morkva-settings-wrapper" style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 20px;">
            
            <div class="morkva-settings-main" style="flex: 3;">
                <h2 class="wc-admin-header">
                    <small>
                        <a href="<?php echo esc_url( $back_link ); ?>" aria-label="<?php esc_attr_e( 'Return to payments', 'woocommerce' ); ?>">
                            <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                        </a>
                    </small>
                    <?php echo esc_html( $this->method_title ); ?>
                </h2>

                <?php echo wpautop( $this->method_description ); ?>
                
                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
            </div>
            <div class="morkva-settings-sidebar" style="flex: 1; min-width: 250px;">
				<div class="morkva-settings-sidebar_inner" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;margin-bottom:15px;">
					<h3 style="margin-top: 0;"><?php echo __( 'Like this plugin?', 'morkva-novapay' ); ?></h3>
					<p>
						<?php echo __( 'Support our efforts with a', 'morkva-novapay' ) . ' '; ?>
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<?php echo __( 'review at', 'morkva-novapay' ) . ' <a href="https://wordpress.org/plugins/morkva-novapay/" target="blanc">WordPress.org</a>'; ?>
					</p>
					<a class="button button-primary" href="https://wordpress.org/plugins/morkva-novapay/" target="blanc">
						<?php echo __( 'Leave', 'morkva-novapay' ) . ' '; ?>
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">
					</a>
					<p>
						<?php echo __( 'Isn’t good enough for a 5', 'morkva-novapay' ) . ' '; ?>
						<img src="<?php echo plugins_url( '../assets/images/star.svg', __FILE__ ); ?>" alt="Star" alt="Star">? 
						<?php echo __( 'Contact us via the widget on our website, or check out', 'morkva-novapay' ) . ' <a href="https://docs.morkva.co.ua/uk?utm_source=plugin&utm_medium=sidebar&utm_campaign=novapay_free" target="blanc">' . __( 'documentation', 'morkva-novapay' ) . '</a>'; ?>
					</p>
					<div class="mrkv-btns-line-sidebar" style="display: flex;gap: 4px;">
						<a class="button button-primary" href="https://morkva.co.ua/?utm_source=plugin&utm_medium=sidebar&utm_campaign=novapay_free" target="blanc">
							<?php echo __( 'Go to the website', 'morkva-novapay' ); ?>
						</a>
						<a class="button" href="https://docs.morkva.co.ua/uk?utm_source=plugin&utm_medium=sidebar&utm_campaign=novapay_free" target="blanc">
							<?php echo __( 'Documentation', 'morkva-novapay' ); ?>
						</a>
					</div>
				</div>
				<div class="morkva-settings-sidebar_inner" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;margin-bottom:15px;">
					<h3 style="margin-top: 0;"><?php echo __( 'Other free plugins', 'morkva-novapay' ); ?></h3>
					<p><?php echo __( 'All our plugins are cross-compatible', 'morkva-novapay' ); ?></p>
					<?php
						$response = wp_remote_get( 'https://morkva.co.ua/wp-json/pluginManagement/v2', array(
							'headers' => array(
							),
							'timeout' => 30,
							'redirection' => 5,
							'httpversion' => '1.1',
							'sslverify' => true
						));

						$mrkv_mono_response_data = $response['body'] ? json_decode( $response['body'], true ) : null;
						$mrkv_mono_plugins = $mrkv_mono_response_data['plugins'] ?? [];

						if(!empty($mrkv_mono_plugins))
						{
							?>
								<ul style="list-style: disc;padding-left: 17px;">
									<?php
										foreach($mrkv_mono_plugins as $plugin_slug => $plugin_data)
										{
											if($plugin_slug == 'morkva-novapay'){ continue; }
											?>
												<li>
													<a style="margin-bottom:5px;" href="<?php echo $plugin_data['url'] ?? ''; ?>?utm_source=plugin&utm_medium=sidebar&utm_campaign=novapay_free" target="blanc" class="plugin_line"><?php echo $plugin_data['label'] ?? ''; ?></a>
													<span>- 
													<?php 
														$current_desc = (strpos(get_user_locale(), 'uk') === 0) 
															? ($plugin_data['description'] ?? '') 
															: ($plugin_data['description_en'] ?? '');
															
														echo $current_desc; 
													?>
													</span>
												</li>
											<?php
										}
									?>
								</ul>
							<?php
						}
					?>
				</div>
			</div>
        </div>
        <?php
    }

	public function sync_on_thankyou( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->is_paid() ) {
			return;
		}

		$session_id = (string) $order->get_meta( Mrkv_NP_Postback::META_SESSION_ID );
		if ( '' === $session_id ) {
			return;
		}

		try {
			$client = new Mrkv_NP_Client( $this->settings );
			$data   = $client->get_status( $session_id );
			if ( empty( $data['status'] ) ) {
				return;
			}
			Mrkv_NP_Postback::apply_data( $order, $data );
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				'sync_on_thankyou: ' . $e->getMessage(),
				[ 'source' => 'morkva-novapay' ]
			);
		}
	}

	public function init_form_fields() {
		$this->form_fields = [
			'enabled' => [
				'title'   => __( 'Enable / Disable', 'morkva-novapay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable NovaPay', 'morkva-novapay' ),
				'default' => 'no',
			],
			'title' => [
				'title'       => __( 'Title', 'morkva-novapay' ),
				'type'        => 'text',
				'description' => __( 'Title shown to the customer at checkout.', 'morkva-novapay' ),
				'default'     => __( 'NovaPay', 'morkva-novapay' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'morkva-novapay' ),
				'type'        => 'textarea',
				'description' => __( 'Description shown to the customer at checkout.', 'morkva-novapay' ),
				'default'     => __( 'Pay securely with NovaPay. You will be redirected to NovaPay to complete the payment.', 'morkva-novapay' ),
			],
			'sandbox' => [
				'title'       => __( 'Test mode', 'morkva-novapay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable sandbox (test) environment', 'morkva-novapay' ),
				'description' => __( 'When enabled, requests are sent to https://api-qecom.novapay.ua. When disabled — to the production host https://api-ecom.novapay.ua.', 'morkva-novapay' ),
				'default'     => 'no',
			],
			'merchant_id' => [
				'title'       => __( 'Merchant ID', 'morkva-novapay' ),
				'type'        => 'text',
				'description' => __( 'Test environment uses merchant_id = 2.', 'morkva-novapay' ),
				'default'     => '',
			],
			'private_key' => [
				'title'       => __( 'Merchant private key (PEM)', 'morkva-novapay' ),
				'type'        => 'textarea',
				'description' => __( 'RSA private key issued for your merchant account. Used to sign API requests (header x-sign).', 'morkva-novapay' ),
				'css'         => 'min-height:160px;font-family:monospace;',
				'default'     => '',
			],
			'novapay_public_key' => [
				'title'       => __( 'NovaPay public key (PEM)', 'morkva-novapay' ),
				'type'        => 'textarea',
				'description' => __( 'NovaPay public key, used to verify postback signatures.', 'morkva-novapay' ),
				'css'         => 'min-height:160px;font-family:monospace;',
				'default'     => '',
			],
			'redirect_timeout' => [
				'title'             => __( 'Auto-redirect delay (seconds)', 'morkva-novapay' ),
				'type'              => 'number',
				'description'       => __( 'How long the customer waits on the NovaPay confirmation page before being redirected back to the order-received page. 0 = instant. Lower values give better analytics attribution (purchase event fires sooner).', 'morkva-novapay' ),
				'default'           => '3',
				'custom_attributes' => [ 'min' => '0', 'max' => '60', 'step' => '1' ],
			],
			'webhook_url' => [
				'title'       => __( 'Postback URL', 'morkva-novapay' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s — postback endpoint URL */
					__( 'Set this URL in your NovaPay merchant cabinet as the callback endpoint: <code>%s</code>', 'morkva-novapay' ),
					esc_url( Mrkv_NP_Postback::endpoint_url() )
				),
			],
			'debug' => [
				'title'   => __( 'Debug log', 'morkva-novapay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Log API requests and postbacks to WooCommerce → Status → Logs (source: morkva-novapay).', 'morkva-novapay' ),
				'default' => 'no',
			],
		];
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'morkva-novapay' ), 'error' );
			return [ 'result' => 'failure' ];
		}

		$raw_phone = (string) $order->get_billing_phone();
		$phone     = $this->normalize_phone( $raw_phone );

		/**
		 * Filter the customer phone passed to NovaPay as `client_phone`.
		 *
		 * Use this to override the default normalization (e.g. if the checkout
		 * uses a custom phone mask, or to support non-Ukrainian numbers).
		 * Return an empty string to make the gateway reject the order with the
		 * standard "invalid phone" notice.
		 *
		 * @param string   $phone     Normalized phone (E.164, e.g. +380XXXXXXXXX) or '' if input was unrecognized.
		 * @param string   $raw_phone Raw billing phone as entered by the customer.
		 * @param WC_Order $order     The order being processed.
		 */
		$phone = (string) apply_filters( 'mrkv_novapay_client_phone', $phone, $raw_phone, $order );

		if ( '' === $phone ) {
			wc_add_notice(
				__( 'NovaPay requires a valid Ukrainian mobile phone (e.g. +380XXXXXXXXX). Please update your contact phone and try again.', 'morkva-novapay' ),
				'error'
			);
			return [ 'result' => 'failure' ];
		}

		try {
			$client = new Mrkv_NP_Client( $this->settings );

			$session = $client->create_session( [
				'client_phone'             => $phone,
				'client_first_name'        => $order->get_billing_first_name(),
				'client_last_name'         => $order->get_billing_last_name(),
				'client_email'             => $order->get_billing_email(),
				'callback_url'             => Mrkv_NP_Postback::endpoint_url(),
				'success_url'              => $this->get_return_url( $order ),
				'fail_url'                 => wc_get_checkout_url(),
				'success_redirect_timeout' => max( 0, (int) $this->get_option( 'redirect_timeout', '3' ) ),
				'metadata'                 => [
					'order_id'  => $order->get_id(),
					'order_key' => $order->get_order_key(),
				],
			] );

			$session_id = (string) ( $session['id'] ?? '' );
			if ( '' === $session_id ) {
				throw new RuntimeException( 'NovaPay did not return a session id' );
			}

			$order->update_meta_data( Mrkv_NP_Postback::META_SESSION_ID, $session_id );
			$order->save();

			$payment = $client->add_payment( [
				'session_id'  => $session_id,
				'amount'      => (float) $order->get_total(),
				'external_id' => (string) $order->get_id(),
				'use_hold'    => false,
				'products'    => $this->build_products( $order ),
			] );

			$redirect = (string) ( $payment['url'] ?? $session['url'] ?? '' );
			if ( '' === $redirect ) {
				throw new RuntimeException( 'NovaPay did not return a payment URL' );
			}

			return [
				'result'   => 'success',
				'redirect' => $redirect,
			];

		} catch ( Throwable $e ) {
			$message = $e->getMessage();
			wc_get_logger()->error( "process_payment: {$message}", [ 'source' => 'morkva-novapay' ] );
			wc_add_notice(
				sprintf(
					/* translators: %s: error message returned by NovaPay API */
					__( 'NovaPay payment error: %s', 'morkva-novapay' ),
					$message
				),
				'error'
			);
			return [ 'result' => 'failure' ];
		}
	}

	private function build_products( WC_Order $order ): array {
		$items = [];
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$qty   = max( 1, (int) $item->get_quantity() );
			$total = (float) $order->get_line_total( $item, true, false );
			$items[] = [
				'description' => $item->get_name(),
				'count'       => $qty,
				'price'       => round( $total / $qty, 2 ),
			];
		}

		$shipping = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
		if ( $shipping > 0 ) {
			$items[] = [
				'description' => __( 'Shipping', 'morkva-novapay' ),
				'count'       => 1,
				'price'       => round( $shipping, 2 ),
			];
		}

		return $items;
	}

	private function normalize_phone( string $phone ): string {
		$digits = preg_replace( '/\D+/', '', $phone );
		$len    = strlen( $digits );

		if ( 12 === $len && 0 === strpos( $digits, '380' ) ) {
			return '+' . $digits;
		}
		if ( 11 === $len && 0 === strpos( $digits, '80' ) ) {
			return '+3' . $digits;
		}
		if ( 10 === $len && 0 === strpos( $digits, '0' ) ) {
			return '+38' . $digits;
		}
		if ( 9 === $len ) {
			return '+380' . $digits;
		}
		return '';
	}
}
