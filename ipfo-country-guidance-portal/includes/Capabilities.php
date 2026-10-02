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
	 * Gates every portal-management screen/CPT. Uses WordPress's own
	 * built-in `manage_options` rather than a custom capability string, so
	 * every Administrator has it with zero setup and no dependency on our
	 * own role-sync code ever having run. (The admin menu being
	 * unreachable despite a correctly-granted custom capability was
	 * ultimately traced to the CPT capability arrays, not this constant —
	 * see the comment on Capabilities::cpt_capabilities() — but
	 * manage_options remains the simpler, more conventional choice.)
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

	/**
	 * Primitive-only capability map for a map_meta_cap => true custom post
	 * type. Deliberately never sets 'edit_post', 'read_post' or
	 * 'delete_post' (the meta caps): doing so makes WordPress treat our own
	 * capability string as if it were the meta-cap's registered name
	 * rather than its resolved value. Core code that probes a meta cap
	 * generically — with no specific post ID — then trips
	 * map_meta_cap()'s own "you must always check it against a specific
	 * post" _doing_it_wrong() guard on every single admin page load
	 * (confirmed via wp-content/debug.log in local testing: hundreds of
	 * these per page load, present only while this plugin was active).
	 * The practical, reproducible symptom was worse than a stray notice:
	 * the "IPFO Portal" admin menu never appeared and visiting it
	 * directly gave "Sorry, you are not allowed to access this page.",
	 * even though current_user_can() on the gating capability reported
	 * true everywhere else. Supplying only primitive caps lets
	 * WordPress's own map_meta_cap() implementation derive the meta caps
	 * correctly, which is what the register_post_type() documentation
	 * itself recommends.
	 */
	public static function cpt_capabilities(): array {
		$cap = self::MANAGE_PORTAL;

		return [
			'edit_posts'             => $cap,
			'edit_others_posts'      => $cap,
			'edit_private_posts'     => $cap,
			'edit_published_posts'   => $cap,
			'publish_posts'          => $cap,
			'read_private_posts'     => $cap,
			'delete_posts'           => $cap,
			'delete_others_posts'    => $cap,
			'delete_private_posts'   => $cap,
			'delete_published_posts' => $cap,
			'create_posts'           => $cap,
		];
	}
}
