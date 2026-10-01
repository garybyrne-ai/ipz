<?php
declare( strict_types = 1 );

namespace IPFO\Rest;

use IPFO\Services\AccessControlService;
use IPFO\Services\ChecklistService;
use IPFO\Services\AcknowledgementService;
use IPFO\Services\SearchService;
use IPFO\Repositories\GuideProgressRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All portal REST routes under ipfo/v1. Every route requires a logged-in
 * user; WordPress core already rejects cookie-authenticated REST requests
 * missing a valid X-WP-Nonce header (rest_cookie_check_errors), giving us
 * CSRF protection for free. Every handler additionally re-checks ownership
 * / authorisation through the service layer before touching any data.
 */
final class RestController {

	private const NS = 'ipfo/v1';

	public function register_routes(): void {
		register_rest_route(
			self::NS,
			'/progress',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'update_progress' ],
				'permission_callback' => [ $this, 'require_login' ],
				'args'                => [
					'guide_id' => [ 'required' => true, 'type' => 'integer' ],
					'chapter_id' => [ 'required' => true, 'type' => 'integer' ],
					'percent'  => [ 'required' => true, 'type' => 'integer' ],
				],
			]
		);

		register_rest_route(
			self::NS,
			'/bookmark',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'toggle_bookmark' ],
				'permission_callback' => [ $this, 'require_login' ],
				'args'                => [
					'guide_id'   => [ 'required' => true, 'type' => 'integer' ],
					'chapter_id' => [ 'required' => true, 'type' => 'integer' ],
				],
			]
		);

		register_rest_route(
			self::NS,
			'/checklist',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'toggle_checklist' ],
				'permission_callback' => [ $this, 'require_login' ],
				'args'                => [
					'item_id'  => [ 'required' => true, 'type' => 'integer' ],
					'complete' => [ 'required' => true, 'type' => 'boolean' ],
				],
			]
		);

		register_rest_route(
			self::NS,
			'/acknowledge',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'acknowledge' ],
				'permission_callback' => [ $this, 'require_login' ],
				'args'                => [
					'guide_id' => [ 'required' => true, 'type' => 'integer' ],
				],
			]
		);

		register_rest_route(
			self::NS,
			'/search',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'search' ],
				'permission_callback' => [ $this, 'require_login' ],
				'args'                => [
					'q' => [ 'required' => true, 'type' => 'string' ],
				],
			]
		);
	}

	public function require_login(): bool {
		return is_user_logged_in();
	}

	public function update_progress( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id   = get_current_user_id();
		$guide_id  = (int) $request->get_param( 'guide_id' );
		$chapter   = (int) $request->get_param( 'chapter_id' );
		$percent   = (int) $request->get_param( 'percent' );

		if ( ! ( new AccessControlService() )->can_access_guide( $user_id, $guide_id ) ) {
			return new WP_Error( 'ipfo_forbidden', __( 'You do not have access to this guide.', 'ipfo-country-guidance-portal' ), [ 'status' => 403 ] );
		}

		( new GuideProgressRepository() )->set_progress( $user_id, $guide_id, $chapter, $percent );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	public function toggle_bookmark( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id  = get_current_user_id();
		$guide_id = (int) $request->get_param( 'guide_id' );
		$chapter  = (int) $request->get_param( 'chapter_id' );

		if ( ! ( new AccessControlService() )->can_access_guide( $user_id, $guide_id ) ) {
			return new WP_Error( 'ipfo_forbidden', __( 'You do not have access to this guide.', 'ipfo-country-guidance-portal' ), [ 'status' => 403 ] );
		}

		$bookmarks = ( new GuideProgressRepository() )->toggle_bookmark( $user_id, $guide_id, $chapter );

		return new WP_REST_Response( [ 'success' => true, 'bookmarks' => $bookmarks ] );
	}

	public function toggle_checklist( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ok = ( new ChecklistService() )->toggle(
			get_current_user_id(),
			(int) $request->get_param( 'item_id' ),
			(bool) $request->get_param( 'complete' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'ipfo_forbidden', __( 'You do not have access to this checklist item.', 'ipfo-country-guidance-portal' ), [ 'status' => 403 ] );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	public function acknowledge( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ok = ( new AcknowledgementService() )->acknowledge( get_current_user_id(), (int) $request->get_param( 'guide_id' ) );

		if ( ! $ok ) {
			return new WP_Error( 'ipfo_forbidden', __( 'You do not have access to this guide.', 'ipfo-country-guidance-portal' ), [ 'status' => 403 ] );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	public function search( WP_REST_Request $request ): WP_REST_Response {
		$results = ( new SearchService() )->search( get_current_user_id(), (string) $request->get_param( 'q' ) );

		return new WP_REST_Response( [ 'results' => $results ] );
	}
}
