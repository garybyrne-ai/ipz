<?php
declare( strict_types = 1 );

namespace IPFO\CPT;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "IPFO FAQs" custom post type. Title = question, content = answer.
 * Ordered with menu_order, scoped to a country via the _ipfo_country_id meta.
 */
final class FaqCPT {

	public const SLUG = 'ipfo_faq';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		register_post_type(
			self::SLUG,
			[
				'labels'              => [
					'name'          => __( 'FAQs', 'ipfo-country-guidance-portal' ),
					'singular_name' => __( 'FAQ', 'ipfo-country-guidance-portal' ),
					'add_new_item'  => __( 'Add New FAQ', 'ipfo-country-guidance-portal' ),
					'edit_item'     => __( 'Edit FAQ', 'ipfo-country-guidance-portal' ),
					'all_items'     => __( 'FAQs', 'ipfo-country-guidance-portal' ),
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
				'supports'            => [ 'title', 'editor', 'page-attributes' ],
				'capability_type'     => [ 'ipfo_faq', 'ipfo_faqs' ],
				'capabilities'        => $this->capabilities(),
				'map_meta_cap'        => true,
			]
		);
	}

	private function capabilities(): array {
		$cap = Capabilities::MANAGE_PORTAL;

		return [
			'edit_post'              => $cap,
			'read_post'              => $cap,
			'delete_post'            => $cap,
			'edit_posts'             => $cap,
			'edit_others_posts'      => $cap,
			'publish_posts'          => $cap,
			'read_private_posts'     => $cap,
			'delete_posts'           => $cap,
			'delete_others_posts'    => $cap,
			'delete_published_posts' => $cap,
			'edit_published_posts'   => $cap,
			'create_posts'           => $cap,
		];
	}

	/** @return \WP_Post[] */
	public static function for_country( int $country_id ): array {
		return get_posts(
			[
				'post_type'      => self::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'meta_key'       => '_ipfo_country_id',
				'meta_value'     => $country_id,
			]
		);
	}
}
