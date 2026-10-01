<?php
/**
 * Plugin Name:       IPFO Country Guidance Portal
 * Plugin URI:        https://ipfertilityoptions.com/
 * Description:       Secure, premium Intended Parent client portal for IP Fertility Options. Country-specific surrogacy guidance, digital booklets, checklists, resources and invitation-based access control. Integrates with the existing UiCore Pro + Elementor website.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            IP Fertility Options
 * Author URI:        https://ipfertilityoptions.com/
 * Text Domain:       ipfo-country-guidance-portal
 * Domain Path:       /languages
 *
 * This plugin is additive only. It does not modify WordPress core, does not
 * replace the active theme, Elementor, or UiCore Pro, and does not alter any
 * existing page, URL, or branding. All functionality is delivered through
 * custom post types, custom database tables, shortcodes and Elementor
 * widgets that site editors opt into on their own pages.
 */

declare( strict_types = 1 );

namespace IPFO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IPFO_VERSION', '1.0.0' );
define( 'IPFO_DB_VERSION', '1.0.0' );
define( 'IPFO_PLUGIN_FILE', __FILE__ );
define( 'IPFO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IPFO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IPFO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'IPFO_TEXT_DOMAIN', 'ipfo-country-guidance-portal' );

require_once IPFO_PLUGIN_DIR . 'includes/Autoloader.php';
require_once IPFO_PLUGIN_DIR . 'includes/functions.php';
Autoloader::register();

register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', static function (): void {
	Plugin::instance()->boot();
}, 20 );
