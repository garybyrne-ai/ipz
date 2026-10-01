<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records that a user has read and acknowledged a specific guide version.
 * This is a factual record of acknowledgement only; it must never be
 * presented to users as legal advice or legal consent.
 */
final class AcknowledgementRepository extends BaseRepository {

	protected function table_name(): string {
		return 'acknowledgements';
	}

	public function has_acknowledged( int $user_id, int $guide_id, string $version ): bool {
		$count = (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND guide_id = %d AND version = %s",
				$user_id,
				$guide_id,
				$version
			)
		);

		return $count > 0;
	}

	public function record( int $user_id, int $guide_id, string $version ): int {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$this->db->insert(
			$this->table,
			[
				'user_id'         => $user_id,
				'guide_id'        => $guide_id,
				'version'         => $version,
				'acknowledged_at' => $this->now(),
				'ip_hash'         => $ip ? self::hash_ip( $ip ) : null,
				'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : null,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s' ]
		);

		return (int) $this->db->insert_id;
	}

	public function for_user( int $user_id ): array {
		return $this->db->get_results(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY acknowledged_at DESC", $user_id )
		);
	}

	public function for_guide( int $guide_id ): array {
		return $this->db->get_results(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE guide_id = %d ORDER BY acknowledged_at DESC", $guide_id )
		);
	}
}
