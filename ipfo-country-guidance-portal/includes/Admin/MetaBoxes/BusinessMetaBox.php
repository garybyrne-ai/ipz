<?php
declare( strict_types = 1 );

namespace IPFO\Admin\MetaBoxes;

use IPFO\CPT\BusinessCPT;
use IPFO\Repositories\CountryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BusinessMetaBox {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add' ] );
		add_action( 'save_post_' . BusinessCPT::SLUG, [ $this, 'save' ] );
	}

	public function add(): void {
		add_meta_box( 'ipfo_business_details', __( 'Business Details', 'ipfo-country-guidance-portal' ), [ $this, 'render' ], BusinessCPT::SLUG, 'normal', 'high' );
		add_meta_box( 'ipfo_business_owner', __( 'Listing Owner', 'ipfo-country-guidance-portal' ), [ $this, 'render_owner' ], BusinessCPT::SLUG, 'side', 'default' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'ipfo_save_business', 'ipfo_business_nonce' );

		$category_id   = (int) current( wp_get_post_terms( $post->ID, BusinessCPT::TAXONOMY, [ 'fields' => 'ids' ] ) ?: [] );
		$country_ids    = (array) json_decode( (string) get_post_meta( $post->ID, '_ipfo_country_ids', true ), true );
		$phone          = get_post_meta( $post->ID, '_ipfo_phone', true );
		$email          = get_post_meta( $post->ID, '_ipfo_email', true );
		$website        = get_post_meta( $post->ID, '_ipfo_website', true );
		$address        = get_post_meta( $post->ID, '_ipfo_address', true );
		$facebook       = get_post_meta( $post->ID, '_ipfo_social_facebook', true );
		$instagram      = get_post_meta( $post->ID, '_ipfo_social_instagram', true );
		$linkedin       = get_post_meta( $post->ID, '_ipfo_social_linkedin', true );
		$credentials    = get_post_meta( $post->ID, '_ipfo_credentials', true );
		$featured       = '1' === get_post_meta( $post->ID, '_ipfo_featured', true );

		$categories = get_terms( [ 'taxonomy' => BusinessCPT::TAXONOMY, 'hide_empty' => false ] );
		$countries  = ( new CountryRepository() )->all();
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Category', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<select name="ipfo_category_id">
						<option value=""><?php esc_html_e( '— None —', 'ipfo-country-guidance-portal' ); ?></option>
						<?php foreach ( $categories as $term ) : ?>
							<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $category_id, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Countries Served', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<select name="ipfo_country_ids[]" multiple size="5" style="min-width:260px;">
						<?php foreach ( $countries as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( in_array( (int) $c->id, array_map( 'intval', $country_ids ), true ) ); ?>><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Phone', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="text" name="ipfo_business_phone" class="regular-text" value="<?php echo esc_attr( $phone ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Email', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="email" name="ipfo_business_email" class="regular-text" value="<?php echo esc_attr( $email ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Website', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="url" name="ipfo_business_website" class="regular-text" value="<?php echo esc_attr( $website ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Address', 'ipfo-country-guidance-portal' ); ?></th>
				<td><textarea name="ipfo_business_address" rows="3" class="large-text"><?php echo esc_textarea( $address ); ?></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Facebook URL', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="url" name="ipfo_social_facebook" class="regular-text" value="<?php echo esc_attr( $facebook ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Instagram URL', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="url" name="ipfo_social_instagram" class="regular-text" value="<?php echo esc_attr( $instagram ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'LinkedIn URL', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="url" name="ipfo_social_linkedin" class="regular-text" value="<?php echo esc_attr( $linkedin ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Credentials / Certifications', 'ipfo-country-guidance-portal' ); ?></th>
				<td><textarea name="ipfo_credentials" rows="3" class="large-text"><?php echo esc_textarea( $credentials ); ?></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Featured', 'ipfo-country-guidance-portal' ); ?></th>
				<td><label><input type="checkbox" name="ipfo_featured" value="1" <?php checked( $featured ); ?> /> <?php esc_html_e( 'Show this listing near the top of the directory.', 'ipfo-country-guidance-portal' ); ?></label></td>
			</tr>
		</table>
		<p class="description"><?php esc_html_e( 'Set this listing to Published to make it publicly visible in the directory. Use the native Status field above to approve, unpublish or trash a listing.', 'ipfo-country-guidance-portal' ); ?></p>
		<?php
	}

	public function render_owner( \WP_Post $post ): void {
		$owner = get_userdata( (int) $post->post_author );

		if ( ! $owner ) {
			echo '<p>' . esc_html__( 'No owner account (created directly in wp-admin).', 'ipfo-country-guidance-portal' ) . '</p>';
			return;
		}

		echo '<p><strong>' . esc_html( $owner->display_name ) . '</strong><br />' . esc_html( $owner->user_email ) . '</p>';
		echo '<p><a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $owner->ID ) ) . '">' . esc_html__( 'View account', 'ipfo-country-guidance-portal' ) . '</a></p>';
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['ipfo_business_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_business_nonce'] ) ), 'ipfo_save_business' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$category_id = absint( $_POST['ipfo_category_id'] ?? 0 );
		wp_set_post_terms( $post_id, $category_id ? [ $category_id ] : [], BusinessCPT::TAXONOMY );

		$country_ids = array_map( 'absint', (array) ( $_POST['ipfo_country_ids'] ?? [] ) );
		update_post_meta( $post_id, '_ipfo_country_ids', wp_json_encode( array_values( array_filter( $country_ids ) ) ) );

		update_post_meta( $post_id, '_ipfo_phone', sanitize_text_field( wp_unslash( $_POST['ipfo_business_phone'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_email', sanitize_email( wp_unslash( $_POST['ipfo_business_email'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_website', esc_url_raw( wp_unslash( $_POST['ipfo_business_website'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_address', sanitize_textarea_field( wp_unslash( $_POST['ipfo_business_address'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_social_facebook', esc_url_raw( wp_unslash( $_POST['ipfo_social_facebook'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_social_instagram', esc_url_raw( wp_unslash( $_POST['ipfo_social_instagram'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_social_linkedin', esc_url_raw( wp_unslash( $_POST['ipfo_social_linkedin'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_credentials', sanitize_textarea_field( wp_unslash( $_POST['ipfo_credentials'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_featured', ! empty( $_POST['ipfo_featured'] ) ? '1' : '0' );
	}
}
