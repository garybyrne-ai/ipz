<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Capabilities;
use IPFO\Security\RateLimiter;
use IPFO\Security\Tokens;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end client registration: validation, account creation, optional
 * email verification, optional admin approval, and invitation redemption.
 */
final class RegistrationService {

	private AccessControlService $access;
	private NotificationService $notifications;
	private InvitationService $invitations;

	public function __construct() {
		$this->access        = new AccessControlService();
		$this->notifications = new NotificationService();
		$this->invitations   = new InvitationService();
	}

	/**
	 * @param array{first_name:string,last_name:string,email:string,password:string,country_id:int,phone:string,consent:bool,terms:bool} $fields
	 */
	public function register( array $fields, string $invite_code = '', string $invite_token = '' ): WP_User|WP_Error {
		if ( ! RateLimiter::allow( 'register', 5, 900 ) ) {
			return new WP_Error( 'ipfo_rate_limited', __( 'Too many registration attempts. Please try again later.', 'ipfo-country-guidance-portal' ) );
		}

		$error = $this->validate( $fields );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$invitation = null;
		if ( $invite_code && $invite_token ) {
			$result = $this->invitations->validate( $invite_code, $invite_token );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$invitation = $result;
		}

		$email = sanitize_email( $fields['email'] );

		$username = $this->unique_username( $email );

		$user_id = wp_insert_user(
			[
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $fields['password'],
				'first_name'   => sanitize_text_field( $fields['first_name'] ),
				'last_name'    => sanitize_text_field( $fields['last_name'] ),
				'display_name' => trim( sanitize_text_field( $fields['first_name'] ) . ' ' . sanitize_text_field( $fields['last_name'] ) ),
				'role'         => Capabilities::CLIENT_ROLE,
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, '_ipfo_phone', sanitize_text_field( $fields['phone'] ?? '' ) );
		update_user_meta( $user_id, '_ipfo_consent_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, '_ipfo_terms_accepted_at', current_time( 'mysql' ) );

		$country_id = (int) ( $invitation->country_id ?? $fields['country_id'] ?? 0 );
		if ( $country_id ) {
			$this->access->set_user_country( $user_id, $country_id );
		}

		$require_verification = '1' === get_option( 'ipfo_require_email_verification', '1' );
		$require_approval     = '1' === get_option( 'ipfo_require_admin_approval', '0' );

		update_user_meta( $user_id, '_ipfo_email_verified', $require_verification ? '0' : '1' );
		update_user_meta( $user_id, '_ipfo_approval_status', $require_approval ? 'pending' : 'approved' );

		if ( $invitation ) {
			$this->invitations->redeem( $invitation, $user_id );
		}

		$user = get_userdata( $user_id );

		if ( $require_verification ) {
			$this->send_verification_email( $user );
		}

		$this->notifications->registration_received( $user );

		return $user;
	}

	private function validate( array $fields ): true|WP_Error {
		if ( empty( $fields['first_name'] ) || empty( $fields['last_name'] ) ) {
			return new WP_Error( 'ipfo_missing_name', __( 'Please provide your first and last name.', 'ipfo-country-guidance-portal' ) );
		}

		if ( empty( $fields['email'] ) || ! is_email( $fields['email'] ) ) {
			return new WP_Error( 'ipfo_invalid_email', __( 'Please provide a valid email address.', 'ipfo-country-guidance-portal' ) );
		}

		if ( email_exists( sanitize_email( $fields['email'] ) ) ) {
			return new WP_Error( 'ipfo_email_exists', __( 'An account already exists with this email address.', 'ipfo-country-guidance-portal' ) );
		}

		if ( empty( $fields['password'] ) || strlen( $fields['password'] ) < 10 ) {
			return new WP_Error( 'ipfo_weak_password', __( 'Please choose a password of at least 10 characters.', 'ipfo-country-guidance-portal' ) );
		}

		if ( empty( $fields['consent'] ) || empty( $fields['terms'] ) ) {
			return new WP_Error( 'ipfo_consent_required', __( 'Please accept the privacy policy and terms to continue.', 'ipfo-country-guidance-portal' ) );
		}

		return true;
	}

	private function unique_username( string $email ): string {
		$base = sanitize_user( current( explode( '@', $email ) ), true ) ?: 'ip';
		$username = $base;
		$i = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $i;
			$i++;
		}

		return $username;
	}

	public function send_verification_email( WP_User $user ): void {
		$token = Tokens::secret_token();
		update_user_meta( $user->ID, '_ipfo_verify_token_hash', Tokens::hash( $token ) );
		update_user_meta( $user->ID, '_ipfo_verify_expires', time() + ( 2 * DAY_IN_SECONDS ) );

		$link = add_query_arg(
			[
				'ipfo_verify_user'  => $user->ID,
				'ipfo_verify_token' => $token,
			],
			ipfo_get_portal_page_url( 'login' )
		);

		$this->notifications->verify_email( $user, $link );
	}

	public function verify_email( int $user_id, string $token ): bool {
		$hash    = get_user_meta( $user_id, '_ipfo_verify_token_hash', true );
		$expires = (int) get_user_meta( $user_id, '_ipfo_verify_expires', true );

		if ( ! $hash || ! hash_equals( (string) $hash, Tokens::hash( $token ) ) ) {
			return false;
		}

		if ( $expires && time() > $expires ) {
			return false;
		}

		update_user_meta( $user_id, '_ipfo_email_verified', '1' );
		delete_user_meta( $user_id, '_ipfo_verify_token_hash' );
		delete_user_meta( $user_id, '_ipfo_verify_expires' );

		if ( 'approved' === get_user_meta( $user_id, '_ipfo_approval_status', true ) ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$this->notifications->account_approved( $user, ipfo_get_portal_page_url( 'login' ) );
			}
		}

		return true;
	}

	public function approve( int $user_id ): void {
		update_user_meta( $user_id, '_ipfo_approval_status', 'approved' );

		$user = get_userdata( $user_id );
		if ( $user && '1' === get_user_meta( $user_id, '_ipfo_email_verified', true ) ) {
			$this->notifications->account_approved( $user, ipfo_get_portal_page_url( 'login' ) );
		}
	}

	public function suspend( int $user_id ): void {
		update_user_meta( $user_id, '_ipfo_approval_status', 'suspended' );
		$this->access->suspend_all( $user_id );
	}

	public function is_ready_to_login( int $user_id ): bool {
		$verified = '1' === get_user_meta( $user_id, '_ipfo_email_verified', true );
		$approved = 'approved' === get_user_meta( $user_id, '_ipfo_approval_status', true );

		return $verified && $approved;
	}

	/**
	 * Hooked to wp_authenticate_user so a client cannot log in before their
	 * email is verified and/or their account is approved, when those
	 * safeguards are enabled by the administrator.
	 */
	public static function guard_login( WP_User|WP_Error $user, string $password ): WP_User|WP_Error {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( ! in_array( Capabilities::CLIENT_ROLE, (array) $user->roles, true ) ) {
			return $user;
		}

		if ( '1' !== get_user_meta( $user->ID, '_ipfo_email_verified', true ) ) {
			return new WP_Error( 'ipfo_unverified', __( 'Please verify your email address before signing in. Check your inbox for the verification link.', 'ipfo-country-guidance-portal' ) );
		}

		$status = get_user_meta( $user->ID, '_ipfo_approval_status', true );
		if ( 'approved' !== $status ) {
			return new WP_Error( 'ipfo_not_approved', __( 'Your account is still being prepared. We will notify you by email once it is ready.', 'ipfo-country-guidance-portal' ) );
		}

		return $user;
	}
}
