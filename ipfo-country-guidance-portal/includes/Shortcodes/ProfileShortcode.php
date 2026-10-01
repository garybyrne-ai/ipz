<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Repositories\CountryRepository;
use IPFO\Services\AccessControlService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ProfileShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$user    = wp_get_current_user();
		$notice  = '';
		$error   = '';

		if ( isset( $_POST['ipfo_action'] ) ) {
			switch ( $_POST['ipfo_action'] ) {
				case 'update_profile':
					[ $notice, $error ] = $this->handle_profile_update( $user );
					break;
				case 'change_password':
					[ $notice, $error ] = $this->handle_password_change( $user );
					break;
				case 'gdpr_export':
					$notice = $this->handle_gdpr_request( $user, 'export_personal_data' );
					break;
				case 'gdpr_erase':
					$notice = $this->handle_gdpr_request( $user, 'remove_personal_data' );
					break;
			}
		}

		$country_id = ( new AccessControlService() )->user_country_id( $user->ID );
		$country    = $country_id ? ( new CountryRepository() )->get( $country_id ) : null;

		return $this->wrap(
			ipfo_render_template(
				'profile.php',
				[
					'user'    => $user,
					'country' => $country,
					'phone'   => get_user_meta( $user->ID, '_ipfo_phone', true ),
					'notice'  => $notice,
					'error'   => $error,
				]
			),
			'ipfo-profile'
		);
	}

	private function handle_profile_update( \WP_User $user ): array {
		if ( ! isset( $_POST['ipfo_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_profile_nonce'] ) ), 'ipfo_profile' ) ) {
			return [ '', __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' ) ];
		}

		wp_update_user(
			[
				'ID'         => $user->ID,
				'first_name' => sanitize_text_field( wp_unslash( $_POST['ipfo_first_name'] ?? '' ) ),
				'last_name'  => sanitize_text_field( wp_unslash( $_POST['ipfo_last_name'] ?? '' ) ),
			]
		);

		update_user_meta( $user->ID, '_ipfo_phone', sanitize_text_field( wp_unslash( $_POST['ipfo_phone'] ?? '' ) ) );

		return [ __( 'Your profile has been updated.', 'ipfo-country-guidance-portal' ), '' ];
	}

	private function handle_password_change( \WP_User $user ): array {
		if ( ! isset( $_POST['ipfo_password_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_password_nonce'] ) ), 'ipfo_change_password' ) ) {
			return [ '', __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' ) ];
		}

		$current = (string) ( $_POST['ipfo_current_password'] ?? '' );
		$new     = (string) ( $_POST['ipfo_new_password'] ?? '' );
		$confirm = (string) ( $_POST['ipfo_new_password_confirm'] ?? '' );

		if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
			return [ '', __( 'Your current password is incorrect.', 'ipfo-country-guidance-portal' ) ];
		}

		if ( strlen( $new ) < 10 || $new !== $confirm ) {
			return [ '', __( 'Please enter matching new passwords of at least 10 characters.', 'ipfo-country-guidance-portal' ) ];
		}

		wp_set_password( $new, $user->ID );

		return [ __( 'Your password has been changed. Please sign in again.', 'ipfo-country-guidance-portal' ), '' ];
	}

	private function handle_gdpr_request( \WP_User $user, string $action_name ): string {
		if ( ! isset( $_POST['ipfo_gdpr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_gdpr_nonce'] ) ), 'ipfo_gdpr' ) ) {
			return __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' );
		}

		$request_id = wp_create_user_request( $user->user_email, $action_name );

		if ( is_wp_error( $request_id ) ) {
			return __( 'A request of this type is already pending for your account.', 'ipfo-country-guidance-portal' );
		}

		wp_send_user_request( $request_id );

		return __( 'Your request has been submitted. Please check your email to confirm it.', 'ipfo-country-guidance-portal' );
	}
}
