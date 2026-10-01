<?php
declare( strict_types = 1 );

namespace IPFO\Admin\MetaBoxes;

use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\CountryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResourceMetaBox {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add' ] );
		add_action( 'save_post_' . ResourceCPT::SLUG, [ $this, 'save' ] );
	}

	public function add(): void {
		add_meta_box( 'ipfo_resource_details', __( 'Resource Settings', 'ipfo-country-guidance-portal' ), [ $this, 'render' ], ResourceCPT::SLUG, 'normal', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'ipfo_save_resource', 'ipfo_resource_nonce' );

		$type          = get_post_meta( $post->ID, '_ipfo_resource_type', true ) ?: 'file';
		$attachment_id = (int) get_post_meta( $post->ID, '_ipfo_attachment_id', true );
		$external_url  = get_post_meta( $post->ID, '_ipfo_external_url', true );
		$country_id    = (int) get_post_meta( $post->ID, '_ipfo_country_id', true );
		$guide_id      = (int) get_post_meta( $post->ID, '_ipfo_guide_id', true );
		$category      = get_post_meta( $post->ID, '_ipfo_category', true );
		$view_enabled  = get_post_meta( $post->ID, '_ipfo_view_enabled', true ) !== '0';
		$download_on   = get_post_meta( $post->ID, '_ipfo_download_enabled', true );
		$expires_at    = get_post_meta( $post->ID, '_ipfo_expires_at', true );

		$countries = ( new CountryRepository() )->all();
		$guides    = get_posts( [ 'post_type' => CountryGuideCPT::SLUG, 'posts_per_page' => -1 ] );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Resource Type', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<select name="ipfo_resource_type" id="ipfo_resource_type">
						<option value="file" <?php selected( $type, 'file' ); ?>><?php esc_html_e( 'Uploaded File (PDF, DOCX, image…)', 'ipfo-country-guidance-portal' ); ?></option>
						<option value="video" <?php selected( $type, 'video' ); ?>><?php esc_html_e( 'Video Link (YouTube/Vimeo)', 'ipfo-country-guidance-portal' ); ?></option>
						<option value="external_link" <?php selected( $type, 'external_link' ); ?>><?php esc_html_e( 'External Link', 'ipfo-country-guidance-portal' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'File', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<input type="hidden" id="ipfo_attachment_id" name="ipfo_attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
					<button type="button" class="button" id="ipfo_media_button"><?php esc_html_e( 'Select File', 'ipfo-country-guidance-portal' ); ?></button>
					<span id="ipfo_media_filename"><?php echo $attachment_id ? esc_html( basename( (string) get_attached_file( $attachment_id ) ) ) : ''; ?></span>
					<p class="description"><?php esc_html_e( 'This file is never publicly linked — it is only ever served through the secure, permission-checked download endpoint.', 'ipfo-country-guidance-portal' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'External / Video URL', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="url" name="ipfo_external_url" class="regular-text" value="<?php echo esc_attr( $external_url ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<select name="ipfo_country_id">
						<option value=""><?php esc_html_e( '— Any / portal-wide —', 'ipfo-country-guidance-portal' ); ?></option>
						<?php foreach ( $countries as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $country_id, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Guide', 'ipfo-country-guidance-portal' ); ?></th>
				<td>
					<select name="ipfo_guide_id">
						<option value=""><?php esc_html_e( '— None —', 'ipfo-country-guidance-portal' ); ?></option>
						<?php foreach ( $guides as $g ) : ?>
							<option value="<?php echo esc_attr( $g->ID ); ?>" <?php selected( $guide_id, $g->ID ); ?>><?php echo esc_html( get_the_title( $g ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Category', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="text" name="ipfo_category" class="regular-text" value="<?php echo esc_attr( $category ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'View Enabled', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="checkbox" name="ipfo_view_enabled" value="1" <?php checked( $view_enabled ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Download Enabled', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="checkbox" name="ipfo_download_enabled" value="1" <?php checked( $download_on ); ?> /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Expires On', 'ipfo-country-guidance-portal' ); ?></th>
				<td><input type="date" name="ipfo_expires_at" value="<?php echo esc_attr( $expires_at ); ?>" /></td>
			</tr>
		</table>
		<script>
		jQuery(function($){
			var frame;
			$('#ipfo_media_button').on('click', function(e){
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({ title: '<?php echo esc_js( __( 'Select Resource File', 'ipfo-country-guidance-portal' ) ); ?>', multiple: false });
				frame.on('select', function(){
					var a = frame.state().get('selection').first().toJSON();
					$('#ipfo_attachment_id').val(a.id);
					$('#ipfo_media_filename').text(a.filename || a.url);
				});
				frame.open();
			});
		});
		</script>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['ipfo_resource_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_resource_nonce'] ) ), 'ipfo_save_resource' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_ipfo_resource_type', sanitize_key( wp_unslash( $_POST['ipfo_resource_type'] ?? 'file' ) ) );
		update_post_meta( $post_id, '_ipfo_attachment_id', absint( $_POST['ipfo_attachment_id'] ?? 0 ) );
		update_post_meta( $post_id, '_ipfo_external_url', esc_url_raw( wp_unslash( $_POST['ipfo_external_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_country_id', absint( $_POST['ipfo_country_id'] ?? 0 ) );
		update_post_meta( $post_id, '_ipfo_guide_id', absint( $_POST['ipfo_guide_id'] ?? 0 ) );
		update_post_meta( $post_id, '_ipfo_category', sanitize_text_field( wp_unslash( $_POST['ipfo_category'] ?? '' ) ) );
		update_post_meta( $post_id, '_ipfo_view_enabled', ! empty( $_POST['ipfo_view_enabled'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_ipfo_download_enabled', ! empty( $_POST['ipfo_download_enabled'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_ipfo_expires_at', sanitize_text_field( wp_unslash( $_POST['ipfo_expires_at'] ?? '' ) ) );
	}
}
