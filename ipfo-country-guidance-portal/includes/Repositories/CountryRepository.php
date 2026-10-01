<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CountryRepository extends BaseRepository {

	protected function table_name(): string {
		return 'countries';
	}

	public function all( string $status = '' ): array {
		if ( '' !== $status ) {
			return $this->db->get_results(
				$this->db->prepare( "SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC", $status )
			);
		}

		return $this->db->get_results( "SELECT * FROM {$this->table} ORDER BY name ASC" );
	}

	public function get( int $id ): ?object {
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);

		return $row ?: null;
	}

	public function get_by_code( string $code ): ?object {
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE code = %s", strtoupper( $code ) )
		);

		return $row ?: null;
	}

	public function create( array $data ): int {
		$now = $this->now();

		$this->db->insert(
			$this->table,
			[
				'name'          => sanitize_text_field( $data['name'] ?? '' ),
				'code'          => strtoupper( sanitize_text_field( $data['code'] ?? '' ) ),
				'flag'          => sanitize_text_field( $data['flag'] ?? '' ),
				'description'   => wp_kses_post( $data['description'] ?? '' ),
				'status'        => sanitize_key( $data['status'] ?? 'active' ),
				'last_reviewed' => ! empty( $data['last_reviewed'] ) ? sanitize_text_field( $data['last_reviewed'] ) : null,
				'created_at'    => $now,
				'updated_at'    => $now,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		return (int) $this->db->insert_id;
	}

	public function update( int $id, array $data ): bool {
		$fields  = [];
		$formats = [];

		$map = [
			'name'          => '%s',
			'code'          => '%s',
			'flag'          => '%s',
			'description'   => '%s',
			'status'        => '%s',
			'last_reviewed' => '%s',
		];

		foreach ( $map as $key => $format ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$value = $data[ $key ];
			if ( 'description' === $key ) {
				$value = wp_kses_post( $value );
			} elseif ( 'code' === $key ) {
				$value = strtoupper( sanitize_text_field( $value ) );
			} elseif ( 'status' === $key ) {
				$value = sanitize_key( $value );
			} elseif ( 'last_reviewed' === $key ) {
				$value = $value ? sanitize_text_field( $value ) : null;
			} else {
				$value = sanitize_text_field( $value );
			}

			$fields[ $key ] = $value;
			$formats[]      = $format;
		}

		if ( empty( $fields ) ) {
			return false;
		}

		$fields['updated_at'] = $this->now();
		$formats[]            = '%s';

		return (bool) $this->db->update( $this->table, $fields, [ 'id' => $id ], $formats, [ '%d' ] );
	}

	public function delete( int $id ): bool {
		return (bool) $this->db->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
	}
}
