<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Mrkv_NovaPay_Blocks_Support extends AbstractPaymentMethodType {

	protected $name = 'mrkv_novapay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_mrkv_novapay_settings', [] );
	}

	public function is_active() {
		return 'yes' === ( $this->settings['enabled'] ?? 'no' );
	}

	public function get_payment_method_script_handles() {
		$handle = 'mrkv-novapay-blocks';
		$src    = plugins_url( 'assets/js/blocks/novapay-block.js', MRKV_NOVAPAY_FILE );
		$path   = plugin_dir_path( MRKV_NOVAPAY_FILE ) . 'assets/js/blocks/novapay-block.js';

		wp_register_script(
			$handle,
			$src,
			[ 'wc-blocks-registry', 'wp-element', 'wp-html-entities' ],
			file_exists( $path ) ? (string) filemtime( $path ) : MRKV_NOVAPAY_VERSION,
			true
		);

		return [ $handle ];
	}

	public function get_payment_method_data() {
		return [
			'title'       => $this->settings['title'] ?? 'NovaPay',
			'description' => $this->settings['description'] ?? '',
			'supports'    => [ 'products' ],
		];
	}
}

add_action( 'woocommerce_blocks_payment_method_type_registration', function( $registry ) {
	$registry->register( new Mrkv_NovaPay_Blocks_Support() );
} );
