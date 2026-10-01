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

function ipfo_disclaimer_text(): string {
	return (string) get_option( 'ipfo_disclaimer_text', '' );
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
