<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mrkv_NP_Postback {

	const META_SESSION_ID       = '_mrkv_np_session_id';
	const META_LAST_STATUS      = '_mrkv_np_last_status';
	const META_PAYTYPE          = '_mrkv_np_paytype';
	const META_RRN              = '_mrkv_np_rrn';
	const META_APPROVAL         = '_mrkv_np_approval';
	const META_TERMINAL_NAME    = '_mrkv_np_terminal_name';
	const META_PROCESSING_RESULT = '_mrkv_np_processing_result';
	const META_CARD_PAN         = '_mrkv_np_card_pan';
	const META_CARD_TYPE        = '_mrkv_np_card_type';
	const META_CARD_BANK        = '_mrkv_np_card_bank';
	const META_CARD_COUNTRY     = '_mrkv_np_card_country';

	public static function register(): void {
		add_action( 'woocommerce_api_mrkv_novapay', [ __CLASS__, 'handle' ] );
	}

	public static function endpoint_url(): string {
		return WC()->api_request_url( 'mrkv_novapay' );
	}

	public static function handle(): void {
		$body      = file_get_contents( 'php://input' );
		$headers   = self::collect_headers();
		$signature = self::extract_signature( $headers );
		$settings  = get_option( 'woocommerce_mrkv_novapay_settings', [] );
		$pubkey    = (string) ( $settings['novapay_public_key'] ?? '' );
		$debug     = ! empty( $settings['debug'] ) && 'yes' === $settings['debug'];

		self::log( "← postback: {$body}", 'info', $debug );
		if ( $debug ) {
			self::log( 'postback headers: ' . wp_json_encode( $headers ), 'info', true );
		}

		if ( '' === $body ) {
			status_header( 400 );
			exit;
		}

		$verified = self::verify_postback( $body, $headers, $pubkey, $debug );
		if ( ! $verified ) {
			self::log(
				'Invalid postback signature (signature header found: '
					. ( '' !== $signature ? 'yes' : 'no' ) . ')',
				'warning',
				true
			);
			status_header( 401 );
			exit;
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			status_header( 400 );
			exit;
		}

		$session_id = (string) ( $data['id'] ?? '' );
		$status     = (string) ( $data['status'] ?? '' );
		if ( '' === $session_id || '' === $status ) {
			status_header( 400 );
			exit;
		}

		$order = self::find_order_by_session( $session_id, $data );
		if ( ! $order ) {
			self::log( "No order found for session {$session_id}", 'warning', true );
			status_header( 200 );
			exit;
		}

		self::apply_data( $order, $data );

		status_header( 200 );
		exit;
	}

	public static function apply_data( WC_Order $order, array $data ): bool {
		$status = (string) ( $data['status'] ?? '' );
		if ( '' === $status ) {
			return false;
		}

		$last = (string) $order->get_meta( self::META_LAST_STATUS );
		if ( $last === $status ) {
			return false;
		}

		self::apply_status( $order, $status, $data );
		$order->update_meta_data( self::META_LAST_STATUS, $status );

		$scalar_map = [
			'paytype'           => self::META_PAYTYPE,
			'RRN'               => self::META_RRN,
			'APPROVAL'          => self::META_APPROVAL,
			'terminal_name'     => self::META_TERMINAL_NAME,
			'processing_result' => self::META_PROCESSING_RESULT,
		];
		foreach ( $scalar_map as $field => $meta_key ) {
			if ( isset( $data[ $field ] ) && '' !== $data[ $field ] && null !== $data[ $field ] ) {
				$order->update_meta_data( $meta_key, (string) $data[ $field ] );
			}
		}

		if ( isset( $data['card_details'] ) && is_array( $data['card_details'] ) ) {
			$card_map = [
				'pan'          => self::META_CARD_PAN,
				'card_type'    => self::META_CARD_TYPE,
				'card_bank'    => self::META_CARD_BANK,
				'card_country' => self::META_CARD_COUNTRY,
			];
			foreach ( $card_map as $field => $meta_key ) {
				$value = $data['card_details'][ $field ] ?? null;
				if ( null !== $value && '' !== $value ) {
					$order->update_meta_data( $meta_key, (string) $value );
				}
			}
		}

		$order->save();
		return true;
	}

	private static function find_order_by_session( string $session_id, array $data ): ?WC_Order {
		$orders = wc_get_orders( [
			'limit'  => 1,
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- session_id lookup is rare and used only on incoming postbacks.
			'meta_key'   => self::META_SESSION_ID,
			'meta_value' => $session_id,
			// phpcs:enable
			'return' => 'objects',
		] );
		if ( ! empty( $orders ) ) {
			return $orders[0];
		}

		$meta_order_id = isset( $data['metadata']['order_id'] ) ? (int) $data['metadata']['order_id'] : 0;
		if ( $meta_order_id > 0 ) {
			$order = wc_get_order( $meta_order_id );
			if ( $order ) {
				return $order;
			}
		}

		$external_id = $data['external_id'] ?? ( $data['payments'][0]['external_id'] ?? '' );
		if ( '' !== (string) $external_id ) {
			$order = wc_get_order( (int) $external_id );
			if ( $order ) {
				return $order;
			}
		}

		return null;
	}

	private static function apply_status( WC_Order $order, string $status, array $data ): void {
		$txn_id = (string) ( $data['external_id'] ?? $data['payments'][0]['external_id'] ?? $data['id'] ?? '' );

		switch ( $status ) {
			case 'paid':
				$order->payment_complete( $txn_id );
				$order->add_order_note( sprintf(
					/* translators: %s: payment method (card, wallet, apple_pay, google_pay) */
					__( 'NovaPay: payment completed (%s).', 'morkva-novapay' ),
					(string) ( $data['paytype'] ?? '—' )
				) );
				break;

			case 'holded':
				$order->update_status( 'on-hold', __( 'NovaPay: funds held, awaiting capture.', 'morkva-novapay' ) );
				break;

			case 'failed':
				$order->update_status( 'failed', __( 'NovaPay: payment failed.', 'morkva-novapay' ) );
				break;

			case 'voided':
				$order->update_status( 'cancelled', __( 'NovaPay: session voided.', 'morkva-novapay' ) );
				break;

			case 'expired':
				$order->update_status( 'cancelled', __( 'NovaPay: session expired.', 'morkva-novapay' ) );
				break;

			default:
				$order->add_order_note( sprintf(
					/* translators: %s: NovaPay session status code */
					__( 'NovaPay status update: %s', 'morkva-novapay' ),
					$status
				) );
		}
	}

	private static function collect_headers(): array {
		if ( function_exists( 'getallheaders' ) ) {
			$raw = getallheaders();
			if ( is_array( $raw ) ) {
				$out = [];
				foreach ( $raw as $name => $value ) {
					$out[ strtolower( (string) $name ) ] = sanitize_text_field( wp_unslash( (string) $value ) );
				}
				return $out;
			}
		}

		$out = [];
		foreach ( $_SERVER as $key => $value ) {
			if ( ! is_string( $key ) || 0 !== strpos( $key, 'HTTP_' ) ) {
				continue;
			}
			$name = strtolower( str_replace( '_', '-', substr( $key, 5 ) ) );
			$out[ $name ] = sanitize_text_field( wp_unslash( (string) $value ) );
		}
		return $out;
	}

	private static function extract_signature( array $headers ): string {
		$candidates = [ 'x-sign-v2', 'x-sign', 'x-signature', 'signature', 'x-novapay-sign', 'x-novapay-signature', 'x-np-sign' ];
		foreach ( $candidates as $name ) {
			if ( isset( $headers[ $name ] ) && '' !== $headers[ $name ] ) {
				return (string) $headers[ $name ];
			}
		}
		return '';
	}

	private static function verify_postback( string $body, array $headers, string $pubkey, bool $debug ): bool {
		if ( '' === $pubkey || '' === $body ) {
			return false;
		}

		// NovaPay current scheme: x-sign-v2 with RSA-SHA256 over raw body.
		if ( ! empty( $headers['x-sign-v2'] )
			&& Mrkv_NP_Signer::verify( $body, (string) $headers['x-sign-v2'], $pubkey ) ) {
			self::log( 'postback verified: x-sign-v2', 'info', true );
			return true;
		}

		// Legacy fallback header (still sent by NovaPay alongside x-sign-v2).
		if ( ! empty( $headers['x-sign'] )
			&& Mrkv_NP_Signer::verify( $body, (string) $headers['x-sign'], $pubkey ) ) {
			self::log( 'postback verified: x-sign (legacy)', 'info', true );
			return true;
		}

		if ( $debug ) {
			self::probe_log( $body, $headers, $pubkey );
		}
		return false;
	}

	private static function probe_log( string $body, array $headers, string $pubkey ): void {
		$header_names = [ 'x-sign-v2', 'x-sign', 'x-signature', 'signature' ];
		$algos        = [
			'sha256' => OPENSSL_ALGO_SHA256,
			'sha512' => OPENSSL_ALGO_SHA512,
			'sha384' => OPENSSL_ALGO_SHA384,
			'sha1'   => OPENSSL_ALGO_SHA1,
		];
		$results = [];
		foreach ( $header_names as $h ) {
			if ( empty( $headers[ $h ] ) ) {
				continue;
			}
			foreach ( $algos as $name => $algo ) {
				$ok = Mrkv_NP_Signer::verify( $body, (string) $headers[ $h ], $pubkey, $algo );
				$results[] = "{$h}/{$name}=" . ( $ok ? 'OK' : 'fail' );
			}
		}
		self::log( 'verify probe: ' . implode( ', ', $results ), 'info', true );
	}

	private static function log( string $message, string $level, bool $force ): void {
		if ( ! $force && 'info' === $level ) {
			return;
		}
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->log( $level, $message, [ 'source' => 'morkva-novapay' ] );
	}
}
