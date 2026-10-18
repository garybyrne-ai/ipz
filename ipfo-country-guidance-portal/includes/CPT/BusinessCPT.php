<?php
declare( strict_types = 1 );

namespace IPFO\CPT;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "IPFO Businesses" custom post type: the public directory of recommended
 * IVF clinics, fertility lawyers, genetic testing labs, psychologists,
 * fertility counsellors and supplement providers.
 *
 * Unlike every other CPT in this plugin, this one is deliberately PUBLIC —
 * published listings are readable by anyone at a permalink under
 * /directory/… (the Intended Parent portal is the opposite: nothing in it
 * is ever publicly queryable). The write side stays exactly as locked down
 * as the rest of the plugin: capabilities() still maps everything to
 * Capabilities::MANAGE_PORTAL, so a business owner has zero WordPress
 * capability on this post type. Their own create/update goes through
 * BusinessDirectoryService, which checks post ownership itself. See the
 * comment on Capabilities::BUSINESS_ROLE for why.
 */
final class BusinessCPT {

	public const SLUG     = 'ipfo_business';
	public const TAXONOMY = 'ipfo_business_category';

	/**
	 * Seeded once on activation (see Activator::seed_business_categories()).
	 * Admins can add more from the Business Categories screen at any time —
	 * this list is only the starting set the user asked for.
	 */
	public const DEFAULT_CATEGORIES = [
		'IVF Clinics',
		'Fertility Lawyers',
		'Genetic Testing',
		'Psychologists',
		'Fertility Counsellors',
		'Supplements Providers',
	];

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	public function register_post_type(): void {
		register_post_type(
			self::SLUG,
			[
				'labels'              => [
					'name'          => __( 'Businesses', 'ipfo-country-guidance-portal' ),
					'singular_name' => __( 'Business', 'ipfo-country-guidance-portal' ),
					'add_new_item'  => __( 'Add New Business', 'ipfo-country-guidance-portal' ),
					'edit_item'     => __( 'Edit Business', 'ipfo-country-guidance-portal' ),
					'all_items'     => __( 'Businesses', 'ipfo-country-guidance-portal' ),
				],
				'public'              => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_menu'        => 'ipfo-portal',
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => [ 'slug' => 'directory', 'with_front' => false ],
				'query_var'           => true,
				'supports'            => [ 'title', 'editor', 'thumbnail', 'author' ],
				'capability_type'     => [ 'ipfo_business', 'ipfo_businesses' ],
				'capabilities'        => Capabilities::cpt_capabilities(),
				'map_meta_cap'        => true,
			]
		);
	}

	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			self::SLUG,
			[
				'labels'            => [
					'name'          => __( 'Business Categories', 'ipfo-country-guidance-portal' ),
					'singular_name' => __( 'Business Category', 'ipfo-country-guidance-portal' ),
				],
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_in_rest'      => false,
				'show_admin_column' => true,
				'rewrite'           => [ 'slug' => 'directory-category' ],
				'capabilities'      => [
					'manage_terms' => Capabilities::MANAGE_PORTAL,
					'edit_terms'   => Capabilities::MANAGE_PORTAL,
					'delete_terms' => Capabilities::MANAGE_PORTAL,
					// Business owners never get this: the category a listing
					// carries is set once at registration and only an admin
					// may recategorise it afterwards, from the admin screen.
					'assign_terms' => Capabilities::MANAGE_PORTAL,
				],
			]
		);
	}
}
