<?php
declare( strict_types = 1 );

namespace IPFO\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Secure token and invitation-code generation. Uses the CSPRNG
 * (random_bytes/wp_generate_password with extra entropy) and stores only
 * hashes of secret tokens in the database, never the plaintext token.
 */
final class Tokens {

	/**
	 * Human-shareable invitation code, e.g. IPFO-IRE-82K9X.
	 */
	public static function invitation_code( string $country_code = '' ): string {
		$country_code = $country_code ? strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', $country_code ), 0, 3 ) ) : 'GEN';
		$country_code = str_pad( $country_code, 3, 'X' );

		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous chars (0/O, 1/I/L)
		$random   = '';
		$bytes    = random_bytes( 5 );

		for ( $i = 0; $i < 5; $i++ ) {
			$random .= $alphabet[ ord( $bytes[ $i ] ) % strlen( $alphabet ) ];
		}

		return sprintf( 'IPFO-%s-%s', $country_code, $random );
	}

	/**
	 * A high-entropy secret token for invitation links. Only its hash is
	 * ever persisted; the plaintext is embedded in the one-time link sent
	 * to the Intended Parent.
	 */
	public static function secret_token(): string {
		return bin2hex( random_bytes( 32 ) );
	}

	public static function hash( string $token ): string {
		return hash( 'sha256', $token );
	}

	public static function hash_equals_constant_time( string $known_hash, string $candidate_token ): bool {
		return hash_equals( $known_hash, self::hash( $candidate_token ) );
	}
}
