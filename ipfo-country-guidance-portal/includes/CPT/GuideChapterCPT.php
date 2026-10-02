<?php
declare( strict_types = 1 );

namespace IPFO\CPT;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "IPFO Guide Chapters" custom post type. Each chapter belongs to one guide
 * (via the _ipfo_guide_id meta key) and is ordered with WordPress's native
 * menu_order field, so admins reorder chapters with a simple drag list
 * rather than editing HTML.
 */
final class GuideChapterCPT {

	public const SLUG = 'ipfo_chapter';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		register_post_type(
			self::SLUG,
			[
				'labels'              => [
					'name'          => __( 'Guide Chapters', 'ipfo-country-guidance-portal' ),
					'singular_name' => __( 'Chapter', 'ipfo-country-guidance-portal' ),
					'add_new_item'  => __( 'Add New Chapter', 'ipfo-country-guidance-portal' ),
					'edit_item'     => __( 'Edit Chapter', 'ipfo-country-guidance-portal' ),
					'all_items'     => __( 'Chapters', 'ipfo-country-guidance-portal' ),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'ipfo-portal',
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'hierarchical'        => false,
				'supports'            => [ 'title', 'editor', 'page-attributes', 'revisions' ],
				'capability_type'     => [ 'ipfo_chapter', 'ipfo_chapters' ],
				'capabilities'        => Capabilities::cpt_capabilities(),
				'map_meta_cap'        => true,
			]
		);
	}

	/**
	 * @return \WP_Post[] Chapters for a guide, ordered by menu_order.
	 */
	public static function for_guide( int $guide_id ): array {
		return get_posts(
			[
				'post_type'      => self::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'meta_key'       => '_ipfo_guide_id',
				'meta_value'     => $guide_id,
			]
		);
	}
}
