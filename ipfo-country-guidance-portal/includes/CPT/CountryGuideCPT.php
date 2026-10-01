<?php
declare( strict_types = 1 );

namespace IPFO\CPT;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "IPFO Country Guides" custom post type.
 *
 * Deliberately NOT publicly queryable: guides are never reachable through a
 * raw WordPress permalink. They are only ever rendered through the
 * [IPFO_GUIDE] shortcode / digital booklet template, after the
 * AccessControlService has verified the logged-in user is authorised.
 */
final class CountryGuideCPT {

	public const SLUG = 'ipfo_guide';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		$caps = $this->capabilities();

		register_post_type(
			self::SLUG,
			[
				'labels'              => [
					'name'          => __( 'Country Guides', 'ipfo-country-guidance-portal' ),
					'singular_name' => __( 'Country Guide', 'ipfo-country-guidance-portal' ),
					'add_new_item'  => __( 'Add New Country Guide', 'ipfo-country-guidance-portal' ),
					'edit_item'     => __( 'Edit Country Guide', 'ipfo-country-guidance-portal' ),
					'all_items'     => __( 'Country Guides', 'ipfo-country-guidance-portal' ),
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
				'menu_icon'           => 'dashicons-book-alt',
				'supports'            => [ 'title', 'editor', 'thumbnail', 'author', 'revisions' ],
				'capability_type'     => [ 'ipfo_guide', 'ipfo_guides' ],
				'capabilities'        => $caps,
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
}
