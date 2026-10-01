<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grants of a user to a country / guide / resource. This is the single
 * source of truth for "may this user see this content" alongside the
 * user's own assigned country stored in user meta.
 */
final class UserAccessRepository extends BaseRepository {

	public const TYPE_COUNTRY  = 'country';
	public const TYPE_GUIDE    = 'guide';
	public const TYPE_RESOURCE = 'resource';

	protected function table_name(): string {
		return 'user_access';
	}

	public function grant( int $user_id, string $object_type, int $object_id, ?int $granted_by = null, ?string $expires_at = null ): int {
		$existing = $this->find( $user_id, $object_type, $object_id );

		if ( $existing ) {
			$this->db->update(
				$this->table,
				[
					'status'     => 'active',
					'granted_by' => $granted_by,
					'granted_at' => $this->now(),
					'expires_at' => $expires_at,
				],
				[ 'id' => $existing->id ],
				[ '%s', '%d', '%s', '%s' ],
				[ '%d' ]
			);

			return (int) $existing->id;
		}

		$this->db->insert(
			$this->table,
			[
				'user_id'     => $user_id,
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => $object_id,
				'status'      => 'active',
				'granted_by'  => $granted_by,
				'granted_at'  => $this->now(),
				'expires_at'  => $expires_at,
			],
			[ '%d', '%s', '%d', '%s', '%d', '%s', '%s' ]
		);

		return (int) $this->db->insert_id;
	}

	public function find( int $user_id, string $object_type, int $object_id ): ?object {
		$row = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d AND object_type = %s AND object_id = %d",
				$user_id,
				$object_type,
				$object_id
			)
		);

		return $row ?: null;
	}

	public function has_active_access( int $user_id, string $object_type, int $object_id ): bool {
		$row = $this->find( $user_id, $object_type, $object_id );

		if ( ! $row || 'active' !== $row->status ) {
			return false;
		}

		if ( $row->expires_at && strtotime( $row->expires_at ) < time() ) {
			return false;
		}

		return true;
	}

	public function revoke( int $user_id, string $object_type, int $object_id ): bool {
		return (bool) $this->db->update(
			$this->table,
			[ 'status' => 'revoked' ],
			[ 'user_id' => $user_id, 'object_type' => $object_type, 'object_id' => $object_id ],
			[ '%s' ],
			[ '%d', '%s', '%d' ]
		);
	}

	public function suspend( int $user_id, string $object_type, int $object_id ): bool {
		return (bool) $this->db->update(
			$this->table,
			[ 'status' => 'suspended' ],
			[ 'user_id' => $user_id, 'object_type' => $object_type, 'object_id' => $object_id ],
			[ '%s' ],
			[ '%d', '%s', '%d' ]
		);
	}

	/** @return int[] */
	public function object_ids_for_user( int $user_id, string $object_type ): array {
		$ids = $this->db->get_col(
			$this->db->prepare(
				"SELECT object_id FROM {$this->table} WHERE user_id = %d AND object_type = %s AND status = 'active' AND (expires_at IS NULL OR expires_at > %s)",
				$user_id,
				$object_type,
				$this->now()
			)
		);

		return array_map( 'intval', $ids );
	}

	/** @return object[] */
	public function for_user( int $user_id ): array {
		return $this->db->get_results(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY granted_at DESC", $user_id )
		);
	}

	public function revoke_all_for_user( int $user_id ): bool {
		return (bool) $this->db->update(
			$this->table,
			[ 'status' => 'revoked' ],
			[ 'user_id' => $user_id ],
			[ '%s' ],
			[ '%d' ]
		);
	}
}
