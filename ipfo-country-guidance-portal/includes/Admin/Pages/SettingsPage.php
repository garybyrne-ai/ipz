<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsPage {

	private const PAGE_KEYS = [
		'login'          => 'Sign In',
		'register'       => 'Register',
		'dashboard'      => 'Dashboard',
		'my-guides'      => 'My Guides',
		'guide'          => 'Guide Reader',
		'country-guides' => 'Country Guides',
		'resources'      => 'Resources',
		'checklist'      => 'Checklist',
		'profile'        => 'Profile',
		'notifications'  => 'Notifications',
	];

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$notice = '';

		if ( isset( $_POST['ipfo_settings_save'] ) ) {
			check_admin_referer( 'ipfo_settings', 'ipfo_settings_nonce' );

			update_option( 'ipfo_disclaimer_text', wp_kses_post( wp_unslash( $_POST['disclaimer_text'] ?? '' ) ) );
			update_option( 'ipfo_require_email_verification', ! empty( $_POST['require_email_verification'] ) ? '1' : '0' );
			update_option( 'ipfo_require_admin_approval', ! empty( $_POST['require_admin_approval'] ) ? '1' : '0' );
			update_option( 'ipfo_remove_data_on_uninstall', ! empty( $_POST['remove_data_on_uninstall'] ) ? '1' : '0' );
			update_option( 'ipfo_default_reading_theme', 'dark' === ( $_POST['default_reading_theme'] ?? 'light' ) ? 'dark' : 'light' );

			$pages = [];
			foreach ( array_keys( self::PAGE_KEYS ) as $key ) {
				$pages[ $key ] = absint( $_POST[ 'page_' . $key ] ?? 0 );
			}
			update_option( 'ipfo_portal_pages', $pages );

			$notice = __( 'Settings saved.', 'ipfo-country-guidance-portal' );
		}

		$pages_option = get_option( 'ipfo_portal_pages', [] );
		$all_pages    = get_pages( [ 'sort_column' => 'post_title' ] );

		echo '<div class="wrap"><h1>' . esc_html__( 'IPFO Portal Settings', 'ipfo-country-guidance-portal' ) . '</h1>';
		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}
		?>
		<form method="post">
			<?php wp_nonce_field( 'ipfo_settings', 'ipfo_settings_nonce' ); ?>

			<h2><?php esc_html_e( 'Registration & Security', 'ipfo-country-guidance-portal' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Require Email Verification', 'ipfo-country-guidance-portal' ); ?></th>
					<td><label><input type="checkbox" name="require_email_verification" value="1" <?php checked( get_option( 'ipfo_require_email_verification', '1' ), '1' ); ?> /> <?php esc_html_e( 'New clients must verify their email before signing in.', 'ipfo-country-guidance-portal' ); ?></label></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Require Admin Approval', 'ipfo-country-guidance-portal' ); ?></th>
					<td><label><input type="checkbox" name="require_admin_approval" value="1" <?php checked( get_option( 'ipfo_require_admin_approval', '0' ), '1' ); ?> /> <?php esc_html_e( 'New clients must be manually approved before signing in.', 'ipfo-country-guidance-portal' ); ?></label></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Default Reading Mode', 'ipfo-country-guidance-portal' ); ?></th>
					<td>
						<select name="default_reading_theme">
							<option value="light" <?php selected( get_option( 'ipfo_default_reading_theme', 'light' ), 'light' ); ?>><?php esc_html_e( 'Light', 'ipfo-country-guidance-portal' ); ?></option>
							<option value="dark" <?php selected( get_option( 'ipfo_default_reading_theme', 'light' ), 'dark' ); ?>><?php esc_html_e( 'Dark', 'ipfo-country-guidance-portal' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Remove Data on Uninstall', 'ipfo-country-guidance-portal' ); ?></th>
					<td><label><input type="checkbox" name="remove_data_on_uninstall" value="1" <?php checked( get_option( 'ipfo_remove_data_on_uninstall', '0' ), '1' ); ?> /> <?php esc_html_e( 'Permanently delete all portal data if this plugin is ever uninstalled. Leave unchecked to preserve client data.', 'ipfo-country-guidance-portal' ); ?></label></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Legal Disclaimer', 'ipfo-country-guidance-portal' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Shown throughout the portal. This system never represents its content as legal advice.', 'ipfo-country-guidance-portal' ); ?></p>
			<textarea name="disclaimer_text" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'ipfo_disclaimer_text', '' ) ); ?></textarea>

			<h2><?php esc_html_e( 'Portal Pages', 'ipfo-country-guidance-portal' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Map each portal area to the Elementor page where you have placed its shortcode or widget.', 'ipfo-country-guidance-portal' ); ?></p>
			<table class="form-table">
				<?php foreach ( self::PAGE_KEYS as $key => $label ) : ?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td>
							<select name="page_<?php echo esc_attr( $key ); ?>">
								<option value="0"><?php esc_html_e( '— Not set —', 'ipfo-country-guidance-portal' ); ?></option>
								<?php foreach ( $all_pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( (int) ( $pages_option[ $key ] ?? 0 ), $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button( __( 'Save Settings', 'ipfo-country-guidance-portal' ), 'primary', 'ipfo_settings_save' ); ?>
		</form>
		</div>
		<?php
	}
}
