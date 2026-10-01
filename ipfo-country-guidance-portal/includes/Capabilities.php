<?php
declare( strict_types = 1 );

namespace IPFO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom roles and capabilities used throughout the portal.
 */
final class Capabilities {

	public const MANAGE_PORTAL  = 'manage_ipfo_portal';
	public const ACCESS_PORTAL  = 'ipfo_access_portal';
	public const CLIENT_ROLE    = 'ipfo_client';

	public static function install(): void {
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::MANAGE_PORTAL ) ) {
			$admin->add_cap( self::MANAGE_PORTAL );
		}

		if ( ! get_role( self::CLIENT_ROLE ) ) {
			add_role(
				self::CLIENT_ROLE,
				__( 'Intended Parent (IPFO Client)', 'ipfo-country-guidance-portal' ),
				[
					'read'                 => true,
					self::ACCESS_PORTAL    => true,
				]
			);
		}
	}

	public static function current_user_can_manage(): bool {
		return current_user_can( self::MANAGE_PORTAL );
	}

	public static function current_user_can_access_portal(): bool {
		return is_user_logged_in() && current_user_can( self::ACCESS_PORTAL ) || self::current_user_can_manage();
	}
}
