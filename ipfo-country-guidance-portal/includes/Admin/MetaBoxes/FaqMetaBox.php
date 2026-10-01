<?php
declare( strict_types = 1 );

namespace IPFO\Admin\MetaBoxes;

use IPFO\CPT\FaqCPT;
use IPFO\Repositories\CountryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FaqMetaBox {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add' ] );
		add_action( 'save_post_' . FaqCPT::SLUG, [ $this, 'save' ] );
	}

	public function add(): void {
		add_meta_box( 'ipfo_faq_details', __( 'FAQ Settings', 'ipfo-country-guidance-portal' ), [ $this, 'render' ], FaqCPT::SLUG, 'side', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'ipfo_save_faq', 'ipfo_faq_nonce' );

		$country_id = (int) get_post_meta( $post->ID, '_ipfo_country_id', true );
		$countries  = ( new CountryRepository() )->all();
		?>
		<p>
			<label for="ipfo_country_id"><strong><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></strong></label><br />
			<select name="ipfo_country_id" id="ipfo_country_id" style="width:100%;">
				<option value=""><?php esc_html_e( '— Select —', 'ipfo-country-guidance-portal' ); ?></option>
				<?php foreach ( $countries as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $country_id, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description"><?php esc_html_e( 'Title = question, content = answer. Use Order (Page Attributes) to control display order.', 'ipfo-country-guidance-portal' ); ?></p>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['ipfo_faq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ipfo_faq_nonce'] ) ), 'ipfo_save_faq' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_ipfo_country_id', absint( $_POST['ipfo_country_id'] ?? 0 ) );
	}
}
