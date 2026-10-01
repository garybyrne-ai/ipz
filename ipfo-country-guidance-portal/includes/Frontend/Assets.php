<?php
declare( strict_types = 1 );

namespace IPFO\Frontend;

use IPFO\Shortcodes\ShortcodeManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditionally loads the portal's CSS/JS. The public IPFO website must stay
 * fast, so nothing here is enqueued unless the current page actually
 * contains an [IPFO_*] shortcode or an IPFO Elementor widget.
 */
final class Assets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue' ] );
	}

	public function maybe_enqueue(): void {
		if ( ! $this->current_page_uses_portal() ) {
			return;
		}

		wp_enqueue_style(
			'ipfo-variables',
			IPFO_PLUGIN_URL . 'assets/css/ipfo-variables.css',
			[],
			IPFO_VERSION
		);

		wp_enqueue_style(
			'ipfo-portal',
			IPFO_PLUGIN_URL . 'assets/css/ipfo-portal.css',
			[ 'ipfo-variables' ],
			IPFO_VERSION
		);

		wp_enqueue_script(
			'ipfo-portal',
			IPFO_PLUGIN_URL . 'assets/js/ipfo-portal.js',
			[],
			IPFO_VERSION,
			true
		);

		wp_localize_script(
			'ipfo-portal',
			'IPFO_PORTAL',
			[
				'restUrl'   => esc_url_raw( rest_url( 'ipfo/v1' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'      => [
					'acknowledged' => __( 'Thank you — your acknowledgement has been recorded.', 'ipfo-country-guidance-portal' ),
					'noResults'    => __( 'No results found.', 'ipfo-country-guidance-portal' ),
				],
			]
		);
	}

	private function current_page_uses_portal(): bool {
		$post = get_queried_object();

		if ( ! ( $post instanceof \WP_Post ) ) {
			return false;
		}

		foreach ( ShortcodeManager::TAGS as $tag ) {
			if ( has_shortcode( (string) $post->post_content, $tag ) ) {
				return true;
			}
		}

		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
		if ( $elementor_data && false !== strpos( (string) $elementor_data, 'ipfo-' ) ) {
			return true;
		}

		/**
		 * Allow themes/editors to force-load portal assets on a page that
		 * doesn't contain a detectable shortcode (e.g. a template part).
		 */
		return (bool) apply_filters( 'ipfo_force_enqueue_assets', false, $post );
	}
}
