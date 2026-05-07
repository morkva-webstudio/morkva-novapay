<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mrkv_NP_Order_Meta_Box {

	public static function register(): void {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add' ], 30, 2 );
	}

	public static function add( $screen_id, $post_or_order ): void {
		$screens = self::screens();
		if ( ! in_array( $screen_id, $screens, true ) ) {
			return;
		}

		$order = self::resolve_order( $post_or_order );
		if ( ! $order ) {
			return;
		}

		if ( 'mrkv_novapay' !== $order->get_payment_method() ) {
			return;
		}

		add_meta_box(
			'mrkv_novapay_order_meta',
			__( 'morkva NovaPay', 'morkva-novapay' ),
			[ __CLASS__, 'render' ],
			$screen_id,
			'side',
			'default'
		);
	}

	public static function render( $post_or_order ): void {
		$order = self::resolve_order( $post_or_order );
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Order not found.', 'morkva-novapay' ) . '</p>';
			return;
		}

		$rows = [
			__( 'Session ID', 'morkva-novapay' )         => $order->get_meta( Mrkv_NP_Postback::META_SESSION_ID ),
			__( 'Status', 'morkva-novapay' )             => $order->get_meta( Mrkv_NP_Postback::META_LAST_STATUS ),
			__( 'Payment type', 'morkva-novapay' )       => $order->get_meta( Mrkv_NP_Postback::META_PAYTYPE ),
			__( 'Terminal', 'morkva-novapay' )           => $order->get_meta( Mrkv_NP_Postback::META_TERMINAL_NAME ),
			__( 'Processing result', 'morkva-novapay' )  => $order->get_meta( Mrkv_NP_Postback::META_PROCESSING_RESULT ),
			__( 'RRN', 'morkva-novapay' )                => $order->get_meta( Mrkv_NP_Postback::META_RRN ),
			__( 'Approval', 'morkva-novapay' )           => $order->get_meta( Mrkv_NP_Postback::META_APPROVAL ),
		];

		$card_rows = [
			__( 'Card', 'morkva-novapay' )         => $order->get_meta( Mrkv_NP_Postback::META_CARD_PAN ),
			__( 'Card type', 'morkva-novapay' )    => $order->get_meta( Mrkv_NP_Postback::META_CARD_TYPE ),
			__( 'Issuer bank', 'morkva-novapay' )  => $order->get_meta( Mrkv_NP_Postback::META_CARD_BANK ),
			__( 'Card country', 'morkva-novapay' ) => $order->get_meta( Mrkv_NP_Postback::META_CARD_COUNTRY ),
		];

		$has_any = (bool) array_filter( array_merge( $rows, $card_rows ), 'strlen' );
		if ( ! $has_any ) {
			echo '<p>' . esc_html__( 'No NovaPay data yet. Waiting for postback.', 'morkva-novapay' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped" style="margin:0">';
		self::render_rows( $rows );

		$has_card = (bool) array_filter( $card_rows, 'strlen' );
		if ( $has_card ) {
			echo '<tr><td colspan="2" style="padding-top:10px"><strong>' . esc_html__( 'Card details', 'morkva-novapay' ) . '</strong></td></tr>';
			self::render_rows( $card_rows );
		}
		echo '</table>';
	}

	private static function render_rows( array $rows ): void {
		foreach ( $rows as $label => $value ) {
			$value = (string) $value;
			if ( '' === $value ) {
				continue;
			}
			printf(
				'<tr><td style="width:45%%"><strong>%s</strong></td><td>%s</td></tr>',
				esc_html( $label ),
				esc_html( $value )
			);
		}
	}

	private static function screens(): array {
		$screens = [ 'shop_order' ];
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}
		return array_values( array_unique( array_filter( $screens ) ) );
	}

	private static function resolve_order( $post_or_order ): ?WC_Order {
		if ( $post_or_order instanceof WC_Order ) {
			return $post_or_order;
		}
		if ( $post_or_order instanceof WP_Post ) {
			return wc_get_order( $post_or_order->ID );
		}
		if ( is_numeric( $post_or_order ) ) {
			$order = wc_get_order( (int) $post_or_order );
			return $order instanceof WC_Order ? $order : null;
		}
		return null;
	}
}
