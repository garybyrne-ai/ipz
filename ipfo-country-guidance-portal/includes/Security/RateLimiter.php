<?php
declare( strict_types = 1 );

namespace IPFO\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple transient-backed rate limiter for sensitive public actions
 * (registration, invitation redemption, login attempts via the portal
 * forms). Not a substitute for a WAF, but stops casual brute-forcing.
 */
final class RateLimiter {

	public static function identifier(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return hash( 'sha256', $ip . wp_salt( 'auth' ) );
	}

	/**
	 * @return bool True if the action is still allowed; false if the limit was exceeded.
	 */
	public static function allow( string $action, int $max_attempts = 5, int $window_seconds = 900 ): bool {
		$key   = 'ipfo_rl_' . $action . '_' . self::identifier();
		$count = (int) get_transient( $key );

		if ( $count >= $max_attempts ) {
			return false;
		}

		if ( 0 === $count ) {
			set_transient( $key, 1, $window_seconds );
		} else {
			set_transient( $key, $count + 1, $window_seconds );
		}

		return true;
	}

	public static function reset( string $action ): void {
		delete_transient( 'ipfo_rl_' . $action . '_' . self::identifier() );
	}
}
