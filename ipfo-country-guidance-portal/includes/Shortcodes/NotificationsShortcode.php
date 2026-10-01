<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Derives a notification feed from the user's own access log entries for
 * the events the spec lists as notification-worthy (guide updates,
 * acknowledgement requirements, invitation use). No separate table is
 * needed since access_logs already records these facts with a timestamp.
 */
final class NotificationsShortcode extends ShortcodeBase {

	private const NOTIFIABLE = [ 'guide_updated', 'acknowledgement', 'invitation_use', 'resource_access' ];

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		global $wpdb;
		$table = \IPFO\Database\Schema::table( 'access_logs' );

		$placeholders = implode( ',', array_fill( 0, count( self::NOTIFIABLE ), '%s' ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND event_type IN ({$placeholders}) ORDER BY created_at DESC LIMIT 20",
				array_merge( [ get_current_user_id() ], self::NOTIFIABLE )
			)
		);

		return $this->wrap(
			ipfo_render_template( 'notifications.php', [ 'notifications' => $rows ] ),
			'ipfo-notifications'
		);
	}
}
