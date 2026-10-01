<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Repositories\AcknowledgementRepository;
use IPFO\Repositories\AccessLogRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records that a user has read and acknowledged guidance. This is a factual
 * audit record only; it is never presented as legal advice or legal
 * consent, per the configurable disclaimer shown alongside it.
 */
final class AcknowledgementService {

	private AcknowledgementRepository $repo;
	private AccessLogRepository $logs;
	private AccessControlService $access;

	public function __construct() {
		$this->repo   = new AcknowledgementRepository();
		$this->logs   = new AccessLogRepository();
		$this->access = new AccessControlService();
	}

	public function requires_acknowledgement( int $user_id, int $guide_id ): bool {
		if ( ! get_post_meta( $guide_id, '_ipfo_ack_required', true ) ) {
			return false;
		}

		$version = (string) ( get_post_meta( $guide_id, '_ipfo_version', true ) ?: '1.0' );

		return ! $this->repo->has_acknowledged( $user_id, $guide_id, $version );
	}

	public function acknowledge( int $user_id, int $guide_id ): bool {
		if ( ! $this->access->can_access_guide( $user_id, $guide_id ) ) {
			return false;
		}

		$version = (string) ( get_post_meta( $guide_id, '_ipfo_version', true ) ?: '1.0' );

		$this->repo->record( $user_id, $guide_id, $version );
		$this->logs->log( 'acknowledgement', $user_id, 'guide', $guide_id, [ 'version' => $version ] );

		return true;
	}

	public function history_for_user( int $user_id ): array {
		return $this->repo->for_user( $user_id );
	}

	public function history_for_guide( int $guide_id ): array {
		return $this->repo->for_guide( $guide_id );
	}
}
