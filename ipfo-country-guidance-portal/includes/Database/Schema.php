<?php
declare( strict_types = 1 );

namespace IPFO\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and upgrades the plugin's custom database tables.
 *
 * All table names are built from $wpdb->prefix at runtime; the "wp_" prefix
 * is never hard-coded so the plugin works on any WordPress installation.
 */
final class Schema {

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'ipfo_' . $name;
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$tables = [];

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_countries (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			code VARCHAR(10) NOT NULL,
			flag VARCHAR(191) DEFAULT NULL,
			description LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			last_reviewed DATE NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY status (status)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_invitations (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			code VARCHAR(64) NOT NULL,
			token_hash CHAR(64) NOT NULL,
			country_id BIGINT UNSIGNED NULL,
			guide_id BIGINT UNSIGNED NULL,
			assigned_resources LONGTEXT NULL,
			max_uses INT UNSIGNED NOT NULL DEFAULT 1,
			use_count INT UNSIGNED NOT NULL DEFAULT 0,
			single_use TINYINT(1) NOT NULL DEFAULT 1,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			expires_at DATETIME NULL,
			created_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY status (status),
			KEY country_id (country_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_user_access (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			object_type VARCHAR(20) NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			granted_by BIGINT UNSIGNED NULL,
			granted_at DATETIME NOT NULL,
			expires_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_object (user_id, object_type, object_id),
			KEY user_id (user_id),
			KEY object (object_type, object_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_guide_progress (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			guide_id BIGINT UNSIGNED NOT NULL,
			last_chapter_id BIGINT UNSIGNED NULL,
			progress_percent SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			bookmarks LONGTEXT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_guide (user_id, guide_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_checklist_items (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			country_id BIGINT UNSIGNED NOT NULL,
			label VARCHAR(255) NOT NULL,
			description TEXT NULL,
			sort_order INT NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY country_id (country_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_checklist_progress (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			checklist_item_id BIGINT UNSIGNED NOT NULL,
			is_complete TINYINT(1) NOT NULL DEFAULT 0,
			completed_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_item (user_id, checklist_item_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_acknowledgements (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			guide_id BIGINT UNSIGNED NOT NULL,
			version VARCHAR(20) NOT NULL,
			acknowledged_at DATETIME NOT NULL,
			ip_hash CHAR(64) NULL,
			user_agent VARCHAR(255) NULL,
			PRIMARY KEY  (id),
			KEY user_guide (user_id, guide_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_guide_versions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			guide_id BIGINT UNSIGNED NOT NULL,
			version VARCHAR(20) NOT NULL,
			changelog LONGTEXT NULL,
			requires_ack TINYINT(1) NOT NULL DEFAULT 0,
			published_by BIGINT UNSIGNED NULL,
			published_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY guide_id (guide_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}ipfo_access_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NULL,
			event_type VARCHAR(50) NOT NULL,
			object_type VARCHAR(50) NULL,
			object_id BIGINT UNSIGNED NULL,
			meta LONGTEXT NULL,
			ip_hash CHAR(64) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'ipfo_db_version', IPFO_DB_VERSION );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( 'ipfo_db_version' ) !== IPFO_DB_VERSION ) {
			self::install();
		}
	}
}
