<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\CPT\GuideChapterCPT;
use IPFO\Repositories\GuideVersionRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuideService {

	private GuideVersionRepository $versions;
	private NotificationService $notifications;

	public function __construct() {
		$this->versions      = new GuideVersionRepository();
		$this->notifications = new NotificationService();
	}

	public function meta( int $guide_id ): array {
		return [
			'country_id'       => (int) get_post_meta( $guide_id, '_ipfo_country_id', true ),
			'version'          => (string) ( get_post_meta( $guide_id, '_ipfo_version', true ) ?: '1.0' ),
			'publication_date' => (string) get_post_meta( $guide_id, '_ipfo_publication_date', true ),
			'last_reviewed'    => (string) get_post_meta( $guide_id, '_ipfo_last_reviewed', true ),
			'next_review'      => (string) get_post_meta( $guide_id, '_ipfo_next_review', true ),
			'download_enabled' => (bool) get_post_meta( $guide_id, '_ipfo_download_enabled', true ),
			'viewing_enabled'  => get_post_meta( $guide_id, '_ipfo_online_viewing_enabled', true ) !== '0',
			'ack_required'     => (bool) get_post_meta( $guide_id, '_ipfo_ack_required', true ),
			'pdf_resource_id'  => (int) get_post_meta( $guide_id, '_ipfo_pdf_resource_id', true ),
		];
	}

	/** @return \WP_Post[] */
	public function chapters( int $guide_id ): array {
		return GuideChapterCPT::for_guide( $guide_id );
	}

	public function reading_time_minutes( int $guide_id ): int {
		$words = 0;
		foreach ( $this->chapters( $guide_id ) as $chapter ) {
			$words += str_word_count( wp_strip_all_tags( $chapter->post_content ) );
		}

		return max( 1, (int) ceil( $words / 200 ) );
	}

	public function publish_new_version( int $guide_id, string $version, string $changelog, bool $requires_ack ): void {
		update_post_meta( $guide_id, '_ipfo_version', $version );
		update_post_meta( $guide_id, '_ipfo_last_reviewed', current_time( 'mysql' ) );
		update_post_meta( $guide_id, '_ipfo_ack_required', $requires_ack ? '1' : '0' );

		$this->versions->publish( $guide_id, $version, $changelog, $requires_ack );

		$this->notify_assigned_users( $guide_id, 'updated', $version );
	}

	public function notify_assigned_users( int $guide_id, string $type = 'new', string $version = '' ): void {
		$country_id = (int) get_post_meta( $guide_id, '_ipfo_country_id', true );
		$guide_link = add_query_arg( [ 'ipfo_guide' => $guide_id ], ipfo_get_portal_page_url( 'dashboard' ) );
		$title      = get_the_title( $guide_id );

		$user_ids = $this->users_with_access( $guide_id, $country_id );

		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			if ( 'updated' === $type ) {
				$this->notifications->guide_updated( $user, $title, $version, $guide_link );
				if ( get_post_meta( $guide_id, '_ipfo_ack_required', true ) ) {
					$this->notifications->acknowledgement_required( $user, $title, $guide_link );
				}
			} else {
				$this->notifications->guide_assigned( $user, $title, $guide_link );
			}
		}
	}

	/** @return int[] */
	private function users_with_access( int $guide_id, int $country_id ): array {
		global $wpdb;
		$table = \IPFO\Database\Schema::table( 'user_access' );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id FROM {$table} WHERE status = 'active' AND
				 ((object_type = 'guide' AND object_id = %d) OR (object_type = 'country' AND object_id = %d))",
				$guide_id,
				$country_id
			)
		);

		return array_map( 'intval', $ids );
	}
}
