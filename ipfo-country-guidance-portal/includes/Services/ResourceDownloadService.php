<?php
declare( strict_types = 1 );

namespace IPFO\Services;

use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\AccessLogRepository;
use IPFO\Security\RateLimiter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Streams protected resource files through a nonce-gated, permission-checked
 * endpoint instead of ever exposing a raw Media Library URL. A request for
 * protected content is re-verified on every single request — no cached or
 * guessable public link ever grants access.
 */
final class ResourceDownloadService {

	public const QUERY_VAR = 'ipfo_resource_file';

	private AccessControlService $access;
	private AccessLogRepository $logs;

	public function __construct() {
		$this->access = new AccessControlService();
		$this->logs   = new AccessLogRepository();
	}

	public static function register(): void {
		add_action( 'template_redirect', [ new self(), 'maybe_handle' ] );
	}

	public static function build_url( int $resource_id, bool $download = true ): string {
		$action = $download ? 'download' : 'view';
		$url    = add_query_arg(
			[
				self::QUERY_VAR => $resource_id,
				'ipfo_mode'     => $action,
			],
			home_url( '/' )
		);

		return wp_nonce_url( $url, 'ipfo_resource_' . $resource_id );
	}

	public function maybe_handle(): void {
		if ( empty( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		$resource_id = absint( $_GET[ self::QUERY_VAR ] );
		$mode        = isset( $_GET['ipfo_mode'] ) && 'download' === $_GET['ipfo_mode'] ? 'download' : 'view';
		$nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ipfo_resource_' . $resource_id ) ) {
			wp_die( esc_html__( 'This link has expired. Please return to your portal and try again.', 'ipfo-country-guidance-portal' ), 403 );
		}

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please sign in to access this resource.', 'ipfo-country-guidance-portal' ), 401 );
		}

		if ( ! RateLimiter::allow( 'resource_download', 60, 3600 ) ) {
			wp_die( esc_html__( 'Too many requests. Please try again shortly.', 'ipfo-country-guidance-portal' ), 429 );
		}

		$user_id = get_current_user_id();

		if ( get_post_type( $resource_id ) !== ResourceCPT::SLUG || ! $this->access->can_access_resource( $user_id, $resource_id ) ) {
			$this->logs->log( 'resource_denied', $user_id, 'resource', $resource_id );
			wp_die( esc_html__( 'You do not have permission to access this resource.', 'ipfo-country-guidance-portal' ), 403 );
		}

		if ( 'download' === $mode && ! get_post_meta( $resource_id, '_ipfo_download_enabled', true ) ) {
			wp_die( esc_html__( 'Downloading this resource is not permitted.', 'ipfo-country-guidance-portal' ), 403 );
		}

		$this->stream( $resource_id, $mode );
	}

	private function stream( int $resource_id, string $mode ): void {
		$attachment_id = (int) get_post_meta( $resource_id, '_ipfo_attachment_id', true );
		$path          = $attachment_id ? get_attached_file( $attachment_id ) : '';

		if ( ! $path || ! file_exists( $path ) ) {
			wp_die( esc_html__( 'This resource could not be found.', 'ipfo-country-guidance-portal' ), 404 );
		}

		$this->logs->log( 'download' === $mode ? 'download' : 'resource_access', get_current_user_id(), 'resource', $resource_id );

		$mime = get_post_mime_type( $attachment_id ) ?: 'application/octet-stream';

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . filesize( $path ) );

		$disposition = 'download' === $mode ? 'attachment' : 'inline';
		header( sprintf( 'Content-Disposition: %s; filename="%s"', $disposition, basename( $path ) ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
