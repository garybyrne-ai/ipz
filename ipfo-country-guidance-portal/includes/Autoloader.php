<?php
declare( strict_types = 1 );

namespace IPFO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal PSR-4-style autoloader for the IPFO\ namespace, mapped onto /includes.
 * No Composer dependency is required to install the plugin.
 */
final class Autoloader {

	private const PREFIX   = __NAMESPACE__ . '\\';
	private const BASE_DIR = __DIR__;

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );
	}

	public static function load( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$path     = self::BASE_DIR . DIRECTORY_SEPARATOR . $relative . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
