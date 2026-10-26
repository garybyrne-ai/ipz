<?php
/**
 * @var string $error
 * @var object[] $countries
 * @var \WP_Term[] $categories
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
	<h2><?php esc_html_e( 'Create Your Business Listing', 'ipfo-country-guidance-portal' ); ?></h2>

	<?php if ( $invite_code ) : ?>
		<p class="ipfo-form-success"><?php esc_html_e( "You're registering with a secure invitation.", 'ipfo-country-guidance-portal' ); ?> <span class="ipfo-badge"><?php echo esc_html( $invite_code ); ?></span></p>
	<?php endif; ?>

	<?php if ( $error ) : ?>
		<p class="ipfo-form-error"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<form method="post" novalidate>
		<input type="hidden" name="ipfo_action" value="business_register" />
		<input type="hidden" name="ipfo_invite" value="<?php echo esc_attr( $invite_code ); ?>" />
		<input type="hidden" name="ipfo_token" value="<?php echo esc_attr( $invite_token ); ?>" />
		<?php wp_nonce_field( 'ipfo_business_register', 'ipfo_business_register_nonce' ); ?>

		<div class="ipfo-field">
			<label for="ipfo_business_name"><?php esc_html_e( 'Business Name', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="text" id="ipfo_business_name" name="ipfo_business_name" required />
		</div>

		<div class="ipfo-grid ipfo-grid--2">
			<div class="ipfo-field">
				<label for="ipfo_first_name"><?php esc_html_e( 'Your First Name', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="text" id="ipfo_first_name" name="ipfo_first_name" required />
			</div>
			<div class="ipfo-field">
				<label for="ipfo_last_name"><?php esc_html_e( 'Your Last Name', 'ipfo-country-guidance-portal' ); ?></label>
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
			<label for="ipfo_category_id"><?php esc_html_e( 'Category', 'ipfo-country-guidance-portal' ); ?></label>
			<select id="ipfo_category_id" name="ipfo_category_id" required>
				<option value=""><?php esc_html_e( 'Select a category', 'ipfo-country-guidance-portal' ); ?></option>
				<?php foreach ( $categories as $term ) : ?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php
		echo ipfo_render_template(
			'partials/country-picker.php',
			[
				'countries'             => $countries,
				'selected_country_ids'  => [],
				'worldwide_checked'     => false,
			]
		);
		?>

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
			<label for="ipfo_terms"><?php esc_html_e( 'I accept the Terms & Conditions for directory listings.', 'ipfo-country-guidance-portal' ); ?></label>
		</div>

		<button type="submit" class="ipfo-btn ipfo-btn--primary" style="width:100%;"><?php esc_html_e( 'Create Listing', 'ipfo-country-guidance-portal' ); ?></button>
	</form>

	<p style="margin-top:24px;text-align:center;font-size:14px;">
		<?php esc_html_e( 'Already have an account?', 'ipfo-country-guidance-portal' ); ?>
		<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Sign In', 'ipfo-country-guidance-portal' ); ?></a>
	</p>
</div>
