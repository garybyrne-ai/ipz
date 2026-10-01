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
	}

	public function build_menu(): void {
		$cap = Capabilities::MANAGE_PORTAL;

		add_menu_page(
			__( 'IPFO Guidance Portal', 'ipfo-country-guidance-portal' ),
			__( 'IPFO Portal', 'ipfo-country-guidance-portal' ),
			$cap,
			self::SLUG,
			[ new DashboardPage(), 'render' ],
			'dashicons-shield',
			30
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
