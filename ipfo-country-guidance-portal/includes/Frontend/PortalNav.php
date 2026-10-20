<?php
declare( strict_types = 1 );

namespace IPFO\Frontend;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The persistent sidebar shown on every portal page once a user is logged
 * in — its contents depend entirely on which of the two account types is
 * signed in, which is the whole point: a Business Directory owner should
 * never see Intended-Parent navigation (My Guides, Checklist…) and vice
 * versa. Renders nothing at all for a logged-out visitor.
 */
final class PortalNav {

	public static function render( string $active = '' ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user        = wp_get_current_user();
		$is_business = in_array( Capabilities::BUSINESS_ROLE, (array) $user->roles, true );

		return ipfo_render_template(
			'partials/portal-nav.php',
			[
				'items'       => $is_business ? self::business_items() : self::client_items(),
				'active'      => $active,
				'user'        => $user,
				'is_business' => $is_business,
				'logout_url'  => wp_logout_url( ipfo_get_portal_page_url( 'login' ) ),
			]
		);
	}

	/** @return array<int,array{key:string,label:string,icon:string,url:string}> */
	private static function client_items(): array {
		return [
			[ 'key' => 'dashboard', 'label' => __( 'Dashboard', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-grid-view', 'url' => ipfo_get_portal_page_url( 'dashboard' ) ],
			[ 'key' => 'my-guides', 'label' => __( 'My Guides', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-book-alt', 'url' => ipfo_get_portal_page_url( 'my-guides' ) ],
			[ 'key' => 'country-guides', 'label' => __( 'Country Guides', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-location-alt', 'url' => ipfo_get_portal_page_url( 'country-guides' ) ],
			[ 'key' => 'resources', 'label' => __( 'Resources', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-media-document', 'url' => ipfo_get_portal_page_url( 'resources' ) ],
			[ 'key' => 'checklist', 'label' => __( 'Checklist', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-yes-alt', 'url' => ipfo_get_portal_page_url( 'checklist' ) ],
			[ 'key' => 'directory', 'label' => __( 'Business Directory', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-search', 'url' => ipfo_get_portal_page_url( 'directory' ) ],
			[ 'key' => 'profile', 'label' => __( 'Profile', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-admin-users', 'url' => ipfo_get_portal_page_url( 'profile' ) ],
			[ 'key' => 'notifications', 'label' => __( 'Notifications', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-bell', 'url' => ipfo_get_portal_page_url( 'notifications' ) ],
		];
	}

	/** @return array<int,array{key:string,label:string,icon:string,url:string}> */
	private static function business_items(): array {
		return [
			[ 'key' => 'business-dashboard', 'label' => __( 'My Listing', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-store', 'url' => ipfo_get_portal_page_url( 'business-dashboard' ) ],
			[ 'key' => 'directory', 'label' => __( 'Public Directory', 'ipfo-country-guidance-portal' ), 'icon' => 'dashicons-search', 'url' => ipfo_get_portal_page_url( 'directory' ) ],
		];
	}
}
