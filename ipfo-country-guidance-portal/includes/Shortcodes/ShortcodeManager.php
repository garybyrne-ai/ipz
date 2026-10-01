<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShortcodeManager {

	public const TAGS = [
		'IPFO_LOGIN'          => LoginShortcode::class,
		'IPFO_REGISTER'       => RegisterShortcode::class,
		'IPFO_DASHBOARD'      => DashboardShortcode::class,
		'IPFO_MY_GUIDES'      => MyGuidesShortcode::class,
		'IPFO_GUIDE'          => GuideShortcode::class,
		'IPFO_COUNTRY_GUIDES' => CountryGuidesShortcode::class,
		'IPFO_RESOURCES'      => ResourcesShortcode::class,
		'IPFO_CHECKLIST'      => ChecklistShortcode::class,
		'IPFO_PROFILE'        => ProfileShortcode::class,
		'IPFO_NOTIFICATIONS'  => NotificationsShortcode::class,
		'IPFO_LOGOUT'         => LogoutShortcode::class,
	];

	public function register(): void {
		foreach ( self::TAGS as $tag => $class ) {
			add_shortcode( $tag, [ new $class(), 'render' ] );
		}
	}
}
