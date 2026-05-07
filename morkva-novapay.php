<?php
/**
 * Plugin Name: morkva NovaPay
 * Description: NovaPay payment gateway for WooCommerce.
 * Version: 0.2.0
 * Author: morkva
 * Author URI: https://morkva.co.ua
 * Text Domain: morkva-novapay
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MRKV_NOVAPAY_FILE', __FILE__ );
define( 'MRKV_NOVAPAY_VERSION', '0.2.0' );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'plugins_loaded', 'mrkv_novapay_init' );

function mrkv_novapay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	$base = plugin_dir_path( __FILE__ );

	require_once $base . 'includes/api/class-mrkv-np-signer.php';
	require_once $base . 'includes/api/class-mrkv-np-client.php';
	require_once $base . 'includes/api/class-mrkv-np-postback.php';
	require_once $base . 'includes/class-morkva-novapay-gateway.php';
	require_once $base . 'includes/class-morkva-novapay-blocks-support.php';

	Mrkv_NP_Postback::register();

	if ( is_admin() ) {
		require_once $base . 'includes/admin/class-mrkv-np-order-meta-box.php';
		Mrkv_NP_Order_Meta_Box::register();
	}

	add_filter( 'woocommerce_payment_gateways', function ( $methods ) {
		$methods[] = 'Mrkv_NovaPay_Gateway';
		return $methods;
	} );
}
