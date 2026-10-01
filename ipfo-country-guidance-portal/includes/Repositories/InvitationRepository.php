<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InvitationRepository extends BaseRepository {

	protected function table_name(): string {
		return 'invitations';
	}

	public function create( array $data ): int {
		$now = $this->now();

		$this->db->insert(
			$this->table,
			[
				'code'               => $data['code'],
				'token_hash'         => $data['token_hash'],
				'country_id'         => $data['country_id'] ?? null,
				'guide_id'           => $data['guide_id'] ?? null,
				'assigned_resources' => isset( $data['assigned_resources'] ) ? wp_json_encode( array_map( 'intval', (array) $data['assigned_resources'] ) ) : null,
				'max_uses'           => max( 1, (int) ( $data['max_uses'] ?? 1 ) ),
				'use_count'          => 0,
				'single_use'         => ! empty( $data['single_use'] ) ? 1 : 0,
				'status'             => 'active',
				'expires_at'         => $data['expires_at'] ?? null,
				'created_by'         => get_current_user_id(),
				'created_at'         => $now,
				'updated_at'         => $now,
			],
			[ '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s' ]
		);

		return (int) $this->db->insert_id;
	}

	public function get( int $id ): ?object {
		$row = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
		return $row ?: null;
	}

	public function get_by_code( string $code ): ?object {
		$row = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table} WHERE code = %s", $code ) );
		return $row ?: null;
	}

	public function all( array $args = [] ): array {
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC';

		if ( $params ) {
			$sql = $this->db->prepare( $sql, $params );
		}

		return $this->db->get_results( $sql );
	}

	public function expiring_within_days( int $days ): array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at <= DATE_ADD(%s, INTERVAL %d DAY) ORDER BY expires_at ASC",
				$this->now(),
				$days
			)
		);
	}

	public function mark_expired_due(): int {
		return (int) $this->db->query(
			$this->db->prepare(
				"UPDATE {$this->table} SET status = 'expired', updated_at = %s WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < %s",
				$this->now(),
				$this->now()
			)
		);
	}

	public function increment_use( int $id ): void {
		$this->db->query(
			$this->db->prepare(
				"UPDATE {$this->table} SET use_count = use_count + 1, updated_at = %s WHERE id = %d",
				$this->now(),
				$id
			)
		);

		$invitation = $this->get( $id );
		if ( ! $invitation ) {
			return;
		}

		if ( (int) $invitation->use_count >= (int) $invitation->max_uses ) {
			$this->db->update(
				$this->table,
				[ 'status' => 'used', 'updated_at' => $this->now() ],
				[ 'id' => $id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
		}
	}

	public function revoke( int $id ): bool {
		return (bool) $this->db->update(
			$this->table,
			[ 'status' => 'revoked', 'updated_at' => $this->now() ],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	public function delete( int $id ): bool {
		return (bool) $this->db->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}
}
