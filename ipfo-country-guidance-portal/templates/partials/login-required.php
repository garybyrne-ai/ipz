<?php
/**
 * @var string $login_url
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-portal">
	<div class="ipfo-card ipfo-auth-card" style="text-align:center;">
		<h2><?php esc_html_e( 'Please sign in', 'ipfo-country-guidance-portal' ); ?></h2>
		<p><?php esc_html_e( 'This content is part of your private IP Fertility Options Guidance Centre. Please sign in to continue.', 'ipfo-country-guidance-portal' ); ?></p>
		<p><a class="ipfo-btn ipfo-btn--primary" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign In', 'ipfo-country-guidance-portal' ); ?></a></p>
	</div>
</div>
