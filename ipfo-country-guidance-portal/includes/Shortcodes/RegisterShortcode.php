<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Repositories\CountryRepository;
use IPFO\Services\RegistrationService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RegisterShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		if ( is_user_logged_in() ) {
			return $this->wrap( ipfo_render_template( 'already-logged-in.php', [ 'dashboard_url' => ipfo_get_portal_page_url( 'dashboard' ) ] ) );
		}

		$invite_code  = isset( $_REQUEST['ipfo_invite'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ipfo_invite'] ) ) : '';
		$invite_token = isset( $_REQUEST['ipfo_token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ipfo_token'] ) ) : '';

		$error   = '';
		$success = false;

		if ( isset( $_POST['ipfo_action'] ) && 'register' === $_POST['ipfo_action'] ) {
			$result = $this->handle_submit();

			if ( is_wp_error( $result ) ) {
				$error = $result->get_error_message();
			} else {
				$success = true;
			}
		}

		if ( $success ) {
			return $this->wrap(
				'<div class="ipfo-card ipfo-auth-card" style="text-align:center;"><h2>' . esc_html__( 'Thank you.', 'ipfo-country-guidance-portal' ) . '</h2><p>' . esc_html__( 'Your account is being prepared.', 'ipfo-country-guidance-portal' ) . '</p></div>'
			);
		}

		return $this->wrap(
			ipfo_render_template(
				'register-form.php',
				[
					'error'        => $error,
					'countries'    => ( new CountryRepository() )->all( 'active' ),
					'invite_code'  => $invite_code,
					'invite_token' => $invite_token,
					'login_url'    => ipfo_get_portal_page_url( 'login' ),
					'disclaimer'   => ipfo_disclaimer_text(),
				]
			)
		);
	}

	private function handle_submit(): \WP_User|\WP_Error {
		if ( ! isset( $_POST['ipfo_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_register_nonce'] ) ), 'ipfo_register' ) ) {
			return new \WP_Error( 'ipfo_nonce', __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' ) );
		}

		$fields = [
			'first_name' => sanitize_text_field( wp_unslash( $_POST['ipfo_first_name'] ?? '' ) ),
			'last_name'  => sanitize_text_field( wp_unslash( $_POST['ipfo_last_name'] ?? '' ) ),
			'email'      => sanitize_email( wp_unslash( $_POST['ipfo_email'] ?? '' ) ),
			'password'   => (string) ( $_POST['ipfo_password'] ?? '' ),
			'phone'      => sanitize_text_field( wp_unslash( $_POST['ipfo_phone'] ?? '' ) ),
			'country_id' => absint( $_POST['ipfo_country_id'] ?? 0 ),
			'consent'    => ! empty( $_POST['ipfo_consent'] ),
			'terms'      => ! empty( $_POST['ipfo_terms'] ),
		];

		$invite_code  = sanitize_text_field( wp_unslash( $_POST['ipfo_invite'] ?? '' ) );
		$invite_token = sanitize_text_field( wp_unslash( $_POST['ipfo_token'] ?? '' ) );

		return ( new RegistrationService() )->register( $fields, $invite_code, $invite_token );
	}
}
