<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Capabilities;
use IPFO\CPT\CountryGuideCPT;
use IPFO\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight aggregate queries for the admin dashboard and analytics
 * screen. Deliberately avoids pulling in a JS charting library; totals are
 * rendered as simple numbers and HTML tables/bars.
 */
final class AnalyticsService {

	public function totals(): array {
		global $wpdb;

		$ip_users = count( get_users( [ 'role' => Capabilities::CLIENT_ROLE, 'fields' => 'ID' ] ) );

		$active_ip_users = count(
			get_users(
				[
					'role'       => Capabilities::CLIENT_ROLE,
					'fields'     => 'ID',
					'meta_query' => [ [ 'key' => '_ipfo_approval_status', 'value' => 'approved' ] ],
				]
			)
		);

		return [
			'total_ips'        => $ip_users,
			'active_ips'       => $active_ip_users,
			'countries'        => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Schema::table( 'countries' ) ),
			'published_guides' => (int) wp_count_posts( CountryGuideCPT::SLUG )->publish,
			'draft_guides'     => (int) wp_count_posts( CountryGuideCPT::SLUG )->draft,
			'pending_ack'      => $this->pending_acknowledgement_count(),
			'expiring_invites' => count( ( new InvitationService() )->expiring_soon( 7 ) ),
		];
	}

	private function pending_acknowledgement_count(): int {
		global $wpdb;

		$guides = get_posts(
			[
				'post_type'      => CountryGuideCPT::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [ [ 'key' => '_ipfo_ack_required', 'value' => '1' ] ],
			]
		);

		if ( ! $guides ) {
			return 0;
		}

		$access_table = Schema::table( 'user_access' );
		$ack_table    = Schema::table( 'acknowledgements' );
		$placeholders = implode( ',', array_fill( 0, count( $guides ), '%d' ) );

		$sql = "SELECT COUNT(*) FROM {$access_table} ua
			WHERE ua.object_type = 'guide' AND ua.status = 'active' AND ua.object_id IN ({$placeholders})
			AND NOT EXISTS (
				SELECT 1 FROM {$ack_table} ak WHERE ak.user_id = ua.user_id AND ak.guide_id = ua.object_id
			)";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $guides ) );
	}

	public function recent_registrations( int $limit = 5 ): array {
		return get_users(
			[
				'role'    => Capabilities::CLIENT_ROLE,
				'orderby' => 'registered',
				'order'   => 'DESC',
				'number'  => $limit,
			]
		);
	}

	public function users_by_country(): array {
		global $wpdb;

		$countries_table = Schema::table( 'countries' );
		$access_table    = Schema::table( 'user_access' );

		return $wpdb->get_results(
			"SELECT c.name, COUNT(DISTINCT ua.user_id) AS total
			 FROM {$countries_table} c
			 LEFT JOIN {$access_table} ua ON ua.object_type = 'country' AND ua.object_id = c.id AND ua.status = 'active'
			 GROUP BY c.id
			 ORDER BY total DESC"
		);
	}

	public function guide_views( int $limit = 10 ): array {
		global $wpdb;

		$logs_table = Schema::table( 'access_logs' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT object_id AS guide_id, COUNT(*) AS views
				 FROM {$logs_table}
				 WHERE event_type = 'guide_view' AND object_type = 'guide'
				 GROUP BY object_id
				 ORDER BY views DESC
				 LIMIT %d",
				$limit
			)
		);
	}
}
