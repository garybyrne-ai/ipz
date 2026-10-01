<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Security\RateLimiter;
use IPFO\Services\NotificationService;
use IPFO\Services\RegistrationService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LoginShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$this->maybe_handle_verification();

		if ( is_user_logged_in() ) {
			return $this->wrap( ipfo_render_template( 'already-logged-in.php', [ 'dashboard_url' => ipfo_get_portal_page_url( 'dashboard' ) ] ) );
		}

		if ( ( isset( $_GET['ipfo_reset_login'], $_GET['ipfo_reset_key'] ) || ( isset( $_POST['ipfo_action'] ) && 'reset_password' === $_POST['ipfo_action'] ) ) ) {
			return $this->wrap( $this->render_reset_password() );
		}

		$error   = '';
		$notice  = '';

		if ( isset( $_POST['ipfo_action'] ) && 'login' === $_POST['ipfo_action'] ) {
			$error = $this->handle_login();
		} elseif ( isset( $_POST['ipfo_action'] ) && 'forgot_password' === $_POST['ipfo_action'] ) {
			$notice = $this->handle_forgot_password();
		}

		return $this->wrap(
			ipfo_render_template(
				'login-form.php',
				[
					'error'        => $error,
					'notice'       => $notice,
					'register_url' => ipfo_get_portal_page_url( 'register' ),
				]
			)
		);
	}

	private function maybe_handle_verification(): void {
		if ( empty( $_GET['ipfo_verify_user'] ) || empty( $_GET['ipfo_verify_token'] ) ) {
			return;
		}

		$user_id = absint( $_GET['ipfo_verify_user'] );
		$token   = sanitize_text_field( wp_unslash( $_GET['ipfo_verify_token'] ) );

		( new RegistrationService() )->verify_email( $user_id, $token );
	}

	private function handle_login(): string {
		if ( ! isset( $_POST['ipfo_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_login_nonce'] ) ), 'ipfo_login' ) ) {
			return __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' );
		}

		if ( ! RateLimiter::allow( 'login', 8, 900 ) ) {
			return __( 'Too many sign-in attempts. Please wait a few minutes and try again.', 'ipfo-country-guidance-portal' );
		}

		$creds = [
			'user_login'    => sanitize_text_field( wp_unslash( $_POST['ipfo_email'] ?? '' ) ),
			'user_password' => (string) ( $_POST['ipfo_password'] ?? '' ),
			'remember'      => ! empty( $_POST['ipfo_remember'] ),
		];

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			return $this->generic_or_specific_error( $user );
		}

		wp_safe_redirect( ipfo_get_portal_page_url( 'dashboard' ) );
		exit;
	}

	private function generic_or_specific_error( \WP_Error $user ): string {
		// Our own guard_login() errors are safe/specific to show; WordPress's
		// own "incorrect password" errors are generalised to avoid user enumeration.
		$safe_codes = [ 'ipfo_unverified', 'ipfo_not_approved' ];

		if ( in_array( $user->get_error_code(), $safe_codes, true ) ) {
			return $user->get_error_message();
		}

		return __( 'The email or password you entered is incorrect.', 'ipfo-country-guidance-portal' );
	}

	private function handle_forgot_password(): string {
		if ( ! isset( $_POST['ipfo_forgot_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_forgot_nonce'] ) ), 'ipfo_forgot_password' ) ) {
			return __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' );
		}

		if ( ! RateLimiter::allow( 'forgot_password', 5, 900 ) ) {
			return __( 'Too many requests. Please try again later.', 'ipfo-country-guidance-portal' );
		}

		$email = sanitize_email( wp_unslash( $_POST['ipfo_email'] ?? '' ) );
		$user  = get_user_by( 'email', $email );

		if ( $user ) {
			$key = get_password_reset_key( $user );
			if ( ! is_wp_error( $key ) ) {
				$link = add_query_arg(
					[
						'ipfo_reset_login' => rawurlencode( $user->user_login ),
						'ipfo_reset_key'   => rawurlencode( $key ),
					],
					ipfo_get_portal_page_url( 'login' )
				);

				( new NotificationService() )->password_reset( $user, $link );
			}
		}

		// Always the same message, whether or not the account exists.
		return __( 'If an account exists for that email address, a password reset link has been sent.', 'ipfo-country-guidance-portal' );
	}

	private function render_reset_password(): string {
		$login = sanitize_text_field( wp_unslash( $_REQUEST['ipfo_reset_login'] ?? '' ) );
		$key   = sanitize_text_field( wp_unslash( $_REQUEST['ipfo_reset_key'] ?? '' ) );

		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			return '<div class="ipfo-card ipfo-auth-card"><p class="ipfo-form-error">' . esc_html__( 'This password reset link is invalid or has expired.', 'ipfo-country-guidance-portal' ) . '</p></div>';
		}

		$error = '';

		if ( isset( $_POST['ipfo_action'] ) && 'reset_password' === $_POST['ipfo_action'] ) {
			if ( ! isset( $_POST['ipfo_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_reset_nonce'] ) ), 'ipfo_reset_password' ) ) {
				$error = __( 'Your session expired. Please try again.', 'ipfo-country-guidance-portal' );
			} else {
				$password = (string) ( $_POST['ipfo_password'] ?? '' );
				$confirm  = (string) ( $_POST['ipfo_password_confirm'] ?? '' );

				if ( strlen( $password ) < 10 || $password !== $confirm ) {
					$error = __( 'Please enter matching passwords of at least 10 characters.', 'ipfo-country-guidance-portal' );
				} else {
					reset_password( $user, $password );
					return '<div class="ipfo-card ipfo-auth-card"><p class="ipfo-form-success">' . esc_html__( 'Your password has been reset. You can now sign in.', 'ipfo-country-guidance-portal' ) . '</p><p><a class="ipfo-btn ipfo-btn--primary" href="' . esc_url( ipfo_get_portal_page_url( 'login' ) ) . '">' . esc_html__( 'Sign In', 'ipfo-country-guidance-portal' ) . '</a></p></div>';
				}
			}
		}

		return ipfo_render_template(
			'reset-password-form.php',
			[
				'error' => $error,
				'login' => $login,
				'key'   => $key,
			]
		);
	}
}
