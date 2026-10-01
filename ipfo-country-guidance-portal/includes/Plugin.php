<?php
declare( strict_types = 1 );

namespace IPFO;

use IPFO\Admin\AdminMenu;
use IPFO\Admin\MetaBoxes\ChapterMetaBox;
use IPFO\Admin\MetaBoxes\FaqMetaBox;
use IPFO\Admin\MetaBoxes\GuideMetaBox;
use IPFO\Admin\MetaBoxes\ResourceMetaBox;
use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\FaqCPT;
use IPFO\CPT\GuideChapterCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Database\Schema;
use IPFO\Elementor\ElementorIntegration;
use IPFO\Frontend\Assets;
use IPFO\Privacy\GdprHandler;
use IPFO\Rest\RestController;
use IPFO\Services\InvitationService;
use IPFO\Services\RegistrationService;
use IPFO\Services\ResourceDownloadService;
use IPFO\Shortcodes\ShortcodeManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		load_plugin_textdomain( IPFO_TEXT_DOMAIN, false, dirname( IPFO_PLUGIN_BASENAME ) . '/languages' );

		Schema::maybe_upgrade();

		$this->register_post_types();
		$this->register_meta_boxes();
		$this->register_shortcodes();
		$this->register_elementor();
		$this->register_rest_routes();
		$this->register_frontend();
		$this->register_admin();
		$this->register_security_hooks();
		$this->register_privacy();
		$this->register_cron();
	}

	private function register_post_types(): void {
		( new CountryGuideCPT() )->register();
		( new GuideChapterCPT() )->register();
		( new ResourceCPT() )->register();
		( new FaqCPT() )->register();
	}

	private function register_meta_boxes(): void {
		( new GuideMetaBox() )->register();
		( new ChapterMetaBox() )->register();
		( new ResourceMetaBox() )->register();
		( new FaqMetaBox() )->register();
	}

	private function register_shortcodes(): void {
		add_action( 'init', static function (): void {
			( new ShortcodeManager() )->register();
		} );
	}

	private function register_elementor(): void {
		add_action( 'plugins_loaded', static function (): void {
			if ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) {
				( new ElementorIntegration() )->register();
			}
		}, 30 );
	}

	private function register_rest_routes(): void {
		add_action( 'rest_api_init', static function (): void {
			( new RestController() )->register_routes();
		} );
	}

	private function register_frontend(): void {
		( new Assets() )->register();
		ResourceDownloadService::register();
	}

	private function register_admin(): void {
		if ( is_admin() ) {
			( new AdminMenu() )->register();
		}
	}

	private function register_security_hooks(): void {
		add_filter( 'wp_authenticate_user', [ RegistrationService::class, 'guard_login' ], 10, 2 );
	}

	private function register_privacy(): void {
		( new GdprHandler() )->register();
	}

	private function register_cron(): void {
		add_action( 'ipfo_daily_maintenance', static function (): void {
			( new InvitationService() )->run_daily_maintenance();
		} );
	}
}
