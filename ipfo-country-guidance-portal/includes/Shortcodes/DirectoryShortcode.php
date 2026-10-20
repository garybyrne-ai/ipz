<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Services\BusinessDirectoryService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public, unauthenticated Business Directory listing page. Unlike every
 * other shortcode in this plugin, this one is NOT gated behind
 * require_access() — the directory is part of the public, SEO-visible
 * website, not the private Intended Parent portal.
 */
final class DirectoryShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$service = new BusinessDirectoryService();

		$filters = [
			'category' => absint( $_GET['ipfo_category'] ?? 0 ),
			'country'  => absint( $_GET['ipfo_country'] ?? 0 ),
			'q'        => sanitize_text_field( wp_unslash( $_GET['ipfo_q'] ?? '' ) ),
		];

		$paged  = max( 1, absint( $_GET['ipfo_paged'] ?? 1 ) );
		$result = $service->search( $filters, $paged );

		return $this->wrap(
			ipfo_render_template(
				'directory.php',
				[
					'businesses' => $result['posts'],
					'total'      => $result['total'],
					'max_pages'  => $result['max_pages'],
					'paged'      => $paged,
					'filters'    => $filters,
					'categories' => $service->categories(),
					'countries'  => $service->countries(),
				]
			),
			'ipfo-portal--public',
			true,
			'directory'
		);
	}
}
