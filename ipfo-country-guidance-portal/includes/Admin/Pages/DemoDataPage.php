<?php
declare( strict_types = 1 );

namespace IPFO\Admin\Pages;

use IPFO\Capabilities;
use IPFO\DemoData\DemoDataSeeder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DemoDataPage {

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_PORTAL ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ipfo-country-guidance-portal' ) );
		}

		$seeder = new DemoDataSeeder();
		$result = null;
		$notice = '';

		if ( isset( $_POST['ipfo_demo_action'] ) ) {
			check_admin_referer( 'ipfo_demo_data', 'ipfo_demo_data_nonce' );

			if ( 'install' === $_POST['ipfo_demo_action'] ) {
				$result = $seeder->install();
				$notice = $result['already_installed']
					? __( 'Demo data was already installed — nothing new was created.', 'ipfo-country-guidance-portal' )
					: __( 'Demo data installed.', 'ipfo-country-guidance-portal' );
			} elseif ( 'remove' === $_POST['ipfo_demo_action'] ) {
				$seeder->remove();
				$notice = __( 'Demo data removed.', 'ipfo-country-guidance-portal' );
			}
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Demo Data', 'ipfo-country-guidance-portal' ) . '</h1>';

		echo '<p>' . esc_html__( 'Installs a ready-made set of sample content so you can click through every part of the portal and the Business Directory before inviting real clients: a Denmark country guide with five chapters built from the Danish legal guide PDF, a downloadable PDF resource, two FAQs, five checklist items, two directory listings (one with its own business-owner login you can sign in as), a demo client account with access already granted, and one unredeemed invitation of each type so you can test the invite flow yourself.', 'ipfo-country-guidance-portal' ) . '</p>';

		if ( $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}

		if ( $result && ! empty( $result['errors'] ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Some items could not be created:', 'ipfo-country-guidance-portal' ) . '</p><ul>';
			foreach ( $result['errors'] as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul></div>';
		}

		if ( $result && ! $result['already_installed'] ) {
			$this->render_credentials( $result );
		}

		if ( $seeder->is_installed() ) {
			echo '<h2>' . esc_html__( 'Demo data is installed', 'ipfo-country-guidance-portal' ) . '</h2>';
			echo '<p>' . esc_html__( 'Login credentials are only ever shown once, right after installing — if you need them again, reset the password for the demo-client or demo-business-owner account from the Users screen.', 'ipfo-country-guidance-portal' ) . '</p>';
			?>
			<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Remove all demo data? This deletes the demo country, guide, chapters, resource, FAQs, checklist items, both directory listings, the demo client and business-owner accounts, and both demo invitations.', 'ipfo-country-guidance-portal' ) ); ?>');">
				<?php wp_nonce_field( 'ipfo_demo_data', 'ipfo_demo_data_nonce' ); ?>
				<input type="hidden" name="ipfo_demo_action" value="remove" />
				<?php submit_button( __( 'Remove Demo Data', 'ipfo-country-guidance-portal' ), 'delete', '', false ); ?>
			</form>
			<?php
		} else {
			?>
			<form method="post">
				<?php wp_nonce_field( 'ipfo_demo_data', 'ipfo_demo_data_nonce' ); ?>
				<input type="hidden" name="ipfo_demo_action" value="install" />
				<?php submit_button( __( 'Install Demo Data', 'ipfo-country-guidance-portal' ), 'primary', '', false ); ?>
			</form>
			<?php
		}

		echo '</div>';
	}

	private function render_credentials( array $result ): void {
		echo '<h2>' . esc_html__( 'Save these now — shown only once', 'ipfo-country-guidance-portal' ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:720px;"><tbody>';

		if ( ! empty( $result['client_login'] ) ) {
			echo '<tr><th>' . esc_html__( 'Demo client login', 'ipfo-country-guidance-portal' ) . '</th><td>' . esc_html( $result['client_login']['email'] ) . ' / <code>' . esc_html( $result['client_login']['password'] ) . '</code></td></tr>';
		}

		if ( ! empty( $result['business_login'] ) ) {
			echo '<tr><th>' . esc_html__( 'Demo business-owner login', 'ipfo-country-guidance-portal' ) . '</th><td>' . esc_html( $result['business_login']['email'] ) . ' / <code>' . esc_html( $result['business_login']['password'] ) . '</code></td></tr>';
		}

		if ( ! empty( $result['invitation_links']['client'] ) ) {
			echo '<tr><th>' . esc_html__( 'Unredeemed client invitation', 'ipfo-country-guidance-portal' ) . '</th><td><code>' . esc_html( $result['invitation_links']['client'] ) . '</code></td></tr>';
		}

		if ( ! empty( $result['invitation_links']['business'] ) ) {
			echo '<tr><th>' . esc_html__( 'Unredeemed business invitation', 'ipfo-country-guidance-portal' ) . '</th><td><code>' . esc_html( $result['invitation_links']['business'] ) . '</code></td></tr>';
		}

		if ( ! empty( $result['directory_url'] ) ) {
			echo '<tr><th>' . esc_html__( 'Directory page', 'ipfo-country-guidance-portal' ) . '</th><td><a href="' . esc_url( $result['directory_url'] ) . '" target="_blank" rel="noopener">' . esc_html( $result['directory_url'] ) . '</a></td></tr>';
		}

		echo '</tbody></table>';
	}
}
