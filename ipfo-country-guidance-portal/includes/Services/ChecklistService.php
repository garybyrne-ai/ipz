<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Repositories\ChecklistRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChecklistService {

	private ChecklistRepository $repo;
	private AccessControlService $access;

	public function __construct() {
		$this->repo   = new ChecklistRepository();
		$this->access = new AccessControlService();
	}

	/**
	 * @return array<int,array{id:int,label:string,description:string,complete:bool}>
	 */
	public function for_user( int $user_id, int $country_id ): array {
		if ( ! $this->access->can_access_country( $user_id, $country_id ) ) {
			return [];
		}

		$items    = $this->repo->items_for_country( $country_id );
		$progress = $this->repo->progress_for_user( $user_id, $country_id );

		$out = [];
		foreach ( $items as $item ) {
			$out[] = [
				'id'          => (int) $item->id,
				'label'       => $item->label,
				'description' => $item->description,
				'complete'    => $progress[ (int) $item->id ] ?? false,
			];
		}

		return $out;
	}

	public function toggle( int $user_id, int $item_id, bool $complete ): bool {
		$item = $this->repo->get_item( $item_id );
		if ( ! $item || ! $this->access->can_access_country( $user_id, (int) $item->country_id ) ) {
			return false;
		}

		$this->repo->set_complete( $user_id, $item_id, $complete );
		return true;
	}

	public function completion_percent( int $user_id, int $country_id ): int {
		$items = $this->for_user( $user_id, $country_id );
		if ( ! $items ) {
			return 0;
		}

		$done = count( array_filter( $items, static fn( $i ) => $i['complete'] ) );
		return (int) round( ( $done / count( $items ) ) * 100 );
	}

	public function remaining_count( int $user_id, int $country_id ): int {
		$items = $this->for_user( $user_id, $country_id );
		return count( array_filter( $items, static fn( $i ) => ! $i['complete'] ) );
	}
}
