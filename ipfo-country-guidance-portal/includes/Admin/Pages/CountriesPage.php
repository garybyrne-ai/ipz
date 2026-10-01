<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\Repositories\CountryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CountriesPage {

	private CountryRepository $repo;

	public function __construct() {
		$this->repo = new CountryRepository();
	}

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$notice = $this->handle_actions();
		$editing = null;

		if ( isset( $_GET['edit'] ) ) {
			$editing = $this->repo->get( absint( $_GET['edit'] ) );
		}

		$countries = $this->repo->all();

		echo '<div class="wrap"><h1>' . esc_html__( 'Countries', 'ipfo-country-guidance-portal' ) . '</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}

		$this->render_form( $editing );
		$this->render_table( $countries );

		echo '</div>';
	}

	private function handle_actions(): string {
		if ( isset( $_POST['ipfo_country_action'] ) && 'save' === $_POST['ipfo_country_action'] ) {
			check_admin_referer( 'ipfo_save_country', 'ipfo_country_nonce' );

			$data = [
				'name'          => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'code'          => sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) ),
				'flag'          => sanitize_text_field( wp_unslash( $_POST['flag'] ?? '' ) ),
				'description'   => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
				'status'        => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
				'last_reviewed' => sanitize_text_field( wp_unslash( $_POST['last_reviewed'] ?? '' ) ),
			];

			$id = absint( $_POST['id'] ?? 0 );

			if ( $id ) {
				$this->repo->update( $id, $data );
				return __( 'Country updated.', 'ipfo-country-guidance-portal' );
			}

			$this->repo->create( $data );
			return __( 'Country created.', 'ipfo-country-guidance-portal' );
		}

		if ( isset( $_GET['ipfo_delete_country'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ipfo_delete_country' ) ) {
			$this->repo->delete( absint( $_GET['ipfo_delete_country'] ) );
			return __( 'Country deleted.', 'ipfo-country-guidance-portal' );
		}

		return '';
	}

	private function render_form( ?object $editing ): void {
		?>
		<h2><?php echo $editing ? esc_html__( 'Edit Country', 'ipfo-country-guidance-portal' ) : esc_html__( 'Add Country', 'ipfo-country-guidance-portal' ); ?></h2>
		<form method="post" style="max-width:640px;">
			<input type="hidden" name="ipfo_country_action" value="save" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $editing->id ?? '' ); ?>" />
			<?php wp_nonce_field( 'ipfo_save_country', 'ipfo_country_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="name"><?php esc_html_e( 'Country Name', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="text" id="name" name="name" class="regular-text" required value="<?php echo esc_attr( $editing->name ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="code"><?php esc_html_e( 'Country Code', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="text" id="code" name="code" maxlength="10" required value="<?php echo esc_attr( $editing->code ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="flag"><?php esc_html_e( 'Flag (emoji or image URL)', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="text" id="flag" name="flag" class="regular-text" value="<?php echo esc_attr( $editing->flag ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="description"><?php esc_html_e( 'Description', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><textarea id="description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $editing->description ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="status"><?php esc_html_e( 'Status', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td>
						<select id="status" name="status">
							<option value="active" <?php selected( $editing->status ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'ipfo-country-guidance-portal' ); ?></option>
							<option value="inactive" <?php selected( $editing->status ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ipfo-country-guidance-portal' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="last_reviewed"><?php esc_html_e( 'Last Reviewed', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="date" id="last_reviewed" name="last_reviewed" value="<?php echo esc_attr( $editing->last_reviewed ?? '' ); ?>" /></td>
				</tr>
			</table>
			<?php submit_button( $editing ? __( 'Update Country', 'ipfo-country-guidance-portal' ) : __( 'Add Country', 'ipfo-country-guidance-portal' ) ); ?>
		</form>
		<?php
	}

	private function render_table( array $countries ): void {
		?>
		<h2><?php esc_html_e( 'All Countries', 'ipfo-country-guidance-portal' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Code', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Last Reviewed', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ipfo-country-guidance-portal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $countries as $country ) : ?>
					<tr>
						<td><?php echo esc_html( $country->name ); ?></td>
						<td><span class="ipfo-admin-code"><?php echo esc_html( $country->code ); ?></span></td>
						<td><span class="ipfo-admin-pill ipfo-admin-pill--<?php echo esc_attr( $country->status ); ?>"><?php echo esc_html( ucfirst( $country->status ) ); ?></span></td>
						<td><?php echo esc_html( $country->last_reviewed ?: '—' ); ?></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( [ 'edit' => $country->id ] ) ); ?>"><?php esc_html_e( 'Edit', 'ipfo-country-guidance-portal' ); ?></a>
							|
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'ipfo_delete_country' => $country->id, 'edit' => false ] ), 'ipfo_delete_country' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this country? This cannot be undone.', 'ipfo-country-guidance-portal' ) ); ?>');"><?php esc_html_e( 'Delete', 'ipfo-country-guidance-portal' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $countries ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No countries yet.', 'ipfo-country-guidance-portal' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
