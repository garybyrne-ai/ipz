<?php
declare( strict_types = 1 );

namespace IPFO\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single reusable Elementor widget shell that renders one of the
 * [IPFO_*] shortcodes. One instance is registered per shortcode tag with
 * its own slug/title/icon, so editors see 11 distinct, properly labelled
 * widgets in the "IPFO Portal" category rather than one generic block.
 */
final class ShortcodeWidget extends Widget_Base {

	private string $slug;
	private string $tag;
	private string $widget_title;
	private string $widget_icon;
	private bool $has_guide_control;
	private bool $has_label_control;

	public function __construct( string $slug, string $tag, string $title, string $icon, bool $guide_control = false, bool $label_control = false, $data = [], $args = null ) {
		$this->slug              = $slug;
		$this->tag               = $tag;
		$this->widget_title      = $title;
		$this->widget_icon       = $icon;
		$this->has_guide_control = $guide_control;
		$this->has_label_control = $label_control;

		parent::__construct( $data, $args );
	}

	public function get_name(): string {
		return $this->slug;
	}

	public function get_title(): string {
		return $this->widget_title;
	}

	public function get_icon(): string {
		return $this->widget_icon;
	}

	public function get_categories(): array {
		return [ 'ipfo-portal' ];
	}

	public function get_keywords(): array {
		return [ 'ipfo', 'portal', 'guidance', 'surrogacy' ];
	}

	protected function register_controls(): void {
		if ( ! $this->has_guide_control && ! $this->has_label_control ) {
			return;
		}

		$this->start_controls_section(
			'ipfo_section_settings',
			[ 'label' => __( 'Settings', 'ipfo-country-guidance-portal' ) ]
		);

		if ( $this->has_guide_control ) {
			$this->add_control(
				'guide_id',
				[
					'label'       => __( 'Default Guide ID (optional)', 'ipfo-country-guidance-portal' ),
					'type'        => Controls_Manager::NUMBER,
					'description' => __( 'Leave empty to use the ?ipfo_guide= URL parameter, which is how "Continue Reading" links normally work.', 'ipfo-country-guidance-portal' ),
				]
			);
		}

		if ( $this->has_label_control ) {
			$this->add_control(
				'label',
				[
					'label'   => __( 'Button Label', 'ipfo-country-guidance-portal' ),
					'type'    => Controls_Manager::TEXT,
					'default' => __( 'Sign Out', 'ipfo-country-guidance-portal' ),
				]
			);
		}

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$atts     = [];

		if ( $this->has_guide_control && ! empty( $settings['guide_id'] ) ) {
			$atts[] = 'guide_id="' . absint( $settings['guide_id'] ) . '"';
		}

		if ( $this->has_label_control && ! empty( $settings['label'] ) ) {
			$atts[] = 'label="' . esc_attr( $settings['label'] ) . '"';
		}

		echo do_shortcode( '[' . $this->tag . ( $atts ? ' ' . implode( ' ', $atts ) : '' ) . ']' );
	}
}
