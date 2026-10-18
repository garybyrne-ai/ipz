<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\Capabilities;
use IPFO\CPT\BusinessCPT;
use IPFO\Repositories\AccessLogRepository;
use IPFO\Repositories\CountryRepository;
use IPFO\Security\RateLimiter;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Self-service for the public Business Directory: registration, the owner
 * editing their own listing, and the public search/filter query. A business
 * owner never receives a WordPress capability on the ipfo_business post
 * type (see Capabilities::BUSINESS_ROLE); every write here instead checks
 * post_author ownership itself before calling wp_update_post() directly —
 * the same pattern this plugin already uses for checklist/progress writes.
 */
final class BusinessDirectoryService {

	private InvitationService $invitations;
	private NotificationService $notifications;
	private AccessLogRepository $logs;

	public function __construct() {
		$this->invitations   = new InvitationService();
		$this->notifications = new NotificationService();
		$this->logs          = new AccessLogRepository();
	}

	/**
	 * @param array{business_name:string,first_name:string,last_name:string,email:string,password:string,category_id:int,country_ids:int[],phone:string,consent:bool,terms:bool} $fields
	 */
	public function register( array $fields, string $invite_code, string $invite_token ): WP_User|WP_Error {
		if ( ! RateLimiter::allow( 'business_register', 5, 900 ) ) {
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
			if ( 'business' !== $result->invite_type ) {
				return new WP_Error( 'ipfo_wrong_invitation_type', __( 'This invitation link is not valid for a business listing.', 'ipfo-country-guidance-portal' ) );
			}
			$invitation = $result;
		} else {
			return new WP_Error( 'ipfo_invitation_required', __( 'A valid invitation is required to create a business listing.', 'ipfo-country-guidance-portal' ) );
		}

		$email    = sanitize_email( $fields['email'] );
		$username = $this->unique_username( $email );

		$user_id = wp_insert_user(
			[
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $fields['password'],
				'first_name'   => sanitize_text_field( $fields['first_name'] ),
				'last_name'    => sanitize_text_field( $fields['last_name'] ),
				'display_name' => sanitize_text_field( $fields['business_name'] ),
				'role'         => Capabilities::BUSINESS_ROLE,
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, '_ipfo_phone', sanitize_text_field( $fields['phone'] ?? '' ) );
		update_user_meta( $user_id, '_ipfo_consent_at', current_time( 'mysql' ) );
		update_user_meta( $user_id, '_ipfo_terms_accepted_at', current_time( 'mysql' ) );

		$require_approval = '1' === get_option( 'ipfo_require_business_approval', '1' );

		$post_id = wp_insert_post(
			[
				'post_type'   => BusinessCPT::SLUG,
				'post_title'  => sanitize_text_field( $fields['business_name'] ),
				'post_status' => $require_approval ? 'pending' : 'publish',
				'post_author' => $user_id,
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$category_id = (int) ( $invitation->business_category ?? $fields['category_id'] ?? 0 );
		if ( $category_id ) {
			wp_set_post_terms( $post_id, [ $category_id ], BusinessCPT::TAXONOMY );
		}

		update_post_meta( $post_id, '_ipfo_country_ids', wp_json_encode( array_values( array_filter( array_map( 'absint', (array) ( $fields['country_ids'] ?? [] ) ) ) ) ) );
		update_post_meta( $post_id, '_ipfo_phone', sanitize_text_field( $fields['phone'] ?? '' ) );
		update_post_meta( $post_id, '_ipfo_email', $email );
		update_post_meta( $post_id, '_ipfo_featured', '0' );

		update_user_meta( $user_id, '_ipfo_business_post_id', $post_id );

		$this->invitations->mark_used( $invitation, $user_id );

		$user = get_userdata( $user_id );

		$this->notifications->business_registration_received( $user, $require_approval );
		$this->logs->log( 'business_registered', $user_id, 'ipfo_business', $post_id );

		return $user;
	}

	private function validate( array $fields ): true|WP_Error {
		if ( empty( $fields['business_name'] ) ) {
			return new WP_Error( 'ipfo_missing_business_name', __( 'Please provide your business name.', 'ipfo-country-guidance-portal' ) );
		}

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
		$base     = sanitize_user( current( explode( '@', $email ) ), true ) ?: 'biz';
		$username = $base;
		$i        = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $i;
			$i++;
		}

		return $username;
	}

	public function get_my_listing( int $user_id ): ?WP_Post {
		$post_id = (int) get_user_meta( $user_id, '_ipfo_business_post_id', true );
		if ( ! $post_id ) {
			return null;
		}

		$post = get_post( $post_id );

		return ( $post instanceof WP_Post && (int) $post->post_author === $user_id ) ? $post : null;
	}

	/**
	 * @param array{business_name:string,description:string,category_id:int,country_ids:int[],phone:string,email:string,website:string,address:string,facebook:string,instagram:string,linkedin:string,credentials:string} $fields
	 */
	public function update_my_listing( int $user_id, array $fields ): true|WP_Error {
		$post = $this->get_my_listing( $user_id );
		if ( ! $post ) {
			return new WP_Error( 'ipfo_no_listing', __( 'No business listing was found for your account.', 'ipfo-country-guidance-portal' ) );
		}

		$update = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_title'   => sanitize_text_field( $fields['business_name'] ?? $post->post_title ),
				'post_content' => wp_kses_post( $fields['description'] ?? '' ),
			],
			true
		);

		if ( is_wp_error( $update ) ) {
			return $update;
		}

		if ( ! empty( $fields['category_id'] ) ) {
			wp_set_post_terms( $post->ID, [ absint( $fields['category_id'] ) ], BusinessCPT::TAXONOMY );
		}

		update_post_meta( $post->ID, '_ipfo_country_ids', wp_json_encode( array_values( array_filter( array_map( 'absint', (array) ( $fields['country_ids'] ?? [] ) ) ) ) ) );
		update_post_meta( $post->ID, '_ipfo_phone', sanitize_text_field( $fields['phone'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_email', sanitize_email( $fields['email'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_website', esc_url_raw( $fields['website'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_address', sanitize_textarea_field( $fields['address'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_social_facebook', esc_url_raw( $fields['facebook'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_social_instagram', esc_url_raw( $fields['instagram'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_social_linkedin', esc_url_raw( $fields['linkedin'] ?? '' ) );
		update_post_meta( $post->ID, '_ipfo_credentials', sanitize_textarea_field( $fields['credentials'] ?? '' ) );

		if ( ! empty( $fields['logo_attachment_id'] ) ) {
			set_post_thumbnail( $post->ID, absint( $fields['logo_attachment_id'] ) );
		}

		$this->logs->log( 'business_listing_updated', $user_id, 'ipfo_business', $post->ID );

		return true;
	}

	/**
	 * Public directory search/filter. Only ever returns published listings —
	 * a pending or suspended listing is never reachable through this query,
	 * regardless of who is browsing.
	 *
	 * @return array{posts:WP_Post[], total:int, max_pages:int}
	 */
	public function search( array $filters, int $paged = 1, int $per_page = 12 ): array {
		$tax_query = [];
		if ( ! empty( $filters['category'] ) ) {
			$tax_query[] = [
				'taxonomy' => BusinessCPT::TAXONOMY,
				'field'    => 'term_id',
				'terms'    => absint( $filters['category'] ),
			];
		}

		$meta_query = [];
		if ( ! empty( $filters['country'] ) ) {
			$meta_query[] = [
				'key'     => '_ipfo_country_ids',
				'value'   => '"' . absint( $filters['country'] ) . '"',
				'compare' => 'LIKE',
			];
		}

		$args = [
			'post_type'      => BusinessCPT::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => max( 1, $paged ),
			'orderby'        => [ 'meta_value_num' => 'DESC', 'title' => 'ASC' ],
			'meta_key'       => '_ipfo_featured',
		];

		if ( ! empty( $filters['q'] ) ) {
			$args['s'] = sanitize_text_field( $filters['q'] );
		}

		if ( $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		if ( $meta_query ) {
			$args['meta_query'] = array_merge( [ 'relation' => 'AND' ], $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		$query = new WP_Query( $args );

		return [
			'posts'     => $query->posts,
			'total'     => (int) $query->found_posts,
			'max_pages' => (int) $query->max_num_pages,
		];
	}

	/** @return \WP_Term[] */
	public function categories(): array {
		return get_terms( [ 'taxonomy' => BusinessCPT::TAXONOMY, 'hide_empty' => false ] );
	}

	/** @return object[] */
	public function countries(): array {
		return ( new CountryRepository() )->all( 'active' );
	}

	public static function register_hooks(): void {
		add_action( 'transition_post_status', [ self::class, 'on_status_transition' ], 10, 3 );
	}

	/**
	 * Fires the "your listing is now live" email the moment an admin
	 * approves a pending listing (or publishes one directly) — no separate
	 * "Approve" button needed in wp-admin, the native Publish action on the
	 * CPT edit screen (or a bulk "Publish" from the list table) already
	 * does this.
	 */
	public static function on_status_transition( string $new_status, string $old_status, WP_Post $post ): void {
		if ( BusinessCPT::SLUG !== $post->post_type || 'publish' === $old_status || 'publish' !== $new_status ) {
			return;
		}

		$owner = get_userdata( (int) $post->post_author );
		if ( ! $owner ) {
			return;
		}

		( new NotificationService() )->business_listing_published( $owner, $post->post_title, get_permalink( $post ) );
		( new AccessLogRepository() )->log( 'business_listing_published', $owner->ID, 'ipfo_business', $post->ID );
	}
}
