<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\Repositories\ChecklistRepository;
use IPFO\Repositories\CountryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChecklistsPage {

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$countries = ( new CountryRepository() )->all();
		$repo      = new ChecklistRepository();
		$country_id = absint( $_GET['country_id'] ?? ( $countries[0]->id ?? 0 ) );
		$notice    = $this->handle_actions( $repo, $country_id );

		echo '<div class="wrap"><h1>' . esc_html__( 'Checklists', 'ipfo-country-guidance-portal' ) . '</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}

		if ( empty( $countries ) ) {
			echo '<p>' . esc_html__( 'Please add a country first.', 'ipfo-country-guidance-portal' ) . '</p></div>';
			return;
		}
		?>
		<form method="get" style="margin:16px 0;">
			<input type="hidden" name="page" value="ipfo-checklists" />
			<select name="country_id" onchange="this.form.submit()">
				<?php foreach ( $countries as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $country_id, $c->id ); ?>><?php echo esc_html( $c->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>

		<h2><?php esc_html_e( 'Add Checklist Item', 'ipfo-country-guidance-portal' ); ?></h2>
		<form method="post" style="max-width:640px;">
			<input type="hidden" name="ipfo_checklist_action" value="create" />
			<input type="hidden" name="country_id" value="<?php echo esc_attr( $country_id ); ?>" />
			<?php wp_nonce_field( 'ipfo_checklist', 'ipfo_checklist_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="label"><?php esc_html_e( 'Label', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="text" id="label" name="label" class="regular-text" required /></td>
				</tr>
				<tr>
					<th><label for="description"><?php esc_html_e( 'Description', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><textarea id="description" name="description" rows="2" class="large-text"></textarea></td>
				</tr>
			</table>
			<?php submit_button( __( 'Add Item', 'ipfo-country-guidance-portal' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Checklist Items', 'ipfo-country-guidance-portal' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Drag items to reorder, then click Save Order.', 'ipfo-country-guidance-portal' ); ?></p>
		<form method="post">
			<input type="hidden" name="ipfo_checklist_action" value="reorder" />
			<input type="hidden" name="country_id" value="<?php echo esc_attr( $country_id ); ?>" />
			<input type="hidden" name="ordered_ids" value="" data-ipfo-order-field />
			<?php wp_nonce_field( 'ipfo_checklist', 'ipfo_checklist_nonce' ); ?>
			<ul class="ipfo-admin-sortable" data-ipfo-sortable>
				<?php foreach ( $repo->items_for_country( $country_id ) as $item ) : ?>
					<li data-id="<?php echo esc_attr( $item->id ); ?>">
						<span><span class="dashicons dashicons-move"></span> <?php echo esc_html( $item->label ); ?></span>
						<span>
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'ipfo_delete_item' => $item->id ] ), 'ipfo_delete_checklist_item' ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Delete this item?', 'ipfo-country-guidance-portal' ) ); ?>');"><?php esc_html_e( 'Delete', 'ipfo-country-guidance-portal' ); ?></a>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php submit_button( __( 'Save Order', 'ipfo-country-guidance-portal' ), 'secondary' ); ?>
		</form>
		</div>
		<?php
	}

	private function handle_actions( ChecklistRepository $repo, int $country_id ): string {
		if ( isset( $_GET['ipfo_delete_item'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ipfo_delete_checklist_item' ) ) {
			$repo->delete_item( absint( $_GET['ipfo_delete_item'] ) );
			return __( 'Checklist item deleted.', 'ipfo-country-guidance-portal' );
		}

		if ( ! isset( $_POST['ipfo_checklist_action'] ) ) {
			return '';
		}

		check_admin_referer( 'ipfo_checklist', 'ipfo_checklist_nonce' );

		if ( 'create' === $_POST['ipfo_checklist_action'] ) {
			$repo->create_item(
				$country_id,
				sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
				sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) )
			);
			return __( 'Checklist item added.', 'ipfo-country-guidance-portal' );
		}

		if ( 'reorder' === $_POST['ipfo_checklist_action'] && ! empty( $_POST['ordered_ids'] ) ) {
			$ids = array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['ordered_ids'] ) ) ) );
			$repo->reorder( array_filter( $ids ) );
			return __( 'Checklist order saved.', 'ipfo-country-guidance-portal' );
		}

		return '';
	}
}
