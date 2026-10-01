<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Services\AccessControlService;
use IPFO\Services\ChecklistService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChecklistShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$user_id    = get_current_user_id();
		$country_id = ( new AccessControlService() )->user_country_id( $user_id );

		if ( ! $country_id ) {
			return $this->wrap( '<div class="ipfo-empty-state">' . esc_html__( 'Your checklist will appear once a country has been assigned to your account.', 'ipfo-country-guidance-portal' ) . '</div>' );
		}

		$service = new ChecklistService();
		$items   = $service->for_user( $user_id, $country_id );
		$percent = $service->completion_percent( $user_id, $country_id );

		return $this->wrap(
			ipfo_render_template( 'checklist.php', [ 'items' => $items, 'percent' => $percent ] ),
			'ipfo-checklist'
		);
	}
}
