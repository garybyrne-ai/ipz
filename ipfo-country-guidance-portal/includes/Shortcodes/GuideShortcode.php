<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\GuideChapterCPT;
use IPFO\Repositories\AccessLogRepository;
use IPFO\Repositories\GuideProgressRepository;
use IPFO\Services\AccessControlService;
use IPFO\Services\AcknowledgementService;
use IPFO\Services\GuideService;
use IPFO\Services\ResourceDownloadService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuideShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$atts     = shortcode_atts( [ 'guide_id' => 0 ], $atts );
		$guide_id = isset( $_GET['ipfo_guide'] ) ? absint( $_GET['ipfo_guide'] ) : absint( $atts['guide_id'] );

		if ( ! $guide_id || get_post_type( $guide_id ) !== CountryGuideCPT::SLUG ) {
			return $this->wrap( '<div class="ipfo-empty-state">' . esc_html__( 'This guide could not be found.', 'ipfo-country-guidance-portal' ) . '</div>' );
		}

		$user_id = get_current_user_id();
		$access  = new AccessControlService();

		if ( ! $access->can_access_guide( $user_id, $guide_id ) ) {
			return $this->wrap( '<div class="ipfo-empty-state">' . esc_html__( 'You do not have access to this guide. Please contact IP Fertility Options if you believe this is incorrect.', 'ipfo-country-guidance-portal' ) . '</div>' );
		}

		$guide_service = new GuideService();
		$ack_service   = new AcknowledgementService();
		$progress_repo = new GuideProgressRepository();
		$logs          = new AccessLogRepository();

		$meta     = $guide_service->meta( $guide_id );
		$chapters = $guide_service->chapters( $guide_id );

		$chapter_id = isset( $_GET['ipfo_chapter'] ) ? absint( $_GET['ipfo_chapter'] ) : 0;
		$chapter    = $chapter_id ? get_post( $chapter_id ) : ( $chapters[0] ?? null );

		if ( $chapter && ( (int) get_post_meta( $chapter->ID, '_ipfo_guide_id', true ) !== $guide_id ) ) {
			$chapter = $chapters[0] ?? null;
		}

		$progress  = $progress_repo->get( $user_id, $guide_id );
		$bookmarks = $progress && $progress->bookmarks ? (array) json_decode( $progress->bookmarks, true ) : [];

		$logs->log( 'guide_view', $user_id, 'guide', $guide_id, [ 'chapter_id' => $chapter?->ID ] );

		$download_url = '';
		if ( $meta['download_enabled'] && $meta['pdf_resource_id'] && $access->can_access_resource( $user_id, $meta['pdf_resource_id'] ) ) {
			$download_url = ResourceDownloadService::build_url( $meta['pdf_resource_id'], true );
		}

		return $this->wrap(
			ipfo_render_template(
				'guide-reader.php',
				[
					'guide'          => get_post( $guide_id ),
					'meta'           => $meta,
					'chapters'       => $chapters,
					'chapter'        => $chapter,
					'bookmarks'      => array_map( 'intval', $bookmarks ),
					'progress'       => $progress ? (int) $progress->progress_percent : 0,
					'reading_time'   => $guide_service->reading_time_minutes( $guide_id ),
					'requires_ack'   => $ack_service->requires_acknowledgement( $user_id, $guide_id ),
					'download_url'   => $download_url,
					'disclaimer'     => ipfo_disclaimer_text(),
				]
			),
			'ipfo-guide'
		);
	}
}
