<?php
declare( strict_types = 1 );

namespace IPFO\CPT;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "IPFO Resources" custom post type: PDFs, DOCX, images, videos, forms,
 * checklists, information sheets and external links. Protected resources
 * are served through ResourceDownloadService, never a raw Media Library URL.
 */
final class ResourceCPT {

	public const SLUG = 'ipfo_resource';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		register_post_type(
			self::SLUG,
			[
				'labels'              => [
					'name'          => __( 'Resources', 'ipfo-country-guidance-portal' ),
					'singular_name' => __( 'Resource', 'ipfo-country-guidance-portal' ),
					'add_new_item'  => __( 'Add New Resource', 'ipfo-country-guidance-portal' ),
					'edit_item'     => __( 'Edit Resource', 'ipfo-country-guidance-portal' ),
					'all_items'     => __( 'Resources', 'ipfo-country-guidance-portal' ),
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
				'supports'            => [ 'title', 'editor', 'thumbnail' ],
				'capability_type'     => [ 'ipfo_resource', 'ipfo_resources' ],
				'capabilities'        => Capabilities::cpt_capabilities(),
				'map_meta_cap'        => true,
			]
		);
	}
}
