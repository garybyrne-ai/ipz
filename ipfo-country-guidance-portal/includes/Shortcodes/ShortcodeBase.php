<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Capabilities;
use IPFO\Frontend\PortalNav;

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

	protected function require_business_access(): string|true {
		if ( Capabilities::current_user_can_access_business_portal() ) {
			return true;
		}

		return ipfo_render_template(
			'partials/login-required.php',
			[ 'login_url' => ipfo_get_portal_page_url( 'login' ) ]
		);
	}

	/**
	 * @param bool   $with_nav   Pass false for auth pages (login/register)
	 *                           where showing the signed-in nav shell would
	 *                           never make sense even in the logged-in edge
	 *                           case those pages handle (e.g. "you're
	 *                           already signed in"). Everywhere else, the
	 *                           nav simply doesn't render for a logged-out
	 *                           visitor regardless of this flag.
	 * @param string $active_key Nav item to highlight — see
	 *                           PortalNav::client_items()/business_items().
	 */
	protected function wrap( string $inner, string $extra_class = '', bool $with_nav = true, string $active_key = '' ): string {
		$theme = get_option( 'ipfo_default_reading_theme', 'light' );
		$nav   = $with_nav ? PortalNav::render( $active_key ) : '';

		return sprintf(
			'<div class="ipfo-portal %1$s%2$s" data-ipfo-theme="%3$s">%4$s<div class="ipfo-portal__main">%5$s</div></div>',
			esc_attr( $extra_class ),
			$nav ? ' ipfo-has-nav' : '',
			esc_attr( $theme ),
			$nav,
			$inner
		);
	}
}
