<?php
declare( strict_types = 1 );

namespace IPFO;

use IPFO\Database\Schema;
use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\GuideChapterCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\CPT\FaqCPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Activator {

	/**
	 * No current_user_can() guard here: WordPress core already verifies
	 * activation permission before ever invoking a plugin's activation
	 * hook (the admin UI checks it before calling activate_plugin()), so
	 * this callback can assume it's authorised. Re-checking it here was a
	 * real bug — confirmed locally with WP-CLI, which activates plugins
	 * with no "current user" in context, so current_user_can() always
	 * returned false and silently skipped everything below this line
	 * (seeding default options, scheduling the maintenance cron) despite
	 * `wp plugin activate` reporting success and Schema::install() having
	 * already run moments earlier via a different code path.
	 */
	public static function activate(): void {
		Schema::install();
		Capabilities::install();

		// Register post types synchronously (the 'init' hook has already
		// fired by the time this activation callback runs, so register()'s
		// own add_action('init', ...) would never fire this request).
		( new CountryGuideCPT() )->register_post_type();
		( new GuideChapterCPT() )->register_post_type();
		( new ResourceCPT() )->register_post_type();
		( new FaqCPT() )->register_post_type();

		self::seed_default_options();

		if ( ! wp_next_scheduled( 'ipfo_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ipfo_daily_maintenance' );
		}

		flush_rewrite_rules();

		update_option( 'ipfo_version', IPFO_VERSION );
	}

	private static function seed_default_options(): void {
		add_option( 'ipfo_disclaimer_text', self::default_disclaimer() );
		add_option( 'ipfo_require_email_verification', '1' );
		add_option( 'ipfo_require_admin_approval', '0' );
		add_option( 'ipfo_remove_data_on_uninstall', '0' );
		add_option(
			'ipfo_portal_pages',
			[
				'login'          => '',
				'register'       => '',
				'dashboard'      => '',
				'my-guides'      => '',
				'guide'          => '',
				'country-guides' => '',
				'resources'      => '',
				'checklist'      => '',
				'profile'        => '',
				'notifications'  => '',
			]
		);
	}

	private static function default_disclaimer(): string {
		return __(
			'This information is provided for general guidance and does not constitute legal advice. Laws and regulations may change. Intended Parents should obtain independent legal advice where appropriate.',
			'ipfo-country-guidance-portal'
		);
	}
}
