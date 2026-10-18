<?php
declare( strict_types = 1 );

namespace IPFO\Frontend;

use IPFO\CPT\BusinessCPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the public single business-listing permalink
 * (ipfertilityoptions.com/directory/clinic-name/) using the plugin's own
 * template, the same approach WooCommerce/EDD use for single-product pages:
 * the active theme (UiCore Pro) has no single-ipfo_business.php of its own,
 * so relying on the theme template hierarchy would 404. get_header()/
 * get_footer() are still called so the page inherits the site's real header,
 * navigation and footer — only the content area is ours.
 */
final class SingleBusinessTemplate {

	public function register(): void {
		add_filter( 'template_include', [ $this, 'maybe_override' ] );
	}

	public function maybe_override( string $template ): string {
		if ( ! is_singular( BusinessCPT::SLUG ) ) {
			return $template;
		}

		$business = get_queried_object();
		if ( ! ( $business instanceof \WP_Post ) ) {
			return $template;
		}

		/**
		 * A non-published listing (pending review, or an owner's own draft)
		 * is never shown publicly through this permalink — WordPress's own
		 * singular-visibility rules already 404 it for everyone except the
		 * post's author or an administrator, so this is a defence-in-depth
		 * belt-and-braces check, not the only guard.
		 */
		if ( 'publish' !== $business->post_status && ! current_user_can( 'manage_options' ) && get_current_user_id() !== (int) $business->post_author ) {
			return $template;
		}

		add_filter( 'ipfo_force_enqueue_assets', '__return_true' );

		return IPFO_PLUGIN_DIR . 'templates/single-business-page.php';
	}
}
