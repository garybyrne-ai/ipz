<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\Services\AnalyticsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AnalyticsPage {

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$analytics       = new AnalyticsService();
		$totals          = $analytics->totals();
		$by_country      = $analytics->users_by_country();
		$guide_views     = $analytics->guide_views( 10 );
		$max_country     = max( 1, ...array_map( static fn( $r ) => (int) $r->total, $by_country ?: [ (object) [ 'total' => 1 ] ] ) );
		$max_views       = max( 1, ...array_map( static fn( $r ) => (int) $r->views, $guide_views ?: [ (object) [ 'views' => 1 ] ] ) );

		echo '<div class="wrap"><h1>' . esc_html__( 'Analytics', 'ipfo-country-guidance-portal' ) . '</h1>';

		echo '<div class="ipfo-admin-widgets">';
		foreach (
			[
				__( 'Registered IPs', 'ipfo-country-guidance-portal' )       => $totals['total_ips'],
				__( 'Active IPs', 'ipfo-country-guidance-portal' )           => $totals['active_ips'],
				__( 'Published Guides', 'ipfo-country-guidance-portal' )     => $totals['published_guides'],
				__( 'Pending Acknowledgements', 'ipfo-country-guidance-portal' ) => $totals['pending_ack'],
			] as $label => $value
		) {
			echo '<div class="ipfo-admin-widget"><div class="ipfo-admin-widget__label">' . esc_html( $label ) . '</div><div class="ipfo-admin-widget__value">' . (int) $value . '</div></div>';
		}
		echo '</div>';

		echo '<h2>' . esc_html__( 'Intended Parents by Country', 'ipfo-country-guidance-portal' ) . '</h2>';
		echo '<div class="ipfo-admin-widget">';
		foreach ( $by_country as $row ) {
			$pct = round( ( (int) $row->total / $max_country ) * 100 );
			echo '<div style="margin-bottom:10px;"><div style="display:flex;justify-content:space-between;font-size:13px;"><span>' . esc_html( $row->name ) . '</span><span>' . (int) $row->total . '</span></div><div class="ipfo-progress-bar"><div class="ipfo-progress-bar__fill" style="width:' . esc_attr( $pct ) . '%;"></div></div></div>';
		}
		if ( empty( $by_country ) ) {
			echo '<p>' . esc_html__( 'No data yet.', 'ipfo-country-guidance-portal' ) . '</p>';
		}
		echo '</div>';

		echo '<h2>' . esc_html__( 'Most Viewed Guides', 'ipfo-country-guidance-portal' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Guide', 'ipfo-country-guidance-portal' ) . '</th><th>' . esc_html__( 'Views', 'ipfo-country-guidance-portal' ) . '</th></tr></thead><tbody>';
		foreach ( $guide_views as $row ) {
			echo '<tr><td>' . esc_html( get_the_title( (int) $row->guide_id ) ?: ( '#' . $row->guide_id ) ) . '</td><td>' . (int) $row->views . '</td></tr>';
		}
		if ( empty( $guide_views ) ) {
			echo '<tr><td colspan="2">' . esc_html__( 'No guide views recorded yet.', 'ipfo-country-guidance-portal' ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '</div>';
	}
}
