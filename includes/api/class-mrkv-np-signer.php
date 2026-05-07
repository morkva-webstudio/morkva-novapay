<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mrkv_NP_Signer {

	public static function sign( string $body, string $private_key_pem ): string {
		$key = openssl_pkey_get_private( $private_key_pem );
		if ( ! $key ) {
			throw new RuntimeException( 'Invalid merchant private key' );
		}
		$signature = '';
		$ok = openssl_sign( $body, $signature, $key, OPENSSL_ALGO_SHA256 );
		if ( ! $ok ) {
			throw new RuntimeException( 'Failed to sign request body' );
		}
		return base64_encode( $signature );
	}

	public static function verify( string $body, string $signature_b64, string $public_key_pem, int $algo = OPENSSL_ALGO_SHA256 ): bool {
		if ( '' === $body || '' === $signature_b64 || '' === $public_key_pem ) {
			return false;
		}
		$key = openssl_pkey_get_public( $public_key_pem );
		if ( ! $key ) {
			return false;
		}
		$signature = base64_decode( $signature_b64, true );
		if ( false === $signature ) {
			return false;
		}
		return 1 === openssl_verify( $body, $signature, $key, $algo );
	}
}
