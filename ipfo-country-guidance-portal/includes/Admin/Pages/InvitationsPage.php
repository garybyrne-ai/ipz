<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\CountryRepository;
use IPFO\Services\InvitationService;
use IPFO\Services\NotificationService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InvitationsPage {

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$service = new InvitationService();
		$notice  = '';
		$created_link = '';

		if ( isset( $_POST['ipfo_invitation_action'] ) && 'create' === $_POST['ipfo_invitation_action'] ) {
			check_admin_referer( 'ipfo_create_invitation', 'ipfo_invitation_nonce' );

			[ , $code, $token ] = $service->create(
				[
					'country_id'         => absint( $_POST['country_id'] ?? 0 ) ?: null,
					'guide_id'           => absint( $_POST['guide_id'] ?? 0 ) ?: null,
					'assigned_resources' => array_map( 'absint', (array) ( $_POST['resource_ids'] ?? [] ) ),
					'max_uses'           => max( 1, absint( $_POST['max_uses'] ?? 1 ) ),
					'single_use'         => ! empty( $_POST['single_use'] ),
					'expires_at'         => ! empty( $_POST['expires_at'] ) ? sanitize_text_field( wp_unslash( $_POST['expires_at'] ) ) . ' 23:59:59' : null,
				]
			);

			$created_link = $service->build_link( $code, $token );

			$send_email = sanitize_email( wp_unslash( $_POST['send_email'] ?? '' ) );
			if ( $send_email && is_email( $send_email ) ) {
				( new NotificationService() )->invitation( $send_email, $created_link, $code );
				$notice = sprintf( __( 'Invitation %s created and emailed.', 'ipfo-country-guidance-portal' ), $code );
			} else {
				$notice = sprintf( __( 'Invitation %s created.', 'ipfo-country-guidance-portal' ), $code );
			}
		}

		if ( isset( $_GET['ipfo_revoke_invitation'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ipfo_revoke_invitation' ) ) {
			$service->revoke( absint( $_GET['ipfo_revoke_invitation'] ) );
			$notice = __( 'Invitation revoked.', 'ipfo-country-guidance-portal' );
		}

		$countries  = ( new CountryRepository() )->all( 'active' );
		$guides     = get_posts( [ 'post_type' => CountryGuideCPT::SLUG, 'posts_per_page' => -1, 'post_status' => 'publish' ] );
		$resources  = get_posts( [ 'post_type' => ResourceCPT::SLUG, 'posts_per_page' => -1, 'post_status' => 'publish' ] );
		$invitations = $service->all();

		echo '<div class="wrap"><h1>' . esc_html__( 'Invitations', 'ipfo-country-guidance-portal' ) . '</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}
		if ( $created_link ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'Secure link:', 'ipfo-country-guidance-portal' ) . ' <code>' . esc_html( $created_link ) . '</code></p></div>';
		}

		$this->render_form( $countries, $guides, $resources );
		$this->render_table( $invitations, $service );

		echo '</div>';
	}

	private function render_form( array $countries, array $guides, array $resources ): void {
		?>
		<h2><?php esc_html_e( 'Create Invitation', 'ipfo-country-guidance-portal' ); ?></h2>
		<form method="post" style="max-width:640px;">
			<input type="hidden" name="ipfo_invitation_action" value="create" />
			<?php wp_nonce_field( 'ipfo_create_invitation', 'ipfo_invitation_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="country_id"><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td>
						<select id="country_id" name="country_id">
							<option value=""><?php esc_html_e( '— None —', 'ipfo-country-guidance-portal' ); ?></option>
							<?php foreach ( $countries as $c ) : ?>
								<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="guide_id"><?php esc_html_e( 'Specific Guide (optional)', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td>
						<select id="guide_id" name="guide_id">
							<option value=""><?php esc_html_e( '— None —', 'ipfo-country-guidance-portal' ); ?></option>
							<?php foreach ( $guides as $g ) : ?>
								<option value="<?php echo esc_attr( $g->ID ); ?>"><?php echo esc_html( get_the_title( $g ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="resource_ids"><?php esc_html_e( 'Assigned Resources', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td>
						<select id="resource_ids" name="resource_ids[]" multiple size="5" style="min-width:300px;">
							<?php foreach ( $resources as $r ) : ?>
								<option value="<?php echo esc_attr( $r->ID ); ?>"><?php echo esc_html( get_the_title( $r ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="max_uses"><?php esc_html_e( 'Maximum Uses', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="number" id="max_uses" name="max_uses" min="1" value="1" /></td>
				</tr>
				<tr>
					<th><label for="single_use"><?php esc_html_e( 'Single Use', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="checkbox" id="single_use" name="single_use" value="1" checked /></td>
				</tr>
				<tr>
					<th><label for="expires_at"><?php esc_html_e( 'Expiry Date', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="date" id="expires_at" name="expires_at" /></td>
				</tr>
				<tr>
					<th><label for="send_email"><?php esc_html_e( 'Email Invitation To (optional)', 'ipfo-country-guidance-portal' ); ?></label></th>
					<td><input type="email" id="send_email" name="send_email" class="regular-text" /></td>
				</tr>
			</table>
			<?php submit_button( __( 'Generate Invitation', 'ipfo-country-guidance-portal' ) ); ?>
		</form>
		<?php
	}

	private function render_table( array $invitations, InvitationService $service ): void {
		?>
		<h2><?php esc_html_e( 'All Invitations', 'ipfo-country-guidance-portal' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Code', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Uses', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Expires', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Created', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ipfo-country-guidance-portal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $invitations as $inv ) : ?>
					<tr>
						<td><span class="ipfo-admin-code"><?php echo esc_html( $inv->code ); ?></span></td>
						<td><span class="ipfo-admin-pill ipfo-admin-pill--<?php echo esc_attr( $inv->status ); ?>"><?php echo esc_html( ucfirst( $inv->status ) ); ?></span></td>
						<td><?php echo (int) $inv->use_count; ?> / <?php echo (int) $inv->max_uses; ?></td>
						<td><?php echo esc_html( $inv->expires_at ?: '—' ); ?></td>
						<td><?php echo esc_html( $inv->created_at ); ?></td>
						<td>
							<?php if ( 'active' === $inv->status ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'ipfo_revoke_invitation' => $inv->id ] ), 'ipfo_revoke_invitation' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Revoke this invitation?', 'ipfo-country-guidance-portal' ) ); ?>');"><?php esc_html_e( 'Revoke', 'ipfo-country-guidance-portal' ); ?></a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $invitations ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No invitations yet.', 'ipfo-country-guidance-portal' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
