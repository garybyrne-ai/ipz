<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\CountryRepository;
use IPFO\Repositories\UserAccessRepository;
use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for "is this logged-in user authorised to see this
 * content". Every shortcode, template, REST endpoint and download handler
 * must route through here rather than re-implementing its own checks, so
 * there is exactly one place to audit for IDOR-style mistakes.
 */
final class AccessControlService {

	private UserAccessRepository $access;
	private CountryRepository $countries;

	public function __construct() {
		$this->access    = new UserAccessRepository();
		$this->countries = new CountryRepository();
	}

	public function user_country_id( int $user_id ): ?int {
		$id = (int) get_user_meta( $user_id, '_ipfo_country_id', true );
		return $id > 0 ? $id : null;
	}

	public function set_user_country( int $user_id, int $country_id ): void {
		update_user_meta( $user_id, '_ipfo_country_id', $country_id );
		$this->access->grant( $user_id, UserAccessRepository::TYPE_COUNTRY, $country_id, get_current_user_id() ?: null );
	}

	/**
	 * Administrators and anyone holding manage_ipfo_portal always have full access.
	 */
	public function is_portal_admin( int $user_id = 0 ): bool {
		$user_id = $user_id ?: get_current_user_id();
		return user_can( $user_id, Capabilities::MANAGE_PORTAL );
	}

	public function can_access_country( int $user_id, int $country_id ): bool {
		if ( $this->is_portal_admin( $user_id ) ) {
			return true;
		}

		return $this->access->has_active_access( $user_id, UserAccessRepository::TYPE_COUNTRY, $country_id );
	}

	public function can_access_guide( int $user_id, int $guide_id ): bool {
		if ( $this->is_portal_admin( $user_id ) ) {
			return true;
		}

		if ( get_post_type( $guide_id ) !== CountryGuideCPT::SLUG || 'publish' !== get_post_status( $guide_id ) ) {
			return false;
		}

		if ( $this->access->has_active_access( $user_id, UserAccessRepository::TYPE_GUIDE, $guide_id ) ) {
			return true;
		}

		$country_id = (int) get_post_meta( $guide_id, '_ipfo_country_id', true );

		return $country_id > 0 && $this->can_access_country( $user_id, $country_id );
	}

	public function can_access_resource( int $user_id, int $resource_id ): bool {
		if ( $this->is_portal_admin( $user_id ) ) {
			return true;
		}

		if ( get_post_type( $resource_id ) !== ResourceCPT::SLUG || 'publish' !== get_post_status( $resource_id ) ) {
			return false;
		}

		$expires = get_post_meta( $resource_id, '_ipfo_expires_at', true );
		if ( $expires && strtotime( (string) $expires ) < time() ) {
			return false;
		}

		if ( $this->access->has_active_access( $user_id, UserAccessRepository::TYPE_RESOURCE, $resource_id ) ) {
			return true;
		}

		$guide_id = (int) get_post_meta( $resource_id, '_ipfo_guide_id', true );
		if ( $guide_id && $this->can_access_guide( $user_id, $guide_id ) ) {
			return true;
		}

		$country_id = (int) get_post_meta( $resource_id, '_ipfo_country_id', true );
		if ( $country_id && $this->can_access_country( $user_id, $country_id ) ) {
			return true;
		}

		// A resource with no country/guide/user scoping at all is treated as
		// portal-wide (available to any authenticated, authorised client).
		return ! $guide_id && ! $country_id && ! $this->access->find( $user_id, UserAccessRepository::TYPE_RESOURCE, $resource_id );
	}

	/** @return int[] Guide post IDs the user may access. */
	public function guide_ids_for_user( int $user_id ): array {
		if ( $this->is_portal_admin( $user_id ) ) {
			return get_posts(
				[
					'post_type'      => CountryGuideCPT::SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]
			);
		}

		$direct       = $this->access->object_ids_for_user( $user_id, UserAccessRepository::TYPE_GUIDE );
		$country_ids  = $this->access->object_ids_for_user( $user_id, UserAccessRepository::TYPE_COUNTRY );
		$from_country = [];

		if ( $country_ids ) {
			$from_country = get_posts(
				[
					'post_type'      => CountryGuideCPT::SLUG,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_query'     => [
						[
							'key'     => '_ipfo_country_id',
							'value'   => $country_ids,
							'compare' => 'IN',
						],
					],
				]
			);
		}

		return array_values( array_unique( array_map( 'intval', array_merge( $direct, $from_country ) ) ) );
	}

	/** @return int[] Resource post IDs the user may access. */
	public function resource_ids_for_user( int $user_id ): array {
		$all = get_posts(
			[
				'post_type'      => ResourceCPT::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		return array_values( array_filter( array_map( 'intval', $all ), fn( $id ) => $this->can_access_resource( $user_id, $id ) ) );
	}

	public function grant_country( int $user_id, int $country_id, ?int $granted_by = null ): void {
		$this->access->grant( $user_id, UserAccessRepository::TYPE_COUNTRY, $country_id, $granted_by );
	}

	public function grant_guide( int $user_id, int $guide_id, ?int $granted_by = null ): void {
		$this->access->grant( $user_id, UserAccessRepository::TYPE_GUIDE, $guide_id, $granted_by );
	}

	public function grant_resource( int $user_id, int $resource_id, ?int $granted_by = null ): void {
		$this->access->grant( $user_id, UserAccessRepository::TYPE_RESOURCE, $resource_id, $granted_by );
	}

	public function revoke( int $user_id, string $object_type, int $object_id ): void {
		$this->access->revoke( $user_id, $object_type, $object_id );
	}

	public function suspend_all( int $user_id ): void {
		$this->access->revoke_all_for_user( $user_id );
	}
}
