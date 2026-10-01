<?php
/** @var string $dashboard_url */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-card ipfo-auth-card" style="text-align:center;">
	<h2><?php esc_html_e( "You're already signed in", 'ipfo-country-guidance-portal' ); ?></h2>
	<p><a class="ipfo-btn ipfo-btn--primary" href="<?php echo esc_url( $dashboard_url ); ?>"><?php esc_html_e( 'Go to your Guidance Centre', 'ipfo-country-guidance-portal' ); ?></a></p>
</div>
