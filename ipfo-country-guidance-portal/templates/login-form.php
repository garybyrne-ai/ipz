<?php
/**
 * @var string $error
 * @var string $notice
 * @var string $register_url
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-card ipfo-auth-card">
	<h2><?php esc_html_e( 'Guidance Centre Sign In', 'ipfo-country-guidance-portal' ); ?></h2>

	<?php if ( $error ) : ?>
		<p class="ipfo-form-error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>
	<?php if ( $notice ) : ?>
		<p class="ipfo-form-success"><?php echo esc_html( $notice ); ?></p>
	<?php endif; ?>

	<form method="post" novalidate>
		<input type="hidden" name="ipfo_action" value="login" />
		<?php wp_nonce_field( 'ipfo_login', 'ipfo_login_nonce' ); ?>

		<div class="ipfo-field">
			<label for="ipfo_email"><?php esc_html_e( 'Email Address', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="email" id="ipfo_email" name="ipfo_email" required autocomplete="email" />
		</div>

		<div class="ipfo-field">
			<label for="ipfo_password"><?php esc_html_e( 'Password', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="password" id="ipfo_password" name="ipfo_password" required autocomplete="current-password" />
		</div>

		<div class="ipfo-field ipfo-field--checkbox">
			<input type="checkbox" id="ipfo_remember" name="ipfo_remember" value="1" />
			<label for="ipfo_remember"><?php esc_html_e( 'Keep me signed in', 'ipfo-country-guidance-portal' ); ?></label>
		</div>

		<button type="submit" class="ipfo-btn ipfo-btn--primary" style="width:100%;"><?php esc_html_e( 'Sign In', 'ipfo-country-guidance-portal' ); ?></button>
	</form>

	<details style="margin-top:20px;">
		<summary style="cursor:pointer;color:var(--ipfo-primary);font-family:var(--ipfo-font-heading);font-weight:600;"><?php esc_html_e( 'Forgot your password?', 'ipfo-country-guidance-portal' ); ?></summary>
		<form method="post" style="margin-top:14px;">
			<input type="hidden" name="ipfo_action" value="forgot_password" />
			<?php wp_nonce_field( 'ipfo_forgot_password', 'ipfo_forgot_nonce' ); ?>
			<div class="ipfo-field">
				<label for="ipfo_forgot_email"><?php esc_html_e( 'Email Address', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="email" id="ipfo_forgot_email" name="ipfo_email" required />
			</div>
			<button type="submit" class="ipfo-btn ipfo-btn--ghost"><?php esc_html_e( 'Send Reset Link', 'ipfo-country-guidance-portal' ); ?></button>
		</form>
	</details>

	<p style="margin-top:24px;text-align:center;font-size:14px;">
		<?php esc_html_e( "Don't have an account?", 'ipfo-country-guidance-portal' ); ?>
		<a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Register', 'ipfo-country-guidance-portal' ); ?></a>
	</p>
</div>
