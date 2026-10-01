<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class ShortcodeBase {

	abstract public function render( array $atts = [] ): string;

	protected function require_access(): string|true {
		if ( Capabilities::current_user_can_access_portal() ) {
			return true;
		}

		return ipfo_render_template(
			'partials/login-required.php',
			[ 'login_url' => ipfo_get_portal_page_url( 'login' ) ]
		);
	}

	protected function wrap( string $inner, string $extra_class = '' ): string {
		$theme = get_option( 'ipfo_default_reading_theme', 'light' );

		return sprintf(
			'<div class="ipfo-portal %1$s" data-ipfo-theme="%2$s">%3$s</div>',
			esc_attr( $extra_class ),
			esc_attr( $theme ),
			$inner
		);
	}
}
