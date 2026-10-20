<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the front-end URL configured for a named portal page
 * (login, register, dashboard, guides, resources, checklist, profile).
 * Falls back to the home page if the admin has not configured it yet,
 * so the plugin never fatals on a fresh install.
 */
function ipfo_get_portal_page_url( string $key ): string {
	$pages = get_option( 'ipfo_portal_pages', [] );
	$page_id = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;

	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return get_permalink( $page_id );
	}

	return home_url( '/' );
}

/**
 * Cache-busting version string for an enqueued asset. Uses the file's own
 * mtime rather than the static IPFO_VERSION constant, so updating a CSS/JS
 * file always produces a new ?ver= query string — otherwise a host-level
 * cache (full-page cache, CDN, or even just the visitor's browser) can keep
 * serving an old asset indefinitely after a code deploy, since the version
 * string never changed to signal that anything was updated.
 */
function ipfo_asset_version( string $relative_path ): string {
	$path = IPFO_PLUGIN_DIR . ltrim( $relative_path, '/' );
	$mtime = is_readable( $path ) ? filemtime( $path ) : false;

	return $mtime ? (string) $mtime : IPFO_VERSION;
}

function ipfo_disclaimer_text(): string {
	return (string) get_option( 'ipfo_disclaimer_text', '' );
}

/**
 * Where a logged-in user's own dashboard lives — the IP-client Dashboard or
 * the Business Directory owner's Dashboard, whichever matches their actual
 * role. Centralising this is what stops the login/register flow confusing
 * the two: every "take me to my dashboard" redirect (post-login, "already
 * signed in" on Login/Register, nav "Dashboard" link) goes through this one
 * function instead of each place guessing or hard-coding 'dashboard'.
 */
function ipfo_dashboard_url_for_user( int $user_id = 0 ): string {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return ipfo_get_portal_page_url( 'login' );
	}

	$user = get_userdata( $user_id );
	if ( $user && in_array( \IPFO\Capabilities::BUSINESS_ROLE, (array) $user->roles, true ) ) {
		return ipfo_get_portal_page_url( 'business-dashboard' );
	}

	return ipfo_get_portal_page_url( 'dashboard' );
}

/**
 * Renders a template file from /templates, extracting an associative array
 * of variables into local scope for that template only.
 */
function ipfo_render_template( string $template, array $vars = [] ): string {
	$path = IPFO_PLUGIN_DIR . 'templates/' . ltrim( $template, '/' );

	if ( ! is_readable( $path ) ) {
		return '';
	}

	extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract

	ob_start();
	include $path;
	return (string) ob_get_clean();
}
