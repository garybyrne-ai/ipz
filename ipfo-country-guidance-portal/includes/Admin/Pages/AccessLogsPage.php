<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\Repositories\AccessLogRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AccessLogsPage {

	private const EVENT_TYPES = [
		'login', 'guide_view', 'resource_access', 'resource_denied', 'download',
		'acknowledgement', 'invitation_use', 'permission_change', 'version_change',
	];

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$event_type = sanitize_key( wp_unslash( $_GET['event_type'] ?? '' ) );
		$logs       = ( new AccessLogRepository() )->recent( 100, $event_type ? [ 'event_type' => $event_type ] : [] );

		echo '<div class="wrap"><h1>' . esc_html__( 'Access Logs', 'ipfo-country-guidance-portal' ) . '</h1>';

		echo '<form method="get" style="margin:16px 0;"><input type="hidden" name="page" value="ipfo-access-logs" /><select name="event_type" onchange="this.form.submit()"><option value="">' . esc_html__( 'All Events', 'ipfo-country-guidance-portal' ) . '</option>';
		foreach ( self::EVENT_TYPES as $type ) {
			echo '<option value="' . esc_attr( $type ) . '" ' . selected( $event_type, $type, false ) . '>' . esc_html( ucwords( str_replace( '_', ' ', $type ) ) ) . '</option>';
		}
		echo '</select></form>';

		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'User', 'ipfo-country-guidance-portal' ) . '</th><th>' . esc_html__( 'Event', 'ipfo-country-guidance-portal' ) . '</th><th>' . esc_html__( 'Object', 'ipfo-country-guidance-portal' ) . '</th><th>' . esc_html__( 'Date', 'ipfo-country-guidance-portal' ) . '</th></tr></thead><tbody>';

		foreach ( $logs as $log ) {
			$user = $log->user_id ? get_userdata( $log->user_id ) : null;
			$object = $log->object_type ? ucfirst( $log->object_type ) . ' #' . $log->object_id : '—';
			echo '<tr><td>' . esc_html( $user ? $user->display_name : __( 'Guest', 'ipfo-country-guidance-portal' ) ) . '</td><td>' . esc_html( ucwords( str_replace( '_', ' ', $log->event_type ) ) ) . '</td><td>' . esc_html( $object ) . '</td><td>' . esc_html( $log->created_at ) . '</td></tr>';
		}

		if ( empty( $logs ) ) {
			echo '<tr><td colspan="4">' . esc_html__( 'No activity recorded yet.', 'ipfo-country-guidance-portal' ) . '</td></tr>';
		}

		echo '</tbody></table></div>';
	}
}
