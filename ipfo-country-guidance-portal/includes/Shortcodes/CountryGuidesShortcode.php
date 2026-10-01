<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\CPT\FaqCPT;
use IPFO\Repositories\CountryRepository;
use IPFO\Repositories\UserAccessRepository;
use IPFO\Services\AccessControlService;
use IPFO\Services\GuideService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Country-by-country overview of everything the client is authorised to
 * see: assigned guide(s), FAQs and the guide's current review status.
 */
final class CountryGuidesShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$user_id       = get_current_user_id();
		$access        = new AccessControlService();
		$guide_service = new GuideService();

		$country_ids = ( new UserAccessRepository() )->object_ids_for_user( $user_id, UserAccessRepository::TYPE_COUNTRY );
		$countries   = [];

		foreach ( $country_ids as $country_id ) {
			$country = ( new CountryRepository() )->get( $country_id );
			if ( ! $country ) {
				continue;
			}

			$guides = get_posts(
				[
					'post_type'      => \IPFO\CPT\CountryGuideCPT::SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'meta_key'       => '_ipfo_country_id',
					'meta_value'     => $country_id,
				]
			);

			$countries[] = [
				'country'   => $country,
				'guides'    => array_map(
					static fn( $g ) => [ 'post' => $g, 'meta' => $guide_service->meta( $g->ID ), 'url' => add_query_arg( [ 'ipfo_guide' => $g->ID ], ipfo_get_portal_page_url( 'guide' ) ) ],
					$guides
				),
				'faqs'      => FaqCPT::for_country( $country_id ),
			];
		}

		return $this->wrap(
			ipfo_render_template( 'country-guides.php', [ 'countries' => $countries ] ),
			'ipfo-country-guides'
		);
	}
}
