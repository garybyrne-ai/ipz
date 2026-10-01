<?php
declare( strict_types = 1 );

namespace IPFO\Admin;

use IPFO\Capabilities;
use IPFO\Admin\Pages\DashboardPage;
use IPFO\Admin\Pages\CountriesPage;
use IPFO\Admin\Pages\InvitationsPage;
use IPFO\Admin\Pages\UsersAccessPage;
use IPFO\Admin\Pages\ChecklistsPage;
use IPFO\Admin\Pages\AnalyticsPage;
use IPFO\Admin\Pages\AccessLogsPage;
use IPFO\Admin\Pages\SettingsPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {

	public const SLUG = 'ipfo-portal';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'build_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_notices', [ $this, 'ipfo_temp_diagnostic' ] );
	}

	/**
	 * TEMPORARY diagnostic — remove before final release. Prints an
	 * always-visible notice (no capability gate) on every wp-admin page so we
	 * can see exactly what current_user_can() returns for our capability at
	 * request time, independent of whether the menu itself renders.
	 */
	public function ipfo_temp_diagnostic(): void {
		global $wp_filter, $current_user;

		$cap    = Capabilities::MANAGE_PORTAL;
		$direct = current_user_can( $cap );
		$in_allcaps = isset( $current_user->allcaps[ $cap ] ) ? ( $current_user->allcaps[ $cap ] ? 'true' : 'false' ) : 'NOT SET';
		$roles  = implode( ',', (array) ( $current_user->roles ?? [] ) );
		$has_filter = isset( $wp_filter['user_has_cap'] ) ? count( $wp_filter['user_has_cap']->callbacks ?? [] ) : 0;
		$map_meta_filter = isset( $wp_filter['map_meta_cap'] ) ? count( $wp_filter['map_meta_cap']->callbacks ?? [] ) : 0;

		printf(
			'<div class="notice notice-info"><p><strong>IPFO DIAGNOSTIC</strong> — current_user_can(%1$s): %2$s | allcaps[%1$s]: %3$s | roles: %4$s | user_has_cap filter groups: %5$d | map_meta_cap filter groups: %6$d</p></div>',
			esc_html( $cap ),
			$direct ? 'TRUE' : 'FALSE',
			esc_html( $in_allcaps ),
			esc_html( $roles ),
			(int) $has_filter,
			(int) $map_meta_filter
		);
	}

	public function build_menu(): void {
		$cap = Capabilities::MANAGE_PORTAL;

		// No explicit menu position: a fixed integer position (e.g. 30) can collide
		// with another active plugin's menu at the same slot, silently dropping
		// one of the two from $menu with no error. Omitting it appends safely.
		add_menu_page(
			__( 'IPFO Guidance Portal', 'ipfo-country-guidance-portal' ),
			__( 'IPFO Portal', 'ipfo-country-guidance-portal' ),
			$cap,
			self::SLUG,
			[ new DashboardPage(), 'render' ],
			'dashicons-shield'
		);

		add_submenu_page( self::SLUG, __( 'Dashboard', 'ipfo-country-guidance-portal' ), __( 'Dashboard', 'ipfo-country-guidance-portal' ), $cap, self::SLUG, [ new DashboardPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Countries', 'ipfo-country-guidance-portal' ), __( 'Countries', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-countries', [ new CountriesPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Invitations', 'ipfo-country-guidance-portal' ), __( 'Invitations', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-invitations', [ new InvitationsPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Users & Access', 'ipfo-country-guidance-portal' ), __( 'Users & Access', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-users-access', [ new UsersAccessPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Checklists', 'ipfo-country-guidance-portal' ), __( 'Checklists', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-checklists', [ new ChecklistsPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Analytics', 'ipfo-country-guidance-portal' ), __( 'Analytics', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-analytics', [ new AnalyticsPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Access Logs', 'ipfo-country-guidance-portal' ), __( 'Access Logs', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-access-logs', [ new AccessLogsPage(), 'render' ] );
		add_submenu_page( self::SLUG, __( 'Settings', 'ipfo-country-guidance-portal' ), __( 'Settings', 'ipfo-country-guidance-portal' ), $cap, 'ipfo-settings', [ new SettingsPage(), 'render' ] );
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, self::SLUG ) && ! str_contains( (string) ( $_GET['page'] ?? '' ), 'ipfo-' ) && ! in_array( get_current_screen()?->post_type, [ 'ipfo_guide', 'ipfo_chapter', 'ipfo_resource', 'ipfo_faq' ], true ) ) {
			return;
		}

		wp_enqueue_style( 'ipfo-admin', IPFO_PLUGIN_URL . 'assets/css/ipfo-admin.css', [], IPFO_VERSION );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_media();
		wp_enqueue_script( 'ipfo-admin', IPFO_PLUGIN_URL . 'assets/js/ipfo-admin.js', [ 'jquery', 'jquery-ui-sortable' ], IPFO_VERSION, true );
	}
}
