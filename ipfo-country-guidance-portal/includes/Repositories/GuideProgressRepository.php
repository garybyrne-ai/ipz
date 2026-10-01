<?php
declare( strict_types = 1 );

namespace IPFO\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuideProgressRepository extends BaseRepository {

	protected function table_name(): string {
		return 'guide_progress';
	}

	public function get( int $user_id, int $guide_id ): ?object {
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE user_id = %d AND guide_id = %d", $user_id, $guide_id )
		);

		return $row ?: null;
	}

	public function for_user( int $user_id ): array {
		return $this->db->get_results(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE user_id = %d", $user_id )
		);
	}

	public function set_progress( int $user_id, int $guide_id, int $last_chapter_id, int $percent ): void {
		$percent = max( 0, min( 100, $percent ) );

		$existing = $this->get( $user_id, $guide_id );

		if ( $existing ) {
			$this->db->update(
				$this->table,
				[
					'last_chapter_id'   => $last_chapter_id,
					'progress_percent'  => $percent,
					'updated_at'        => $this->now(),
				],
				[ 'id' => $existing->id ],
				[ '%d', '%d', '%s' ],
				[ '%d' ]
			);
			return;
		}

		$this->db->insert(
			$this->table,
			[
				'user_id'          => $user_id,
				'guide_id'         => $guide_id,
				'last_chapter_id'  => $last_chapter_id,
				'progress_percent' => $percent,
				'bookmarks'        => wp_json_encode( [] ),
				'updated_at'       => $this->now(),
			],
			[ '%d', '%d', '%d', '%d', '%s', '%s' ]
		);
	}

	public function toggle_bookmark( int $user_id, int $guide_id, int $chapter_id ): array {
		$existing  = $this->get( $user_id, $guide_id );
		$bookmarks = $existing && $existing->bookmarks ? (array) json_decode( $existing->bookmarks, true ) : [];

		if ( in_array( $chapter_id, $bookmarks, true ) ) {
			$bookmarks = array_values( array_diff( $bookmarks, [ $chapter_id ] ) );
		} else {
			$bookmarks[] = $chapter_id;
		}

		if ( $existing ) {
			$this->db->update(
				$this->table,
				[ 'bookmarks' => wp_json_encode( $bookmarks ), 'updated_at' => $this->now() ],
				[ 'id' => $existing->id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			$this->db->insert(
				$this->table,
				[
					'user_id'          => $user_id,
					'guide_id'         => $guide_id,
					'last_chapter_id'  => $chapter_id,
					'progress_percent' => 0,
					'bookmarks'        => wp_json_encode( $bookmarks ),
					'updated_at'       => $this->now(),
				],
				[ '%d', '%d', '%d', '%d', '%s', '%s' ]
			);
		}

		return $bookmarks;
	}
}
