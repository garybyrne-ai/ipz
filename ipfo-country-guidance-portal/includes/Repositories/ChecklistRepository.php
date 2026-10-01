<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChecklistRepository extends BaseRepository {

	protected function table_name(): string {
		return 'checklist_items';
	}

	private function progress_table(): string {
		return $this->db->prefix . 'ipfo_checklist_progress';
	}

	public function items_for_country( int $country_id ): array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE country_id = %d AND status = 'active' ORDER BY sort_order ASC, id ASC",
				$country_id
			)
		);
	}

	public function get_item( int $id ): ?object {
		$row = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ) );
		return $row ?: null;
	}

	public function create_item( int $country_id, string $label, string $description = '', int $sort_order = 0 ): int {
		$now = $this->now();

		$this->db->insert(
			$this->table,
			[
				'country_id'  => $country_id,
				'label'       => sanitize_text_field( $label ),
				'description' => sanitize_textarea_field( $description ),
				'sort_order'  => $sort_order,
				'status'      => 'active',
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);

		return (int) $this->db->insert_id;
	}

	public function update_item( int $id, array $data ): bool {
		$fields  = [];
		$formats = [];

		if ( isset( $data['label'] ) ) {
			$fields['label'] = sanitize_text_field( $data['label'] );
			$formats[]       = '%s';
		}
		if ( isset( $data['description'] ) ) {
			$fields['description'] = sanitize_textarea_field( $data['description'] );
			$formats[]             = '%s';
		}
		if ( isset( $data['sort_order'] ) ) {
			$fields['sort_order'] = (int) $data['sort_order'];
			$formats[]            = '%d';
		}
		if ( isset( $data['status'] ) ) {
			$fields['status'] = sanitize_key( $data['status'] );
			$formats[]        = '%s';
		}

		if ( empty( $fields ) ) {
			return false;
		}

		$fields['updated_at'] = $this->now();
		$formats[]            = '%s';

		return (bool) $this->db->update( $this->table, $fields, [ 'id' => $id ], $formats, [ '%d' ] );
	}

	public function delete_item( int $id ): bool {
		$this->db->delete( $this->progress_table(), [ 'checklist_item_id' => $id ], [ '%d' ] );
		return (bool) $this->db->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}

	public function reorder( array $ordered_ids ): void {
		foreach ( array_values( $ordered_ids ) as $index => $id ) {
			$this->db->update(
				$this->table,
				[ 'sort_order' => $index, 'updated_at' => $this->now() ],
				[ 'id' => (int) $id ],
				[ '%d', '%s' ],
				[ '%d' ]
			);
		}
	}

	/** @return array<int,bool> item_id => is_complete */
	public function progress_for_user( int $user_id, int $country_id ): array {
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT ci.id, COALESCE(cp.is_complete, 0) AS is_complete
				 FROM {$this->table} ci
				 LEFT JOIN {$this->progress_table()} cp ON cp.checklist_item_id = ci.id AND cp.user_id = %d
				 WHERE ci.country_id = %d AND ci.status = 'active'
				 ORDER BY ci.sort_order ASC",
				$user_id,
				$country_id
			)
		);

		$result = [];
		foreach ( $rows as $row ) {
			$result[ (int) $row->id ] = (bool) $row->is_complete;
		}

		return $result;
	}

	public function set_complete( int $user_id, int $item_id, bool $complete ): void {
		$existing = $this->db->get_row(
			$this->db->prepare(
				"SELECT id FROM {$this->progress_table()} WHERE user_id = %d AND checklist_item_id = %d",
				$user_id,
				$item_id
			)
		);

		$data = [
			'is_complete'  => $complete ? 1 : 0,
			'completed_at' => $complete ? $this->now() : null,
		];

		if ( $existing ) {
			$this->db->update( $this->progress_table(), $data, [ 'id' => $existing->id ], [ '%d', '%s' ], [ '%d' ] );
			return;
		}

		$data['user_id']           = $user_id;
		$data['checklist_item_id'] = $item_id;

		$this->db->insert( $this->progress_table(), $data, [ '%d', '%s', '%d', '%d' ] );
	}
}
