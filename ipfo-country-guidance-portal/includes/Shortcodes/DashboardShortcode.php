<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Repositories\CountryRepository;
use IPFO\Repositories\GuideProgressRepository;
use IPFO\Services\AccessControlService;
use IPFO\Services\ChecklistService;
use IPFO\Services\GuideService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DashboardShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$user_id    = get_current_user_id();
		$access     = new AccessControlService();
		$country_id = $access->user_country_id( $user_id );
		$country    = $country_id ? ( new CountryRepository() )->get( $country_id ) : null;

		$guide_service   = new GuideService();
		$progress_repo   = new GuideProgressRepository();
		$guide_ids       = $access->guide_ids_for_user( $user_id );
		$primary_guide   = $guide_ids ? get_post( $guide_ids[0] ) : null;
		$progress        = $primary_guide ? $progress_repo->get( $user_id, $primary_guide->ID ) : null;
		$checklist       = new ChecklistService();

		$user = wp_get_current_user();

		return $this->wrap(
			ipfo_render_template(
				'dashboard.php',
				[
					'user_display_name' => $user->first_name ?: $user->display_name,
					'country'           => $country,
					'primary_guide'     => $primary_guide,
					'guide_version'     => $primary_guide ? $guide_service->meta( $primary_guide->ID )['version'] : '',
					'guide_updated'     => $primary_guide ? $guide_service->meta( $primary_guide->ID )['last_reviewed'] : '',
					'progress_percent'  => $progress ? (int) $progress->progress_percent : 0,
					'checklist_remaining' => $country_id ? $checklist->remaining_count( $user_id, $country_id ) : 0,
					'guides_url'        => ipfo_get_portal_page_url( 'my-guides' ),
					'guide_url'         => $primary_guide ? add_query_arg( [ 'ipfo_guide' => $primary_guide->ID ], ipfo_get_portal_page_url( 'guide' ) ) : '',
					'checklist_url'     => ipfo_get_portal_page_url( 'checklist' ),
				]
			),
			'ipfo-dashboard'
		);
	}
}
