<?php
declare( strict_types = 1 );

namespace IPFO\Privacy;

use IPFO\Database\Schema;
use IPFO\Repositories\AcknowledgementRepository;
use IPFO\Repositories\GuideProgressRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the portal's own data into WordPress's native Export Personal Data
 * / Erase Personal Data tools (Tools > Export/Erase Personal Data), so site
 * administrators handle GDPR subject requests the same way for every
 * plugin on the site rather than through a bespoke IPFO-only flow.
 */
final class GdprHandler {

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_eraser' ] );
	}

	public function register_exporter( array $exporters ): array {
		$exporters['ipfo-country-guidance-portal'] = [
			'exporter_friendly_name' => __( 'IPFO Guidance Portal', 'ipfo-country-guidance-portal' ),
			'callback'               => [ $this, 'export' ],
		];

		return $exporters;
	}

	public function register_eraser( array $erasers ): array {
		$erasers['ipfo-country-guidance-portal'] = [
			'eraser_friendly_name' => __( 'IPFO Guidance Portal', 'ipfo-country-guidance-portal' ),
			'callback'             => [ $this, 'erase' ],
		];

		return $erasers;
	}

	public function export( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return [ 'data' => [], 'done' => true ];
		}

		$items = [];

		$items[] = [
			'group_id'    => 'ipfo_profile',
			'group_label' => __( 'IPFO Portal Profile', 'ipfo-country-guidance-portal' ),
			'item_id'     => 'ipfo-profile-' . $user->ID,
			'data'        => [
				[ 'name' => __( 'Phone', 'ipfo-country-guidance-portal' ), 'value' => get_user_meta( $user->ID, '_ipfo_phone', true ) ],
				[ 'name' => __( 'Consent recorded at', 'ipfo-country-guidance-portal' ), 'value' => get_user_meta( $user->ID, '_ipfo_consent_at', true ) ],
			],
		];

		foreach ( ( new AcknowledgementRepository() )->for_user( $user->ID ) as $ack ) {
			$items[] = [
				'group_id'    => 'ipfo_acknowledgements',
				'group_label' => __( 'Guidance Acknowledgements', 'ipfo-country-guidance-portal' ),
				'item_id'     => 'ipfo-ack-' . $ack->id,
				'data'        => [
					[ 'name' => __( 'Guide', 'ipfo-country-guidance-portal' ), 'value' => get_the_title( (int) $ack->guide_id ) ],
					[ 'name' => __( 'Version', 'ipfo-country-guidance-portal' ), 'value' => $ack->version ],
					[ 'name' => __( 'Acknowledged At', 'ipfo-country-guidance-portal' ), 'value' => $ack->acknowledged_at ],
				],
			];
		}

		foreach ( ( new GuideProgressRepository() )->for_user( $user->ID ) as $progress ) {
			$items[] = [
				'group_id'    => 'ipfo_progress',
				'group_label' => __( 'Reading Progress', 'ipfo-country-guidance-portal' ),
				'item_id'     => 'ipfo-progress-' . $progress->id,
				'data'        => [
					[ 'name' => __( 'Guide', 'ipfo-country-guidance-portal' ), 'value' => get_the_title( (int) $progress->guide_id ) ],
					[ 'name' => __( 'Progress', 'ipfo-country-guidance-portal' ), 'value' => $progress->progress_percent . '%' ],
				],
			];
		}

		return [ 'data' => $items, 'done' => true ];
	}

	public function erase( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return [ 'items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true ];
		}

		global $wpdb;
		$removed = 0;

		foreach ( [ 'guide_progress', 'checklist_progress', 'acknowledgements', 'user_access' ] as $table ) {
			$removed += (int) $wpdb->delete( Schema::table( $table ), [ 'user_id' => $user->ID ], [ '%d' ] );
		}

		delete_user_meta( $user->ID, '_ipfo_phone' );
		delete_user_meta( $user->ID, '_ipfo_consent_at' );
		delete_user_meta( $user->ID, '_ipfo_terms_accepted_at' );

		return [
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => [],
			'done'           => true,
		];
	}
}
