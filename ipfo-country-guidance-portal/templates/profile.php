<?php
/**
 * @var \WP_User $user
 * @var object|null $country
 * @var string $phone
 * @var string $notice
 * @var string $error
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h1><?php esc_html_e( 'My Profile', 'ipfo-country-guidance-portal' ); ?></h1>

<?php if ( $notice ) : ?><p class="ipfo-form-success"><?php echo esc_html( $notice ); ?></p><?php endif; ?>
<?php if ( $error ) : ?><p class="ipfo-form-error"><?php echo esc_html( $error ); ?></p><?php endif; ?>

<div class="ipfo-profile-grid">
	<div class="ipfo-card">
		<h3><?php esc_html_e( 'Your Details', 'ipfo-country-guidance-portal' ); ?></h3>
		<form method="post">
			<input type="hidden" name="ipfo_action" value="update_profile" />
			<?php wp_nonce_field( 'ipfo_profile', 'ipfo_profile_nonce' ); ?>
			<div class="ipfo-grid ipfo-grid--2">
				<div class="ipfo-field">
					<label><?php esc_html_e( 'First Name', 'ipfo-country-guidance-portal' ); ?></label>
					<input type="text" name="ipfo_first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
				</div>
				<div class="ipfo-field">
					<label><?php esc_html_e( 'Last Name', 'ipfo-country-guidance-portal' ); ?></label>
					<input type="text" name="ipfo_last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
				</div>
			</div>
			<div class="ipfo-field">
				<label><?php esc_html_e( 'Email', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" disabled />
			</div>
			<div class="ipfo-field">
				<label><?php esc_html_e( 'Phone', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="tel" name="ipfo_phone" value="<?php echo esc_attr( $phone ); ?>" />
			</div>
			<div class="ipfo-field">
				<label><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="text" value="<?php echo esc_attr( $country->name ?? __( 'Not yet assigned', 'ipfo-country-guidance-portal' ) ); ?>" disabled />
			</div>
			<button type="submit" class="ipfo-btn ipfo-btn--primary"><?php esc_html_e( 'Save Changes', 'ipfo-country-guidance-portal' ); ?></button>
		</form>
	</div>

	<div class="ipfo-card">
		<h3><?php esc_html_e( 'Change Password', 'ipfo-country-guidance-portal' ); ?></h3>
		<form method="post">
			<input type="hidden" name="ipfo_action" value="change_password" />
			<?php wp_nonce_field( 'ipfo_change_password', 'ipfo_password_nonce' ); ?>
			<div class="ipfo-field">
				<label><?php esc_html_e( 'Current Password', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="password" name="ipfo_current_password" autocomplete="current-password" />
			</div>
			<div class="ipfo-field">
				<label><?php esc_html_e( 'New Password', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="password" name="ipfo_new_password" minlength="10" autocomplete="new-password" />
			</div>
			<div class="ipfo-field">
				<label><?php esc_html_e( 'Confirm New Password', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="password" name="ipfo_new_password_confirm" minlength="10" autocomplete="new-password" />
			</div>
			<button type="submit" class="ipfo-btn ipfo-btn--primary"><?php esc_html_e( 'Change Password', 'ipfo-country-guidance-portal' ); ?></button>
		</form>

		<hr style="margin:28px 0;border-color:var(--ipfo-border);" />

		<h3><?php esc_html_e( 'Your Data', 'ipfo-country-guidance-portal' ); ?></h3>
		<p style="font-size:13px;"><?php esc_html_e( 'You can request a copy of your personal data, or request that it be erased, at any time.', 'ipfo-country-guidance-portal' ); ?></p>
		<div style="display:flex;gap:10px;">
			<form method="post">
				<input type="hidden" name="ipfo_action" value="gdpr_export" />
				<?php wp_nonce_field( 'ipfo_gdpr', 'ipfo_gdpr_nonce' ); ?>
				<button type="submit" class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm"><?php esc_html_e( 'Request My Data', 'ipfo-country-guidance-portal' ); ?></button>
			</form>
			<form method="post">
				<input type="hidden" name="ipfo_action" value="gdpr_erase" />
				<?php wp_nonce_field( 'ipfo_gdpr', 'ipfo_gdpr_nonce' ); ?>
				<button type="submit" class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm"><?php esc_html_e( 'Request Account Deletion', 'ipfo-country-guidance-portal' ); ?></button>
			</form>
		</div>
	</div>
</div>
