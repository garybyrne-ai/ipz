<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Repositories\GuideProgressRepository;
use IPFO\Services\AccessControlService;
use IPFO\Services\GuideService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MyGuidesShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$user_id       = get_current_user_id();
		$access        = new AccessControlService();
		$guide_service = new GuideService();
		$progress_repo = new GuideProgressRepository();

		$guides = [];
		foreach ( $access->guide_ids_for_user( $user_id ) as $guide_id ) {
			$post = get_post( $guide_id );
			if ( ! $post ) {
				continue;
			}

			$meta     = $guide_service->meta( $guide_id );
			$progress = $progress_repo->get( $user_id, $guide_id );

			$guides[] = [
				'post'     => $post,
				'meta'     => $meta,
				'percent'  => $progress ? (int) $progress->progress_percent : 0,
				'url'      => add_query_arg( [ 'ipfo_guide' => $guide_id ], ipfo_get_portal_page_url( 'guide' ) ),
			];
		}

		return $this->wrap(
			ipfo_render_template( 'my-guides.php', [ 'guides' => $guides ] ),
			'ipfo-my-guides'
		);
	}
}
