<?php
declare( strict_types = 1 );

namespace IPFO\Admin\MetaBoxes;

use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\GuideChapterCPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChapterMetaBox {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add' ] );
		add_action( 'save_post_' . GuideChapterCPT::SLUG, [ $this, 'save' ] );
	}

	public function add(): void {
		add_meta_box( 'ipfo_chapter_details', __( 'Chapter Settings', 'ipfo-country-guidance-portal' ), [ $this, 'render' ], GuideChapterCPT::SLUG, 'side', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'ipfo_save_chapter', 'ipfo_chapter_nonce' );

		$guide_id = (int) get_post_meta( $post->ID, '_ipfo_guide_id', true );
		$guides   = get_posts( [ 'post_type' => CountryGuideCPT::SLUG, 'posts_per_page' => -1 ] );
		?>
		<p>
			<label for="ipfo_guide_id"><strong><?php esc_html_e( 'Belongs to Guide', 'ipfo-country-guidance-portal' ); ?></strong></label><br />
			<select name="ipfo_guide_id" id="ipfo_guide_id" style="width:100%;">
				<option value=""><?php esc_html_e( '— Select —', 'ipfo-country-guidance-portal' ); ?></option>
				<?php foreach ( $guides as $g ) : ?>
					<option value="<?php echo esc_attr( $g->ID ); ?>" <?php selected( $guide_id, $g->ID ); ?>><?php echo esc_html( get_the_title( $g ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description"><?php esc_html_e( 'Use the Order field (Page Attributes) to control where this chapter appears in the table of contents.', 'ipfo-country-guidance-portal' ); ?></p>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['ipfo_chapter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_chapter_nonce'] ) ), 'ipfo_save_chapter' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_ipfo_guide_id', absint( $_POST['ipfo_guide_id'] ?? 0 ) );
	}
}
