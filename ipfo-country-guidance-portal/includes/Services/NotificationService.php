<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Emails\Mailer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps portal events to branded emails. Centralising this makes every
 * notification point in the spec (registration, verification, approval,
 * new guide, update, acknowledgement required, invitation, password reset)
 * a one-line call from the service that triggers the event.
 */
final class NotificationService {

	public function invitation( string $email, string $link, string $code ): void {
		$body = sprintf(
			'<p>%s</p><p style="font-family:Manrope,monospace;font-size:18px;letter-spacing:1px;">%s</p>',
			esc_html__( 'Please use this secure link to access your IP Fertility Options guidance.', 'ipfo-country-guidance-portal' ),
			esc_html( $code )
		);

		Mailer::send(
			$email,
			__( 'Your secure IP Fertility Options invitation', 'ipfo-country-guidance-portal' ),
			__( 'You have been invited', 'ipfo-country-guidance-portal' ),
			$body,
			__( 'Accept Invitation', 'ipfo-country-guidance-portal' ),
			$link
		);
	}

	public function registration_received( \WP_User $user ): void {
		Mailer::send(
			$user->user_email,
			__( 'Thank you for registering', 'ipfo-country-guidance-portal' ),
			__( 'Thank you. Your account is being prepared.', 'ipfo-country-guidance-portal' ),
			'<p>' . esc_html__( 'We have received your registration for the IP Fertility Options Guidance Centre. We will be in touch shortly.', 'ipfo-country-guidance-portal' ) . '</p>'
		);
	}

	public function verify_email( \WP_User $user, string $verify_link ): void {
		Mailer::send(
			$user->user_email,
			__( 'Confirm your email address', 'ipfo-country-guidance-portal' ),
			__( 'Please confirm your email address', 'ipfo-country-guidance-portal' ),
			'<p>' . esc_html__( 'Please confirm your email address to activate your IP Fertility Options Guidance Centre account.', 'ipfo-country-guidance-portal' ) . '</p>',
			__( 'Verify Email', 'ipfo-country-guidance-portal' ),
			$verify_link
		);
	}

	public function account_approved( \WP_User $user, string $login_link ): void {
		Mailer::send(
			$user->user_email,
			__( 'Your account is ready', 'ipfo-country-guidance-portal' ),
			__( 'Your Guidance Centre account is ready', 'ipfo-country-guidance-portal' ),
			'<p>' . esc_html__( 'Your account has been approved. You can now sign in to your private Guidance Centre.', 'ipfo-country-guidance-portal' ) . '</p>',
			__( 'Sign In', 'ipfo-country-guidance-portal' ),
			$login_link
		);
	}

	public function guide_assigned( \WP_User $user, string $guide_title, string $guide_link ): void {
		Mailer::send(
			$user->user_email,
			__( 'New guidance available', 'ipfo-country-guidance-portal' ),
			__( 'A new guide has been assigned to you', 'ipfo-country-guidance-portal' ),
			'<p>' . sprintf( esc_html__( '%s is now available in your Guidance Centre.', 'ipfo-country-guidance-portal' ), esc_html( $guide_title ) ) . '</p>',
			__( 'Open Guide', 'ipfo-country-guidance-portal' ),
			$guide_link
		);
	}

	public function guide_updated( \WP_User $user, string $guide_title, string $version, string $guide_link ): void {
		Mailer::send(
			$user->user_email,
			__( 'Updated guidance available', 'ipfo-country-guidance-portal' ),
			__( 'Updated Guidance Available', 'ipfo-country-guidance-portal' ),
			'<p>' . sprintf( esc_html__( '%1$s has been updated to version %2$s.', 'ipfo-country-guidance-portal' ), esc_html( $guide_title ), esc_html( $version ) ) . '</p>',
			__( 'View Update', 'ipfo-country-guidance-portal' ),
			$guide_link
		);
	}

	public function acknowledgement_required( \WP_User $user, string $guide_title, string $guide_link ): void {
		Mailer::send(
			$user->user_email,
			__( 'Action required: please acknowledge updated guidance', 'ipfo-country-guidance-portal' ),
			__( 'Acknowledgement required', 'ipfo-country-guidance-portal' ),
			'<p>' . sprintf( esc_html__( 'Please read and acknowledge the latest version of %s to continue.', 'ipfo-country-guidance-portal' ), esc_html( $guide_title ) ) . '</p>',
			__( 'Review Guide', 'ipfo-country-guidance-portal' ),
			$guide_link
		);
	}

	public function password_reset( \WP_User $user, string $reset_link ): void {
		Mailer::send(
			$user->user_email,
			__( 'Reset your password', 'ipfo-country-guidance-portal' ),
			__( 'Reset your password', 'ipfo-country-guidance-portal' ),
			'<p>' . esc_html__( 'A password reset was requested for your account. If this was not you, you can ignore this email.', 'ipfo-country-guidance-portal' ) . '</p>',
			__( 'Reset Password', 'ipfo-country-guidance-portal' ),
			$reset_link
		);
	}
}
