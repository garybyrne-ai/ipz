<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Repositories\InvitationRepository;
use IPFO\Repositories\CountryRepository;
use IPFO\Repositories\AccessLogRepository;
use IPFO\Security\Tokens;
use IPFO\Security\RateLimiter;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InvitationService {

	private InvitationRepository $invitations;
	private CountryRepository $countries;
	private AccessLogRepository $logs;
	private AccessControlService $access;

	public function __construct() {
		$this->invitations = new InvitationRepository();
		$this->countries   = new CountryRepository();
		$this->logs        = new AccessLogRepository();
		$this->access      = new AccessControlService();
	}

	/**
	 * @return array{0:int,1:string,2:string} [invitation_id, code, plaintext_token]
	 */
	public function create( array $data ): array {
		$country_code = '';
		if ( ! empty( $data['country_id'] ) ) {
			$country = $this->countries->get( (int) $data['country_id'] );
			$country_code = $country->code ?? '';
		}

		$code  = Tokens::invitation_code( $country_code );
		$token = Tokens::secret_token();

		$id = $this->invitations->create(
			[
				'code'               => $code,
				'token_hash'         => Tokens::hash( $token ),
				'country_id'         => $data['country_id'] ?? null,
				'guide_id'           => $data['guide_id'] ?? null,
				'assigned_resources' => $data['assigned_resources'] ?? [],
				'max_uses'           => $data['max_uses'] ?? 1,
				'single_use'         => $data['single_use'] ?? true,
				'expires_at'         => $data['expires_at'] ?? null,
			]
		);

		return [ $id, $code, $token ];
	}

	public function build_link( string $code, string $token ): string {
		$register_page = ipfo_get_portal_page_url( 'register' );

		return add_query_arg(
			[
				'ipfo_invite' => rawurlencode( $code ),
				'ipfo_token'  => rawurlencode( $token ),
			],
			$register_page
		);
	}

	/**
	 * Validate a code + token pair presented on the registration form or
	 * invitation link. Returns the invitation row or a WP_Error — never
	 * reveals *why* a token failed beyond a generic message, to avoid
	 * leaking which codes exist.
	 */
	public function validate( string $code, string $token ): \stdClass|WP_Error {
		if ( ! RateLimiter::allow( 'invitation_validate', 10, 600 ) ) {
			return new WP_Error( 'ipfo_rate_limited', __( 'Too many attempts. Please try again later.', 'ipfo-country-guidance-portal' ) );
		}

		$invitation = $this->invitations->get_by_code( sanitize_text_field( $code ) );

		if ( ! $invitation || ! hash_equals( $invitation->token_hash, Tokens::hash( $token ) ) ) {
			return new WP_Error( 'ipfo_invalid_invitation', __( 'This invitation link is invalid.', 'ipfo-country-guidance-portal' ) );
		}

		if ( 'active' !== $invitation->status ) {
			return new WP_Error( 'ipfo_invitation_unavailable', __( 'This invitation is no longer available.', 'ipfo-country-guidance-portal' ) );
		}

		if ( $invitation->expires_at && strtotime( $invitation->expires_at ) < time() ) {
			return new WP_Error( 'ipfo_invitation_expired', __( 'This invitation has expired.', 'ipfo-country-guidance-portal' ) );
		}

		if ( (int) $invitation->use_count >= (int) $invitation->max_uses ) {
			return new WP_Error( 'ipfo_invitation_exhausted', __( 'This invitation has already been used.', 'ipfo-country-guidance-portal' ) );
		}

		return $invitation;
	}

	/**
	 * Grant the access described by an invitation to a freshly registered
	 * or existing user, then mark the invitation consumed.
	 */
	public function redeem( object $invitation, int $user_id ): void {
		if ( $invitation->country_id ) {
			$this->access->grant_country( $user_id, (int) $invitation->country_id, (int) $invitation->created_by );
		}

		if ( $invitation->guide_id ) {
			$this->access->grant_guide( $user_id, (int) $invitation->guide_id, (int) $invitation->created_by );
		}

		if ( $invitation->assigned_resources ) {
			foreach ( (array) json_decode( (string) $invitation->assigned_resources, true ) as $resource_id ) {
				$this->access->grant_resource( $user_id, (int) $resource_id, (int) $invitation->created_by );
			}
		}

		$this->invitations->increment_use( (int) $invitation->id );

		$this->logs->log( 'invitation_use', $user_id, 'invitation', (int) $invitation->id );
	}

	public function revoke( int $id ): bool {
		return $this->invitations->revoke( $id );
	}

	public function all( array $args = [] ): array {
		return $this->invitations->all( $args );
	}

	public function expiring_soon( int $days = 7 ): array {
		return $this->invitations->expiring_within_days( $days );
	}

	public function run_daily_maintenance(): void {
		$this->invitations->mark_expired_due();
	}
}
