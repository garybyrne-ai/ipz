<?php
declare( strict_types = 1 );

namespace IPFO\Admin\MetaBoxes;

use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\CountryRepository;
use IPFO\Services\GuideService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuideMetaBox {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add' ] );
		add_action( 'save_post_' . CountryGuideCPT::SLUG, [ $this, 'save' ] );
	}

	public function add(): void {
		add_meta_box( 'ipfo_guide_details', __( 'Guide Details', 'ipfo-country-guidance-portal' ), [ $this, 'render' ], CountryGuideCPT::SLUG, 'normal', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'ipfo_save_guide', 'ipfo_guide_nonce' );

		$country_id   = (int) get_post_meta( $post->ID, '_ipfo_country_id', true );
		$version      = get_post_meta( $post->ID, '_ipfo_version', true ) ?: '1.0';
		$pub_date     = get_post_meta( $post->ID, '_ipfo_publication_date', true );
		$last_review  = get_post_meta( $post->ID, '_ipfo_last_reviewed', true );
		$next_review  = get_post_meta( $post->ID, '_ipfo_next_review', true );
		$download     = get_post_meta( $post->ID, '_ipfo_download_enabled', true );
		$viewing      = get_post_meta( $post->ID, '_ipfo_online_viewing_enabled', true ) !== '0';
		$ack_required = get_post_meta( $post->ID, '_ipfo_ack_required', true );
		$pdf_id       = (int) get_post_meta( $post->ID, '_ipfo_pdf_resource_id', true );

		$countries = ( new CountryRepository() )->all();
		$resources = get_posts( [ 'post_type' => ResourceCPT::SLUG, 'posts_per_page' => -1 ] );
		?>
		<table class="form-table">
			<tr>
				<th><label for="ipfo_country_id"><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></label></th>
				<td>
					<select name="ipfo_country_id" id="ipfo_country_id">
						<option value=""><?php esc_html_e( '— Select —', 'ipfo-country-guidance-portal' ); ?></option>
						<?php foreach ( $countries as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $country_id, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="ipfo_version"><?php esc_html_e( 'Version', 'ipfo-country-guidance-portal' ); ?></label></th>
				<td><input type="text" id="ipfo_version" name="ipfo_version" value="<?php echo esc_attr( $version ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Publication Date', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="date" name="ipfo_publication_date" value="<?php echo esc_attr( $pub_date ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Last Reviewed', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="date" name="ipfo_last_reviewed" value="<?php echo esc_attr( substr( (string) $last_review, 0, 10 ) ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Next Review Due', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="date" name="ipfo_next_review" value="<?php echo esc_attr( $next_review ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Online Viewing Enabled', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="checkbox" name="ipfo_online_viewing_enabled" value="1" <?php checked( $viewing ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'PDF Download Enabled', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<input type="checkbox" name="ipfo_download_enabled" value="1" <?php checked( $download ); ?> />
					<select name="ipfo_pdf_resource_id">
						<option value=""><?php esc_html_e( '— Select PDF resource —', 'ipfo-country-guidance-portal' ); ?></option>
						<?php foreach ( $resources as $r ) : ?>
							<option value="<?php echo esc_attr( $r->ID ); ?>" <?php selected( $pdf_id, $r->ID ); ?>><?php echo esc_html( get_the_title( $r ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Acknowledgement Required', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="checkbox" name="ipfo_ack_required" value="1" <?php checked( $ack_required ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Publish As New Version', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<label><input type="checkbox" name="ipfo_publish_new_version" value="1" /> <?php esc_html_e( 'Record this save as a new version and notify assigned clients', 'ipfo-country-guidance-portal' ); ?></label>
					<p><textarea name="ipfo_version_changelog" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'What changed in this version…', 'ipfo-country-guidance-portal' ); ?>"></textarea></p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['ipfo_guide_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_guide_nonce'] ) ), 'ipfo_save_guide' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_ipfo_country_id', absint( $_POST['ipfo_country_id'] ?? 0 ) );
		update_post_meta( $post_id, '_ipfo_version', sanitize_text_field( wp_unslash( $_POST['ipfo_version'] ?? '1.0' ) ) );
		update_post_meta( $post_id, '_ipfo_publication_date', sanitize_text_field( wp_unslash( $_POST['ipfo_publication_date'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_last_reviewed', sanitize_text_field( wp_unslash( $_POST['ipfo_last_reviewed'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_next_review', sanitize_text_field( wp_unslash( $_POST['ipfo_next_review'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_online_viewing_enabled', ! empty( $_POST['ipfo_online_viewing_enabled'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_ipfo_download_enabled', ! empty( $_POST['ipfo_download_enabled'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_ipfo_pdf_resource_id', absint( $_POST['ipfo_pdf_resource_id'] ?? 0 ) );
		update_post_meta( $post_id, '_ipfo_ack_required', ! empty( $_POST['ipfo_ack_required'] ) ? '1' : '0' );

		if ( ! empty( $_POST['ipfo_publish_new_version'] ) ) {
			remove_action( 'save_post_' . CountryGuideCPT::SLUG, [ $this, 'save' ] );
			( new GuideService() )->publish_new_version(
				$post_id,
				sanitize_text_field( wp_unslash( $_POST['ipfo_version'] ?? '1.0' ) ),
				wp_kses_post( wp_unslash( $_POST['ipfo_version_changelog'] ?? '' ) ),
				! empty( $_POST['ipfo_ack_required'] )
			);
			add_action( 'save_post_' . CountryGuideCPT::SLUG, [ $this, 'save' ] );
		}
	}
}
