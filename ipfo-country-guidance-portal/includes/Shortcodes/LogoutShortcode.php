<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LogoutShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts( [ 'label' => __( 'Sign Out', 'ipfo-country-guidance-portal' ) ], $atts );

		$url = wp_logout_url( ipfo_get_portal_page_url( 'login' ) );

		return sprintf(
			'<a class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm" href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html( $atts['label'] )
		);
	}
}
