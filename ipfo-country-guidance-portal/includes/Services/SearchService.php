<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\GuideChapterCPT;
use IPFO\CPT\FaqCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\ChecklistRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Secure portal-wide search. Every branch restricts results to IDs the
 * AccessControlService has already approved for this user — a search query
 * can never be used to enumerate content the user is not authorised to see.
 */
final class SearchService {

	private AccessControlService $access;

	public function __construct() {
		$this->access = new AccessControlService();
	}

	public function search( int $user_id, string $term ): array {
		$term = trim( sanitize_text_field( $term ) );
		if ( '' === $term || mb_strlen( $term ) < 2 ) {
			return [];
		}

		$guide_ids    = $this->access->guide_ids_for_user( $user_id );
		$resource_ids = $this->access->resource_ids_for_user( $user_id );
		$country_id   = $this->access->user_country_id( $user_id );

		$results = [];

		if ( $guide_ids ) {
			foreach ( get_posts( [ 'post_type' => CountryGuideCPT::SLUG, 'post__in' => $guide_ids, 's' => $term, 'posts_per_page' => 10 ] ) as $post ) {
				$results[] = $this->result( 'guide', $post );
			}

			foreach ( get_posts( [ 'post_type' => GuideChapterCPT::SLUG, 'meta_key' => '_ipfo_guide_id', 'meta_value' => $guide_ids, 'meta_compare' => 'IN', 's' => $term, 'posts_per_page' => 10 ] ) as $post ) {
				$results[] = $this->result( 'chapter', $post );
			}
		}

		if ( $country_id ) {
			foreach ( FaqCPT::for_country( $country_id ) as $post ) {
				if ( $this->matches( $term, $post ) ) {
					$results[] = $this->result( 'faq', $post );
				}
			}

			foreach ( ( new ChecklistRepository() )->items_for_country( $country_id ) as $item ) {
				if ( false !== stripos( $item->label . ' ' . $item->description, $term ) ) {
					$results[] = [
						'type'  => 'checklist',
						'id'    => (int) $item->id,
						'title' => $item->label,
						'excerpt' => wp_trim_words( wp_strip_all_tags( $item->description ), 20 ),
					];
				}
			}
		}

		if ( $resource_ids ) {
			foreach ( get_posts( [ 'post_type' => ResourceCPT::SLUG, 'post__in' => $resource_ids, 's' => $term, 'posts_per_page' => 10 ] ) as $post ) {
				$results[] = $this->result( 'resource', $post );
			}
		}

		return $results;
	}

	private function matches( string $term, \WP_Post $post ): bool {
		return false !== stripos( $post->post_title . ' ' . $post->post_content, $term );
	}

	private function result( string $type, \WP_Post $post ): array {
		return [
			'type'    => $type,
			'id'      => $post->ID,
			'title'   => get_the_title( $post ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 ),
		];
	}
}
