<?php
declare( strict_types = 1 );

namespace IPFO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation never deletes data. Tables, options, roles and uploaded
 * content are preserved so the portal can be safely re-enabled later.
 */
final class Deactivator {

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'ipfo_daily_maintenance' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ipfo_daily_maintenance' );
		}

		flush_rewrite_rules();
	}
}
