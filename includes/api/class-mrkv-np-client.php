<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mrkv_NP_Client {

	const BASE_URL_SANDBOX    = 'https://api-qecom.novapay.ua';
	const BASE_URL_PRODUCTION = 'https://api-ecom.novapay.ua';

	private string $merchant_id;
	private string $private_key;
	private string $base_url;
	private bool   $debug;
	private bool   $sandbox;

	public function __construct( array $settings ) {
		$this->merchant_id = (string) ( $settings['merchant_id'] ?? '' );
		$this->private_key = (string) ( $settings['private_key'] ?? '' );
		$this->sandbox     = 'yes' === ( $settings['sandbox'] ?? 'no' );
		$this->debug       = ! empty( $settings['debug'] ) && 'yes' === $settings['debug'];

		$default_url = $this->sandbox ? self::BASE_URL_SANDBOX : self::BASE_URL_PRODUCTION;

		/**
		 * Filter the NovaPay API base URL.
		 *
		 * Use this if NovaPay assigned your merchant a non-standard production
		 * host, or to point the client at a staging environment.
		 *
		 * @param string $url      Default base URL (no trailing slash).
		 * @param bool   $sandbox  Whether the gateway is in sandbox mode.
		 * @param array  $settings Gateway settings array.
		 */
		$this->base_url = (string) apply_filters( 'mrkv_novapay_api_base_url', $default_url, $this->sandbox, $settings );
	}

	public function create_session( array $data ): array {
		return $this->post( '/v1/session', $this->with_merchant( $data ) );
	}

	public function add_payment( array $data ): array {
		return $this->post( '/v1/payment', $this->with_merchant( $data ) );
	}

	public function void_session( string $session_id ): array {
		return $this->post( '/v1/void-session', $this->with_merchant( [ 'id' => $session_id ] ) );
	}

	public function complete_hold( string $session_id, ?float $amount = null ): array {
		$payload = [ 'id' => $session_id ];
		if ( null !== $amount ) {
			$payload['amount'] = $amount;
		}
		return $this->post( '/v1/complete-hold', $this->with_merchant( $payload ) );
	}

	public function expire_session( string $session_id ): array {
		return $this->post( '/v1/expire-session', $this->with_merchant( [ 'id' => $session_id ] ) );
	}

	public function get_status( string $session_id ): array {
		return $this->post( '/v1/get-status', $this->with_merchant( [ 'id' => $session_id ] ) );
	}

	public function print_express_waybill( string $session_id ): array {
		return $this->post( '/v1/print-express-waybill', $this->with_merchant( [ 'id' => $session_id ] ) );
	}

	private function with_merchant( array $data ): array {
		$data['merchant_id'] = $this->merchant_id;
		return $data;
	}

	private function post( string $path, array $payload ): array {
		if ( '' === $this->merchant_id || '' === $this->private_key ) {
			throw new RuntimeException( 'NovaPay credentials are not configured' );
		}

		$body = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
		if ( false === $body ) {
			throw new RuntimeException( 'Failed to encode request payload' );
		}

		$signature = Mrkv_NP_Signer::sign( $body, $this->private_key );
		$url       = $this->base_url . $path;

		$this->log( "→ POST {$path}: {$body}" );

		$response = wp_remote_post( $url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-sign'       => $signature,
			],
			'body'    => $body,
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			$this->log( "✗ {$path}: {$message}", 'error' );
			throw new RuntimeException( esc_html( $message ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		$this->log( "← {$code} {$path}: {$raw}" );

		if ( $code >= 400 ) {
			throw new RuntimeException( esc_html( $this->extract_error_message( $data, $raw, $code ) ) );
		}

		return is_array( $data ) ? $data : [];
	}

	private function extract_error_message( $data, string $raw, int $code ): string {
		if ( is_array( $data ) ) {
			if ( ! empty( $data['errors'] ) && is_array( $data['errors'] ) ) {
				$parts = [];
				foreach ( $data['errors'] as $err ) {
					if ( ! is_array( $err ) ) {
						continue;
					}
					$msg  = (string) ( $err['message'] ?? '' );
					$path = (string) ( $err['path'] ?? '' );
					if ( '' === $msg ) {
						continue;
					}
					$parts[] = '' !== $path ? "{$msg} ({$path})" : $msg;
				}
				if ( $parts ) {
					return implode( '; ', $parts );
				}
			}
			if ( ! empty( $data['message'] ) ) {
				return (string) $data['message'];
			}
		}
		return '' !== $raw ? $raw : "HTTP {$code}";
	}

	private function log( string $message, string $level = 'info' ): void {
		if ( ! $this->debug && 'error' !== $level ) {
			return;
		}
		$logger = function_exists( 'wc_get_logger' ) ? wc_get_logger() : null;
		if ( ! $logger ) {
			return;
		}
		$logger->log( $level, $message, [ 'source' => 'morkva-novapay' ] );
	}
}
