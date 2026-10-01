<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuideVersionRepository extends BaseRepository {

	protected function table_name(): string {
		return 'guide_versions';
	}

	public function publish( int $guide_id, string $version, string $changelog = '', bool $requires_ack = false ): int {
		$this->db->insert(
			$this->table,
			[
				'guide_id'     => $guide_id,
				'version'      => sanitize_text_field( $version ),
				'changelog'    => wp_kses_post( $changelog ),
				'requires_ack' => $requires_ack ? 1 : 0,
				'published_by' => get_current_user_id(),
				'published_at' => $this->now(),
			],
			[ '%d', '%s', '%s', '%d', '%d', '%s' ]
		);

		return (int) $this->db->insert_id;
	}

	public function latest( int $guide_id ): ?object {
		$row = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE guide_id = %d ORDER BY published_at DESC, id DESC LIMIT 1",
				$guide_id
			)
		);

		return $row ?: null;
	}

	public function history( int $guide_id ): array {
		return $this->db->get_results(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE guide_id = %d ORDER BY published_at DESC", $guide_id )
		);
	}
}
