<?php
/**
 * Full-page wrapper for the single business permalink, returned directly
 * from template_include (see Frontend/SingleBusinessTemplate.php). Calling
 * get_header()/get_footer() here — rather than echoing from inside the
 * filter callback — keeps this a normal, self-contained WordPress template
 * that the active theme's own header/footer render exactly as they would
 * for any other page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$business = get_queried_object();

get_header();

if ( $business instanceof WP_Post ) {
	echo ipfo_render_template( 'single-business.php', [ 'business' => $business ] ); // phpcs:ignore WordPress.Security.EscapeOutput
}

get_footer();
