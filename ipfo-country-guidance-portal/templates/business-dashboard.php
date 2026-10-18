<?php
/**
 * @var string $notice
 * @var string $error
 * @var \WP_Post $listing
 * @var string $status
 * @var int $category_id
 * @var int[] $country_ids
 * @var \WP_Term[] $categories
 * @var object[] $countries
 * @var string $listing_url
 * @var string $phone
 * @var string $email
 * @var string $website
 * @var string $address
 * @var string $facebook
 * @var string $instagram
 * @var string $linkedin
 * @var string $credentials
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_labels = [
	'publish' => __( 'Live in the directory', 'ipfo-country-guidance-portal' ),
	'pending' => __( 'Pending review', 'ipfo-country-guidance-portal' ),
	'draft'   => __( 'Draft', 'ipfo-country-guidance-portal' ),
	'trash'   => __( 'Removed', 'ipfo-country-guidance-portal' ),
];
?>
<div class="ipfo-welcome">
	<h1><?php esc_html_e( 'Your Business Listing', 'ipfo-country-guidance-portal' ); ?></h1>
	<p>
		<span class="ipfo-pill ipfo-pill--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_labels[ $status ] ?? ucfirst( $status ) ); ?></span>
		<?php if ( $listing_url ) : ?>
			<a href="<?php echo esc_url( $listing_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View your public listing →', 'ipfo-country-guidance-portal' ); ?></a>
		<?php elseif ( 'pending' === $status ) : ?>
			<span style="font-size:14px;color:var(--ipfo-text);"><?php esc_html_e( 'Your listing will appear in the public directory once an administrator approves it.', 'ipfo-country-guidance-portal' ); ?></span>
		<?php endif; ?>
	</p>
</div>

<?php if ( $notice ) : ?>
	<p class="ipfo-form-success"><?php echo esc_html( $notice ); ?></p>
<?php endif; ?>
<?php if ( $error ) : ?>
	<p class="ipfo-form-error"><?php echo esc_html( $error ); ?></p>
<?php endif; ?>

<div class="ipfo-card">
	<form method="post" enctype="multipart/form-data" novalidate>
		<input type="hidden" name="ipfo_action" value="business_update" />
		<?php wp_nonce_field( 'ipfo_business_dashboard', 'ipfo_business_dashboard_nonce' ); ?>

		<div class="ipfo-field">
			<label for="ipfo_business_name"><?php esc_html_e( 'Business Name', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="text" id="ipfo_business_name" name="ipfo_business_name" value="<?php echo esc_attr( $listing->post_title ); ?>" required />
		</div>

		<div class="ipfo-field">
			<label for="ipfo_description"><?php esc_html_e( 'Description', 'ipfo-country-guidance-portal' ); ?></label>
			<textarea id="ipfo_description" name="ipfo_description" rows="5" class="large-text"><?php echo esc_textarea( $listing->post_content ); ?></textarea>
		</div>

		<div class="ipfo-grid ipfo-grid--2">
			<div class="ipfo-field">
				<label for="ipfo_category_id"><?php esc_html_e( 'Category', 'ipfo-country-guidance-portal' ); ?></label>
				<select id="ipfo_category_id" name="ipfo_category_id" required>
					<?php foreach ( $categories as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $category_id, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="ipfo-field">
				<label for="ipfo_country_ids"><?php esc_html_e( 'Countries You Serve', 'ipfo-country-guidance-portal' ); ?></label>
				<select id="ipfo_country_ids" name="ipfo_country_ids[]" multiple size="4">
					<?php foreach ( $countries as $country ) : ?>
						<option value="<?php echo esc_attr( $country->id ); ?>" <?php selected( in_array( (int) $country->id, $country_ids, true ) ); ?>><?php echo esc_html( $country->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ipfo-field">
			<label for="ipfo_logo"><?php esc_html_e( 'Logo / Photo', 'ipfo-country-guidance-portal' ); ?></label>
			<?php if ( has_post_thumbnail( $listing ) ) : ?>
				<p><?php echo get_the_post_thumbnail( $listing, [ 80, 80 ], [ 'style' => 'border-radius:var(--ipfo-radius-sm);' ] ); ?></p>
			<?php endif; ?>
			<input type="file" id="ipfo_logo" name="ipfo_logo" accept="image/*" />
		</div>

		<div class="ipfo-grid ipfo-grid--2">
			<div class="ipfo-field">
				<label for="ipfo_phone"><?php esc_html_e( 'Phone', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="tel" id="ipfo_phone" name="ipfo_phone" value="<?php echo esc_attr( $phone ); ?>" />
			</div>
			<div class="ipfo-field">
				<label for="ipfo_email"><?php esc_html_e( 'Public Email', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="email" id="ipfo_email" name="ipfo_email" value="<?php echo esc_attr( $email ); ?>" />
			</div>
		</div>

		<div class="ipfo-field">
			<label for="ipfo_website"><?php esc_html_e( 'Website', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="url" id="ipfo_website" name="ipfo_website" value="<?php echo esc_attr( $website ); ?>" />
		</div>

		<div class="ipfo-field">
			<label for="ipfo_address"><?php esc_html_e( 'Address', 'ipfo-country-guidance-portal' ); ?></label>
			<textarea id="ipfo_address" name="ipfo_address" rows="2" class="large-text"><?php echo esc_textarea( $address ); ?></textarea>
		</div>

		<div class="ipfo-grid ipfo-grid--2">
			<div class="ipfo-field">
				<label for="ipfo_facebook"><?php esc_html_e( 'Facebook URL', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="url" id="ipfo_facebook" name="ipfo_facebook" value="<?php echo esc_attr( $facebook ); ?>" />
			</div>
			<div class="ipfo-field">
				<label for="ipfo_instagram"><?php esc_html_e( 'Instagram URL', 'ipfo-country-guidance-portal' ); ?></label>
				<input type="url" id="ipfo_instagram" name="ipfo_instagram" value="<?php echo esc_attr( $instagram ); ?>" />
			</div>
		</div>

		<div class="ipfo-field">
			<label for="ipfo_linkedin"><?php esc_html_e( 'LinkedIn URL', 'ipfo-country-guidance-portal' ); ?></label>
			<input type="url" id="ipfo_linkedin" name="ipfo_linkedin" value="<?php echo esc_attr( $linkedin ); ?>" />
		</div>

		<div class="ipfo-field">
			<label for="ipfo_credentials"><?php esc_html_e( 'Credentials / Certifications', 'ipfo-country-guidance-portal' ); ?></label>
			<textarea id="ipfo_credentials" name="ipfo_credentials" rows="3" class="large-text"><?php echo esc_textarea( $credentials ); ?></textarea>
		</div>

		<button type="submit" class="ipfo-btn ipfo-btn--primary"><?php esc_html_e( 'Save Listing', 'ipfo-country-guidance-portal' ); ?></button>
	</form>
</div>
