<?php
declare( strict_types = 1 );

namespace IPFO\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Branded transactional email sender. Uses wp_mail() exclusively so any
 * SMTP plugin already configured on the site (WP Mail SMTP, etc.) is
 * respected automatically; no credentials are ever hard-coded here.
 */
final class Mailer {

	public static function send( string $to, string $subject, string $heading, string $body_html, string $cta_text = '', string $cta_url = '' ): bool {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$html = self::wrap(
			$heading,
			$body_html,
			$cta_text,
			$cta_url
		);

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		/**
		 * Allow sites to adjust the From name/address via standard WP filters
		 * (wp_mail_from, wp_mail_from_name) or a dedicated SMTP plugin.
		 */
		return wp_mail( $to, sprintf( '[%s] %s', $site_name, $subject ), $html, $headers );
	}

	private static function wrap( string $heading, string $body_html, string $cta_text, string $cta_url ): string {
		$site_name = esc_html( get_bloginfo( 'name' ) );
		$site_url  = esc_url( home_url( '/' ) );
		$primary   = '#7F448A';
		$headline  = '#100A05';
		$text      = '#4C4C4C';
		$bg        = '#F5F6FF';

		$cta = '';
		if ( $cta_text && $cta_url ) {
			$cta = sprintf(
				'<p style="margin:32px 0 0;"><a href="%1$s" style="background:%2$s;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-family:Blinker,Arial,sans-serif;font-weight:600;display:inline-block;">%3$s</a></p>',
				esc_url( $cta_url ),
				esc_attr( $primary ),
				esc_html( $cta_text )
			);
		}

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<body style="margin:0;padding:0;background:<?php echo esc_attr( $bg ); ?>;font-family:Manrope,Arial,sans-serif;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
				<tr>
					<td align="center">
						<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(16,10,5,0.08);">
							<tr>
								<td style="padding:28px 36px;border-bottom:1px solid #ebebeb;">
									<a href="<?php echo $site_url; ?>" style="font-family:Blinker,Arial,sans-serif;font-weight:700;font-size:20px;color:<?php echo esc_attr( $headline ); ?>;text-decoration:none;"><?php echo $site_name; ?></a>
								</td>
							</tr>
							<tr>
								<td style="padding:36px;">
									<h1 style="font-family:Blinker,Arial,sans-serif;font-size:24px;color:<?php echo esc_attr( $headline ); ?>;margin:0 0 16px;"><?php echo esc_html( $heading ); ?></h1>
									<div style="font-family:Manrope,Arial,sans-serif;font-size:15px;line-height:1.7;color:<?php echo esc_attr( $text ); ?>;">
										<?php echo $body_html; ?>
									</div>
									<?php echo $cta; ?>
								</td>
							</tr>
							<tr>
								<td style="padding:20px 36px;background:<?php echo esc_attr( $bg ); ?>;font-family:Manrope,Arial,sans-serif;font-size:12px;color:#8a8a8a;">
									<?php echo esc_html__( 'This is a private, secure communication from', 'ipfo-country-guidance-portal' ) . ' ' . $site_name . '.'; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}
}
