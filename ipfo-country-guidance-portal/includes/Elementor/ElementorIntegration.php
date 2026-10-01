<?php
declare( strict_types = 1 );

namespace IPFO\Elementor;

use IPFO\Elementor\Widgets\ShortcodeWidget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a dedicated "IPFO Portal" Elementor widget category and one
 * widget per [IPFO_*] shortcode, so editors can drag these onto any
 * Elementor-built page without touching shortcode syntax. Does nothing
 * at all if Elementor is not active, and never alters Elementor's own
 * widgets, categories, or global settings.
 */
final class ElementorIntegration {

	/** @var array<int,array{0:string,1:string,2:string,3:string,4:bool,5:bool}> slug, tag, title, icon, guide_control, label_control */
	private const WIDGETS = [
		[ 'ipfo-login', 'IPFO_LOGIN', 'IPFO Login', 'eicon-lock-user', false, false ],
		[ 'ipfo-register', 'IPFO_REGISTER', 'IPFO Register', 'eicon-form-horizontal', false, false ],
		[ 'ipfo-dashboard', 'IPFO_DASHBOARD', 'IPFO Dashboard', 'eicon-dashboard', false, false ],
		[ 'ipfo-my-guides', 'IPFO_MY_GUIDES', 'IPFO My Guides', 'eicon-library-open', false, false ],
		[ 'ipfo-guide', 'IPFO_GUIDE', 'IPFO Guide Reader', 'eicon-book', true, false ],
		[ 'ipfo-country-guides', 'IPFO_COUNTRY_GUIDES', 'IPFO Country Guides', 'eicon-google-maps', false, false ],
		[ 'ipfo-resources', 'IPFO_RESOURCES', 'IPFO Resources', 'eicon-files', false, false ],
		[ 'ipfo-checklist', 'IPFO_CHECKLIST', 'IPFO Checklist', 'eicon-checkbox', false, false ],
		[ 'ipfo-profile', 'IPFO_PROFILE', 'IPFO Profile', 'eicon-person', false, false ],
		[ 'ipfo-notifications', 'IPFO_NOTIFICATIONS', 'IPFO Notifications', 'eicon-alert', false, false ],
		[ 'ipfo-logout', 'IPFO_LOGOUT', 'IPFO Logout', 'eicon-exit', false, true ],
	];

	public function register(): void {
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
	}

	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			'ipfo-portal',
			[
				'title' => __( 'IPFO Portal', 'ipfo-country-guidance-portal' ),
				'icon'  => 'eicon-lock-user',
			]
		);
	}

	public function register_widgets( $widgets_manager ): void {
		foreach ( self::WIDGETS as [ $slug, $tag, $title, $icon, $guide_control, $label_control ] ) {
			$widgets_manager->register( new ShortcodeWidget( $slug, $tag, $title, $icon, $guide_control, $label_control ) );
		}
	}
}
