<?php
/**
 * @var string $error
 * @var string $login
 * @var string $key
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-card ipfo-auth-card">
	<h2><?php esc_html_e( 'Choose a New Password', 'ipfo-country-guidance-portal' ); ?></h2>

	<?php if ( $error ) : ?>
		<p class="ipfo-form-error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<form method="post" novalidate>
		<input type="hidden" name="ipfo_action" value="reset_password" />
		<input type="hidden" name="ipfo_reset_login" value="<?php echo esc_attr( $login ); ?>" />
		<input type="hidden" name="ipfo_reset_key" value="<?php echo esc_attr( $key ); ?>" />
		<?php wp_nonce_field( 'ipfo_reset_password', 'ipfo_reset_nonce' ); ?>

		<div class="ipfo-field">
			<label for="ipfo_password"><?php esc_html_e( 'New Password', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="password" id="ipfo_password" name="ipfo_password" required minlength="10" autocomplete="new-password" />
		</div>
		<div class="ipfo-field">
			<label for="ipfo_password_confirm"><?php esc_html_e( 'Confirm New Password', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="password" id="ipfo_password_confirm" name="ipfo_password_confirm" required minlength="10" autocomplete="new-password" />
		</div>

		<button type="submit" class="ipfo-btn ipfo-btn--primary" style="width:100%;"><?php esc_html_e( 'Reset Password', 'ipfo-country-guidance-portal' ); ?></button>
	</form>
</div>
