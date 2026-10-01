<?php
/** @var array $guides */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h1><?php esc_html_e( 'My Guides', 'ipfo-country-guidance-portal' ); ?></h1>

<?php if ( empty( $guides ) ) : ?>
	<div class="ipfo-empty-state"><?php esc_html_e( 'No guides have been assigned to your account yet.', 'ipfo-country-guidance-portal' ); ?></div>
<?php else : ?>
	<div class="ipfo-grid ipfo-grid--3">
		<?php foreach ( $guides as $g ) : ?>
			<div class="ipfo-card ipfo-guide-card">
				<?php if ( has_post_thumbnail( $g['post'] ) ) : ?>
					<img class="ipfo-guide-card__cover" src="<?php echo esc_url( get_the_post_thumbnail_url( $g['post'], 'medium_large' ) ); ?>" alt="" />
				<?php else : ?>
					<div class="ipfo-guide-card__cover"></div>
				<?php endif; ?>

				<h3><?php echo esc_html( get_the_title( $g['post'] ) ); ?></h3>
				<div class="ipfo-guide-card__meta">
					<?php
					printf(
						/* translators: version number */
						esc_html__( 'Version %s', 'ipfo-country-guidance-portal' ),
						esc_html( $g['meta']['version'] )
					);
					?>
				</div>

				<div class="ipfo-progress-bar">
					<div class="ipfo-progress-bar__fill" style="width:<?php echo (int) $g['percent']; ?>%;"></div>
				</div>

				<p style="margin-top:16px;">
					<a class="ipfo-btn ipfo-btn--primary" href="<?php echo esc_url( $g['url'] ); ?>"><?php esc_html_e( 'Continue Reading →', 'ipfo-country-guidance-portal' ); ?></a>
				</p>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
