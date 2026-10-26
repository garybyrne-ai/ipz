<?php
/**
 * Uninstall handler for IPFO Country Guidance Portal.
 *
 * By default, uninstalling the plugin removes NOTHING — all client data,
 * guides, resources and settings are preserved so the plugin can be
 * reinstalled without data loss. Destructive cleanup only runs if an
 * administrator has explicitly opted in via
 * Settings > "Remove Data on Uninstall".
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( '1' !== get_option( 'ipfo_remove_data_on_uninstall', '0' ) ) {
	return;
}

global $wpdb;

$tables = [
	'countries',
	'invitations',
	'user_access',
	'guide_progress',
	'checklist_items',
	'checklist_progress',
	'acknowledgements',
	'guide_versions',
	'access_logs',
];

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ipfo_{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

foreach ( [ 'ipfo_guide', 'ipfo_chapter', 'ipfo_resource', 'ipfo_faq', 'ipfo_business' ] as $post_type ) {
	$posts = get_posts( [ 'post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids' ] );
	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

$business_terms = get_terms( [ 'taxonomy' => 'ipfo_business_category', 'hide_empty' => false, 'fields' => 'ids' ] );
if ( ! is_wp_error( $business_terms ) ) {
	foreach ( $business_terms as $term_id ) {
		wp_delete_term( $term_id, 'ipfo_business_category' );
	}
}

$options = [
	'ipfo_version',
	'ipfo_db_version',
	'ipfo_rewrite_version',
	'ipfo_disclaimer_text',
	'ipfo_require_email_verification',
	'ipfo_require_admin_approval',
	'ipfo_require_business_approval',
	'ipfo_require_business_invitation',
	'ipfo_remove_data_on_uninstall',
	'ipfo_default_reading_theme',
	'ipfo_portal_pages',
	'ipfo_demo_data_installed',
	'ipfo_demo_data_refs',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

$client_users = get_users( [ 'role' => 'ipfo_client', 'fields' => 'ID' ] );
foreach ( $client_users as $user_id ) {
	foreach ( [ '_ipfo_phone', '_ipfo_consent_at', '_ipfo_terms_accepted_at', '_ipfo_country_id', '_ipfo_email_verified', '_ipfo_approval_status', '_ipfo_verify_token_hash', '_ipfo_verify_expires' ] as $meta_key ) {
		delete_user_meta( $user_id, $meta_key );
	}
}

$business_users = get_users( [ 'role' => 'ipfo_business_owner', 'fields' => 'ID' ] );
foreach ( $business_users as $user_id ) {
	foreach ( [ '_ipfo_phone', '_ipfo_consent_at', '_ipfo_terms_accepted_at', '_ipfo_business_post_id' ] as $meta_key ) {
		delete_user_meta( $user_id, $meta_key );
	}
}

remove_role( 'ipfo_client' );
remove_role( 'ipfo_business_owner' );

$admin = get_role( 'administrator' );
if ( $admin ) {
	$admin->remove_cap( 'manage_ipfo_portal' );
}

wp_clear_scheduled_hook( 'ipfo_daily_maintenance' );
