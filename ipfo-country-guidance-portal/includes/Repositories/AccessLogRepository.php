<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Append-only audit log. Only an IP hash is stored, never a raw IP address,
 * to keep logging GDPR-conscious while still enabling abuse investigation.
 */
final class AccessLogRepository extends BaseRepository {

	protected function table_name(): string {
		return 'access_logs';
	}

	public function log( string $event_type, ?int $user_id = null, ?string $object_type = null, ?int $object_id = null, array $meta = [] ): void {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$this->db->insert(
			$this->table,
			[
				'user_id'     => $user_id,
				'event_type'  => sanitize_key( $event_type ),
				'object_type' => $object_type ? sanitize_key( $object_type ) : null,
				'object_id'   => $object_id,
				'meta'        => $meta ? wp_json_encode( $meta ) : null,
				'ip_hash'     => $ip ? self::hash_ip( $ip ) : null,
				'created_at'  => $this->now(),
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);
	}

	public function recent( int $limit = 50, array $filters = [] ): array {
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = (int) $filters['user_id'];
		}

		if ( ! empty( $filters['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$params[] = $filters['event_type'];
		}

		$params[] = $limit;

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT %d';

		return $this->db->get_results( $this->db->prepare( $sql, $params ) );
	}

	public function count_since( string $event_type, int $user_or_ip_hash_match, string $since ): int {
		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE event_type = %s AND user_id = %d AND created_at >= %s",
				$event_type,
				$user_or_ip_hash_match,
				$since
			)
		);
	}

	public function count_since_by_ip( string $event_type, string $ip_hash, string $since ): int {
		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE event_type = %s AND ip_hash = %s AND created_at >= %s",
				$event_type,
				$ip_hash,
				$since
			)
		);
	}
}
