<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\AccessLogRepository;
use IPFO\Repositories\CountryRepository;
use IPFO\Repositories\UserAccessRepository;
use IPFO\Services\AccessControlService;
use IPFO\Services\AcknowledgementService;
use IPFO\Services\RegistrationService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UsersAccessPage {

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$notice = $this->handle_actions();

		echo '<div class="wrap"><h1>' . esc_html__( 'Users & Access', 'ipfo-country-guidance-portal' ) . '</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}

		$user_id = absint( $_GET['user_id'] ?? 0 );

		if ( $user_id ) {
			$this->render_user_detail( $user_id );
		} else {
			$this->render_user_list();
		}

		echo '</div>';
	}

	private function handle_actions(): string {
		if ( ! isset( $_POST['ipfo_user_action'] ) ) {
			return '';
		}

		check_admin_referer( 'ipfo_user_access', 'ipfo_user_access_nonce' );

		$user_id = absint( $_POST['user_id'] ?? 0 );
		$access  = new AccessControlService();

		switch ( $_POST['ipfo_user_action'] ) {
			case 'grant_country':
				$access->grant_country( $user_id, absint( $_POST['country_id'] ), get_current_user_id() );
				update_user_meta( $user_id, '_ipfo_country_id', absint( $_POST['country_id'] ) );
				return __( 'Country access granted.', 'ipfo-country-guidance-portal' );

			case 'grant_guide':
				$access->grant_guide( $user_id, absint( $_POST['guide_id'] ), get_current_user_id() );
				return __( 'Guide access granted.', 'ipfo-country-guidance-portal' );

			case 'grant_resource':
				$access->grant_resource( $user_id, absint( $_POST['resource_id'] ), get_current_user_id() );
				return __( 'Resource access granted.', 'ipfo-country-guidance-portal' );

			case 'revoke':
				$access->revoke( $user_id, sanitize_key( $_POST['object_type'] ), absint( $_POST['object_id'] ) );
				return __( 'Access revoked.', 'ipfo-country-guidance-portal' );

			case 'approve':
				( new RegistrationService() )->approve( $user_id );
				return __( 'Account approved.', 'ipfo-country-guidance-portal' );

			case 'suspend':
				( new RegistrationService() )->suspend( $user_id );
				return __( 'Account suspended and all access revoked.', 'ipfo-country-guidance-portal' );
		}

		return '';
	}

	private function render_user_list(): void {
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );

		$args = [ 'role' => Capabilities::CLIENT_ROLE, 'orderby' => 'registered', 'order' => 'DESC' ];
		if ( $search ) {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$users   = get_users( $args );
		$access  = new AccessControlService();
		$country_repo = new CountryRepository();
		?>
		<form method="get" style="margin:16px 0;">
			<input type="hidden" name="page" value="ipfo-users-access" />
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by name or email…', 'ipfo-country-guidance-portal' ); ?>" />
			<?php submit_button( __( 'Search', 'ipfo-country-guidance-portal' ), 'secondary', '', false ); ?>
		</form>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Email', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Country', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Approval', 'ipfo-country-guidance-portal' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'ipfo-country-guidance-portal' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $users as $user ) :
					$country_id = $access->user_country_id( $user->ID );
					$country    = $country_id ? $country_repo->get( $country_id ) : null;
					$status     = get_user_meta( $user->ID, '_ipfo_approval_status', true ) ?: 'approved';
					?>
					<tr>
						<td><?php echo esc_html( $user->display_name ); ?></td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><?php echo esc_html( $country->name ?? '—' ); ?></td>
						<td><span class="ipfo-admin-pill ipfo-admin-pill--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $user->user_registered ) ); ?></td>
						<td><a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'user_id' => $user->ID ] ) ); ?>"><?php esc_html_e( 'Manage', 'ipfo-country-guidance-portal' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $users ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No Intended Parent accounts found.', 'ipfo-country-guidance-portal' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_user_detail( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			echo '<p>' . esc_html__( 'User not found.', 'ipfo-country-guidance-portal' ) . '</p>';
			return;
		}

		$access       = new AccessControlService();
		$country_repo = new CountryRepository();
		$grants       = ( new UserAccessRepository() )->for_user( $user_id );
		$acks         = ( new AcknowledgementService() )->history_for_user( $user_id );
		$logs         = ( new AccessLogRepository() )->recent( 20, [ 'user_id' => $user_id ] );
		$status       = get_user_meta( $user_id, '_ipfo_approval_status', true ) ?: 'approved';

		echo '<p><a href="' . esc_url( remove_query_arg( 'user_id' ) ) . '">&larr; ' . esc_html__( 'Back to all users', 'ipfo-country-guidance-portal' ) . '</a></p>';
		echo '<h2>' . esc_html( $user->display_name ) . ' <span class="ipfo-admin-pill ipfo-admin-pill--' . esc_attr( $status ) . '">' . esc_html( ucfirst( $status ) ) . '</span></h2>';
		echo '<p>' . esc_html( $user->user_email ) . ' · ' . esc_html( get_user_meta( $user_id, '_ipfo_phone', true ) ) . '</p>';

		?>
		<form method="post" style="display:inline-block;margin-right:8px;">
			<?php wp_nonce_field( 'ipfo_user_access', 'ipfo_user_access_nonce' ); ?>
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
			<input type="hidden" name="ipfo_user_action" value="approve" />
			<?php submit_button( __( 'Approve Account', 'ipfo-country-guidance-portal' ), 'primary', '', false ); ?>
		</form>
		<form method="post" style="display:inline-block;">
			<?php wp_nonce_field( 'ipfo_user_access', 'ipfo_user_access_nonce' ); ?>
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
			<input type="hidden" name="ipfo_user_action" value="suspend" />
			<?php submit_button( __( 'Suspend & Revoke All Access', 'ipfo-country-guidance-portal' ), 'delete', '', false, [ 'onclick' => "return confirm('" . esc_js( __( 'Suspend this account and revoke all access?', 'ipfo-country-guidance-portal' ) ) . "');" ] ); ?>
		</form>

		<h3 style="margin-top:28px;"><?php esc_html_e( 'Grant Access', 'ipfo-country-guidance-portal' ); ?></h3>
		<div style="display:flex;gap:20px;flex-wrap:wrap;">
			<form method="post">
				<?php wp_nonce_field( 'ipfo_user_access', 'ipfo_user_access_nonce' ); ?>
				<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
				<input type="hidden" name="ipfo_user_action" value="grant_country" />
				<select name="country_id" required>
					<option value=""><?php esc_html_e( 'Select country…', 'ipfo-country-guidance-portal' ); ?></option>
					<?php foreach ( $country_repo->all( 'active' ) as $c ) : ?>
						<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Grant Country', 'ipfo-country-guidance-portal' ), 'secondary', '', false ); ?>
			</form>

			<form method="post">
				<?php wp_nonce_field( 'ipfo_user_access', 'ipfo_user_access_nonce' ); ?>
				<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
				<input type="hidden" name="ipfo_user_action" value="grant_guide" />
				<select name="guide_id" required>
					<option value=""><?php esc_html_e( 'Select guide…', 'ipfo-country-guidance-portal' ); ?></option>
					<?php foreach ( get_posts( [ 'post_type' => CountryGuideCPT::SLUG, 'posts_per_page' => -1 ] ) as $g ) : ?>
						<option value="<?php echo esc_attr( $g->ID ); ?>"><?php echo esc_html( get_the_title( $g ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Grant Guide', 'ipfo-country-guidance-portal' ), 'secondary', '', false ); ?>
			</form>

			<form method="post">
				<?php wp_nonce_field( 'ipfo_user_access', 'ipfo_user_access_nonce' ); ?>
				<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
				<input type="hidden" name="ipfo_user_action" value="grant_resource" />
				<select name="resource_id" required>
					<option value=""><?php esc_html_e( 'Select resource…', 'ipfo-country-guidance-portal' ); ?></option>
					<?php foreach ( get_posts( [ 'post_type' => ResourceCPT::SLUG, 'posts_per_page' => -1 ] ) as $r ) : ?>
						<option value="<?php echo esc_attr( $r->ID ); ?>"><?php echo esc_html( get_the_title( $r ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Grant Resource', 'ipfo-country-guidance-portal' ), 'secondary', '', false ); ?>
			</form>
		</div>

		<h3 style="margin-top:28px;"><?php esc_html_e( 'Current Access', 'ipfo-country-guidance-portal' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Type', 'ipfo-country-guidance-portal' ); ?></th><th><?php esc_html_e( 'Item', 'ipfo-country-guidance-portal' ); ?></th><th><?php esc_html_e( 'Status', 'ipfo-country-guidance-portal' ); ?></th><th></th></tr></thead>
			<tbody>
				<?php foreach ( $grants as $grant ) :
					$label = 'country' === $grant->object_type ? ( $country_repo->get( (int) $grant->object_id )->name ?? '#' . $grant->object_id ) : get_the_title( (int) $grant->object_id );
					?>
					<tr>
						<td><?php echo esc_html( ucfirst( $grant->object_type ) ); ?></td>
						<td><?php echo esc_html( $label ); ?></td>
						<td><span class="ipfo-admin-pill ipfo-admin-pill--<?php echo esc_attr( $grant->status ); ?>"><?php echo esc_html( ucfirst( $grant->status ) ); ?></span></td>
						<td>
							<?php if ( 'active' === $grant->status ) : ?>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'ipfo_user_access', 'ipfo_user_access_nonce' ); ?>
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
									<input type="hidden" name="ipfo_user_action" value="revoke" />
									<input type="hidden" name="object_type" value="<?php echo esc_attr( $grant->object_type ); ?>" />
									<input type="hidden" name="object_id" value="<?php echo esc_attr( $grant->object_id ); ?>" />
									<?php submit_button( __( 'Revoke', 'ipfo-country-guidance-portal' ), 'link-delete', '', false ); ?>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $grants ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No access granted yet.', 'ipfo-country-guidance-portal' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>

		<h3 style="margin-top:28px;"><?php esc_html_e( 'Acknowledgement History', 'ipfo-country-guidance-portal' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Guide', 'ipfo-country-guidance-portal' ); ?></th><th><?php esc_html_e( 'Version', 'ipfo-country-guidance-portal' ); ?></th><th><?php esc_html_e( 'Acknowledged At', 'ipfo-country-guidance-portal' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $acks as $ack ) : ?>
					<tr><td><?php echo esc_html( get_the_title( (int) $ack->guide_id ) ); ?></td><td><?php echo esc_html( $ack->version ); ?></td><td><?php echo esc_html( $ack->acknowledged_at ); ?></td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $acks ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No acknowledgements recorded yet.', 'ipfo-country-guidance-portal' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>

		<h3 style="margin-top:28px;"><?php esc_html_e( 'Recent Activity', 'ipfo-country-guidance-portal' ); ?></h3>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Event', 'ipfo-country-guidance-portal' ); ?></th><th><?php esc_html_e( 'When', 'ipfo-country-guidance-portal' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<tr><td><?php echo esc_html( $log->event_type ); ?></td><td><?php echo esc_html( $log->created_at ); ?></td></tr>
				<?php endforeach; ?>
				<?php if ( empty( $logs ) ) : ?>
					<tr><td colspan="2"><?php esc_html_e( 'No activity recorded yet.', 'ipfo-country-guidance-portal' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
