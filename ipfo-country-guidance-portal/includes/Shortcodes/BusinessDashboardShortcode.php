<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Services\BusinessDirectoryService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BusinessDashboardShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_business_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$service = new BusinessDirectoryService();
		$user_id = get_current_user_id();
		$notice  = '';
		$error   = '';

		if ( isset( $_POST['ipfo_action'] ) && 'business_update' === $_POST['ipfo_action'] ) {
			[ $notice, $error ] = $this->handle_submit( $service, $user_id );
		}

		$listing = $service->get_my_listing( $user_id );

		if ( ! $listing ) {
			return $this->wrap( '<div class="ipfo-empty-state">' . esc_html__( 'No business listing was found for your account. Please contact us if this seems wrong.', 'ipfo-country-guidance-portal' ) . '</div>' );
		}

		$country_ids = (array) json_decode( (string) get_post_meta( $listing->ID, '_ipfo_country_ids', true ), true );
		$category_id = (int) current( wp_get_post_terms( $listing->ID, \IPFO\CPT\BusinessCPT::TAXONOMY, [ 'fields' => 'ids' ] ) ?: [] );

		return $this->wrap(
			ipfo_render_template(
				'business-dashboard.php',
				[
					'notice'      => $notice,
					'error'       => $error,
					'listing'     => $listing,
					'status'      => $listing->post_status,
					'category_id' => $category_id,
					'country_ids' => array_map( 'intval', $country_ids ),
					'categories'  => $service->categories(),
					'countries'   => $service->countries(),
					'listing_url' => 'publish' === $listing->post_status ? get_permalink( $listing ) : '',
					'phone'       => get_post_meta( $listing->ID, '_ipfo_phone', true ),
					'email'       => get_post_meta( $listing->ID, '_ipfo_email', true ),
					'website'     => get_post_meta( $listing->ID, '_ipfo_website', true ),
					'address'     => get_post_meta( $listing->ID, '_ipfo_address', true ),
					'facebook'    => get_post_meta( $listing->ID, '_ipfo_social_facebook', true ),
					'instagram'   => get_post_meta( $listing->ID, '_ipfo_social_instagram', true ),
					'linkedin'    => get_post_meta( $listing->ID, '_ipfo_social_linkedin', true ),
					'credentials' => get_post_meta( $listing->ID, '_ipfo_credentials', true ),
				]
			)
		);
	}

	/** @return array{0:string,1:string} [notice, error] */
	private function handle_submit( BusinessDirectoryService $service, int $user_id ): array {
		if ( ! isset( $_POST['ipfo_business_dashboard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_business_dashboard_nonce'] ) ), 'ipfo_business_dashboard' ) ) {
			return [ '', __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' ) ];
		}

		$fields = [
			'business_name' => sanitize_text_field( wp_unslash( $_POST['ipfo_business_name'] ?? '' ) ),
			'description'   => wp_kses_post( wp_unslash( $_POST['ipfo_description'] ?? '' ) ),
			'category_id'   => absint( $_POST['ipfo_category_id'] ?? 0 ),
			'country_ids'   => array_map( 'absint', (array) ( $_POST['ipfo_country_ids'] ?? [] ) ),
			'phone'         => sanitize_text_field( wp_unslash( $_POST['ipfo_phone'] ?? '' ) ),
			'email'         => sanitize_email( wp_unslash( $_POST['ipfo_email'] ?? '' ) ),
			'website'       => esc_url_raw( wp_unslash( $_POST['ipfo_website'] ?? '' ) ),
			'address'       => sanitize_textarea_field( wp_unslash( $_POST['ipfo_address'] ?? '' ) ),
			'facebook'      => esc_url_raw( wp_unslash( $_POST['ipfo_facebook'] ?? '' ) ),
			'instagram'     => esc_url_raw( wp_unslash( $_POST['ipfo_instagram'] ?? '' ) ),
			'linkedin'      => esc_url_raw( wp_unslash( $_POST['ipfo_linkedin'] ?? '' ) ),
			'credentials'   => sanitize_textarea_field( wp_unslash( $_POST['ipfo_credentials'] ?? '' ) ),
		];

		if ( ! empty( $_FILES['ipfo_logo']['name'] ) ) {
			$attachment_id = $this->handle_logo_upload( $service, $user_id );
			if ( is_wp_error( $attachment_id ) ) {
				return [ '', $attachment_id->get_error_message() ];
			}
			$fields['logo_attachment_id'] = $attachment_id;
		}

		$result = $service->update_my_listing( $user_id, $fields );

		if ( is_wp_error( $result ) ) {
			return [ '', $result->get_error_message() ];
		}

		return [ __( 'Your listing has been updated.', 'ipfo-country-guidance-portal' ), '' ];
	}

	private function handle_logo_upload( BusinessDirectoryService $service, int $user_id ): int|\WP_Error {
		$listing = $service->get_my_listing( $user_id );
		if ( ! $listing ) {
			return new \WP_Error( 'ipfo_no_listing', __( 'No business listing was found for your account.', 'ipfo-country-guidance-portal' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'ipfo_logo', $listing->ID );

		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_Error( 'ipfo_upload_failed', __( 'There was a problem uploading your logo. Please try a JPG or PNG under 5MB.', 'ipfo-country-guidance-portal' ) );
		}

		return (int) $attachment_id;
	}
}
