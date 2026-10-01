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

	/**
	 * Gates every portal-management screen/CPT. Deliberately WordPress's own
	 * built-in `manage_options` rather than a custom capability string: some
	 * security-hardening plugins (confirmed on ipfertilityoptions.com, likely
	 * Sucuri's hardening) hook the `user_has_cap` filter and silently strip
	 * any capability they don't recognise from current_user_can() checks —
	 * even though it's correctly present in the role's raw allcaps. A custom
	 * capability would then only ever evaluate to true in raw capability
	 * dumps (e.g. the REST API's /users/me response) but false everywhere
	 * current_user_can() is actually used to gate access, including
	 * add_menu_page()/add_submenu_page() themselves. manage_options is a
	 * core capability no hardening plugin can safely filter out without
	 * breaking wp-admin itself, so it is immune to this class of conflict.
	 */
	public const MANAGE_PORTAL  = 'manage_options';

	/**
	 * Also granted to administrators for forward-compatibility (e.g. a site
	 * owner later wanting a non-administrator "portal manager" role), but
	 * intentionally not used as the gate anywhere above, for the reason
	 * documented on MANAGE_PORTAL.
	 */
	public const MANAGE_PORTAL_GRANULAR = 'manage_ipfo_portal';

	public const ACCESS_PORTAL  = 'ipfo_access_portal';
	public const CLIENT_ROLE    = 'ipfo_client';

	public static function install(): void {
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( self::MANAGE_PORTAL_GRANULAR ) ) {
			$admin->add_cap( self::MANAGE_PORTAL_GRANULAR );
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
