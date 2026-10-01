<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Services\AnalyticsService;
use IPFO\Services\InvitationService;
use IPFO\Repositories\AccessLogRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DashboardPage {

	public function render(): void {
		if ( ! current_user_can( \IPFO\Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$analytics    = new AnalyticsService();
		$totals       = $analytics->totals();
		$recent_users = $analytics->recent_registrations( 5 );
		$expiring     = ( new InvitationService() )->expiring_soon( 7 );
		$recent_logs  = ( new AccessLogRepository() )->recent( 10 );

		echo '<div class="wrap"><h1>' . esc_html__( 'IPFO Guidance Portal', 'ipfo-country-guidance-portal' ) . '</h1>';

		echo '<div class="ipfo-admin-widgets">';
		$this->widget( __( 'Total Intended Parents', 'ipfo-country-guidance-portal' ), $totals['total_ips'] );
		$this->widget( __( 'Active IPs', 'ipfo-country-guidance-portal' ), $totals['active_ips'] );
		$this->widget( __( 'Countries', 'ipfo-country-guidance-portal' ), $totals['countries'] );
		$this->widget( __( 'Published Guides', 'ipfo-country-guidance-portal' ), $totals['published_guides'] );
		$this->widget( __( 'Draft Guides', 'ipfo-country-guidance-portal' ), $totals['draft_guides'] );
		$this->widget( __( 'Pending Acknowledgements', 'ipfo-country-guidance-portal' ), $totals['pending_ack'] );
		$this->widget( __( 'Expiring Invitations (7d)', 'ipfo-country-guidance-portal' ), $totals['expiring_invites'] );
		echo '</div>';

		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">';

		echo '<div class="ipfo-admin-widget"><h2>' . esc_html__( 'Recent Registrations', 'ipfo-country-guidance-portal' ) . '</h2><table class="widefat"><tbody>';
		foreach ( $recent_users as $user ) {
			echo '<tr><td>' . esc_html( $user->display_name ) . '</td><td>' . esc_html( $user->user_email ) . '</td><td>' . esc_html( mysql2date( get_option( 'date_format' ), $user->user_registered ) ) . '</td></tr>';
		}
		if ( empty( $recent_users ) ) {
			echo '<tr><td>' . esc_html__( 'No registrations yet.', 'ipfo-country-guidance-portal' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';

		echo '<div class="ipfo-admin-widget"><h2>' . esc_html__( 'Recent Activity', 'ipfo-country-guidance-portal' ) . '</h2><table class="widefat"><tbody>';
		foreach ( $recent_logs as $log ) {
			$user = $log->user_id ? get_userdata( $log->user_id ) : null;
			echo '<tr><td>' . esc_html( $user ? $user->display_name : '—' ) . '</td><td>' . esc_html( $log->event_type ) . '</td><td>' . esc_html( $log->created_at ) . '</td></tr>';
		}
		if ( empty( $recent_logs ) ) {
			echo '<tr><td>' . esc_html__( 'No activity recorded yet.', 'ipfo-country-guidance-portal' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';

		echo '</div>';

		if ( ! empty( $expiring ) ) {
			echo '<div class="ipfo-admin-widget" style="margin-top:20px;"><h2>' . esc_html__( 'Invitations Expiring Soon', 'ipfo-country-guidance-portal' ) . '</h2><table class="widefat"><tbody>';
			foreach ( $expiring as $inv ) {
				echo '<tr><td><span class="ipfo-admin-code">' . esc_html( $inv->code ) . '</span></td><td>' . esc_html( $inv->expires_at ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}

		echo '</div>';
	}

	private function widget( string $label, int $value ): void {
		echo '<div class="ipfo-admin-widget"><div class="ipfo-admin-widget__label">' . esc_html( $label ) . '</div><div class="ipfo-admin-widget__value">' . (int) $value . '</div></div>';
	}
}
