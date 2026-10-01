<?php
/**
 * @var string $error
 * @var object[] $countries
 * @var string $invite_code
 * @var string $invite_token
 * @var string $login_url
 * @var string $disclaimer
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-card ipfo-auth-card" style="max-width:560px;">
	<h2><?php esc_html_e( 'Create Your Guidance Centre Account', 'ipfo-country-guidance-portal' ); ?></h2>

	<?php if ( $invite_code ) : ?>
		<p class="ipfo-form-success"><?php esc_html_e( "You're registering with a secure invitation.", 'ipfo-country-guidance-portal' ); ?> <span class="ipfo-badge"><?php echo esc_html( $invite_code ); ?></span></p>
	<?php endif; ?>

	<?php if ( $error ) : ?>
		<p class="ipfo-form-error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<form method="post" novalidate>
		<input type="hidden" name="ipfo_action" value="register" />
		<input type="hidden" name="ipfo_invite" value="<?php echo esc_attr( $invite_code ); ?>" />
		<input type="hidden" name="ipfo_token" value="<?php echo esc_attr( $invite_token ); ?>" />
		<?php wp_nonce_field( 'ipfo_register', 'ipfo_register_nonce' ); ?>

		<div class="ipfo-grid ipfo-grid--2">
			<div class="ipfo-field">
				<label for="ipfo_first_name"><?php esc_html_e( 'First Name', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="text" id="ipfo_first_name" name="ipfo_first_name" required />
			</div>
			<div class="ipfo-field">
				<label for="ipfo_last_name"><?php esc_html_e( 'Last Name', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="text" id="ipfo_last_name" name="ipfo_last_name" required />
			</div>
		</div>

		<div class="ipfo-field">
			<label for="ipfo_email"><?php esc_html_e( 'Email Address', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="email" id="ipfo_email" name="ipfo_email" required />
		</div>

		<div class="ipfo-field">
			<label for="ipfo_password"><?php esc_html_e( 'Password', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="password" id="ipfo_password" name="ipfo_password" required minlength="10" autocomplete="new-password" />
		</div>

		<div class="ipfo-field">
			<label for="ipfo_country_id"><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></label>
			<select id="ipfo_country_id" name="ipfo_country_id" <?php echo $invite_code ? 'disabled' : 'required'; ?>>
				<option value=""><?php esc_html_e( 'Select your country', 'ipfo-country-guidance-portal' ); ?></option>
				<?php foreach ( $countries as $country ) : ?>
					<option value="<?php echo esc_attr( $country->id ); ?>"><?php echo esc_html( $country->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( $invite_code ) : ?>
				<p style="font-size:12px;color:var(--ipfo-text);margin-top:6px;"><?php esc_html_e( 'Your country has been set by your invitation.', 'ipfo-country-guidance-portal' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ipfo-field">
			<label for="ipfo_phone"><?php esc_html_e( 'Phone', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="tel" id="ipfo_phone" name="ipfo_phone" />
		</div>

		<div class="ipfo-notice ipfo-notice--disclaimer"><?php echo esc_html( $disclaimer ); ?></div>

		<div class="ipfo-field ipfo-field--checkbox">
			<input type="checkbox" id="ipfo_consent" name="ipfo_consent" value="1" required />
			<label for="ipfo_consent"><?php esc_html_e( 'I consent to the processing of my personal data as described in the Privacy Policy.', 'ipfo-country-guidance-portal' ); ?></label>
		</div>
		<div class="ipfo-field ipfo-field--checkbox">
			<input type="checkbox" id="ipfo_terms" name="ipfo_terms" value="1" required />
			<label for="ipfo_terms"><?php esc_html_e( 'I accept the Terms & Conditions.', 'ipfo-country-guidance-portal' ); ?></label>
		</div>

		<button type="submit" class="ipfo-btn ipfo-btn--primary" style="width:100%;"><?php esc_html_e( 'Create Account', 'ipfo-country-guidance-portal' ); ?></button>
	</form>

	<p style="margin-top:24px;text-align:center;font-size:14px;">
		<?php esc_html_e( 'Already have an account?', 'ipfo-country-guidance-portal' ); ?>
		<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign In', 'ipfo-country-guidance-portal' ); ?></a>
	</p>
</div>
