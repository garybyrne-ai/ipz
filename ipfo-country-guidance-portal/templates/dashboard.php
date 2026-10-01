<?php
/**
 * @var string $user_display_name
 * @var object|null $country
 * @var \WP_Post|null $primary_guide
 * @var string $guide_version
 * @var string $guide_updated
 * @var int $progress_percent
 * @var int $checklist_remaining
 * @var string $guides_url
 * @var string $guide_url
 * @var string $checklist_url
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-welcome">
	<h1><?php printf( esc_html__( 'Welcome, %s', 'ipfo-country-guidance-portal' ), esc_html( $user_display_name ) ); ?></h1>
	<p><?php esc_html_e( 'Your IP Fertility Options Guidance Centre', 'ipfo-country-guidance-portal' ); ?></p>
</div>

<div class="ipfo-grid ipfo-grid--4">
	<div class="ipfo-card ipfo-stat-card">
		<div class="ipfo-stat-label"><?php esc_html_e( 'Your Country', 'ipfo-country-guidance-portal' ); ?></div>
		<div class="ipfo-stat-value"><?php echo $country ? esc_html( $country->name ) : esc_html__( 'Not yet assigned', 'ipfo-country-guidance-portal' ); ?></div>
	</div>

	<div class="ipfo-card ipfo-stat-card">
		<div class="ipfo-stat-label"><?php esc_html_e( 'Your Guidance', 'ipfo-country-guidance-portal' ); ?></div>
		<div class="ipfo-stat-value" style="font-size:17px;">
			<?php echo $primary_guide ? esc_html( get_the_title( $primary_guide ) ) : esc_html__( 'Not yet assigned', 'ipfo-country-guidance-portal' ); ?>
		</div>
	</div>

	<div class="ipfo-card ipfo-stat-card">
		<div class="ipfo-stat-label"><?php esc_html_e( 'Progress', 'ipfo-country-guidance-portal' ); ?></div>
		<div class="ipfo-stat-value"><?php echo (int) $progress_percent; ?>%</div>
		<div class="ipfo-progress-bar" style="margin-top:10px;">
			<div class="ipfo-progress-bar__fill" style="width:<?php echo (int) $progress_percent; ?>%;"></div>
		</div>
	</div>

	<div class="ipfo-card ipfo-stat-card">
		<div class="ipfo-stat-label"><?php esc_html_e( 'Required Actions', 'ipfo-country-guidance-portal' ); ?></div>
		<div class="ipfo-stat-value"><?php echo (int) $checklist_remaining; ?> <?php esc_html_e( 'remaining', 'ipfo-country-guidance-portal' ); ?></div>
	</div>
</div>

<div class="ipfo-grid ipfo-grid--2" style="margin-top:24px;">
	<div class="ipfo-card">
		<h3><?php esc_html_e( 'Continue Your Guidance', 'ipfo-country-guidance-portal' ); ?></h3>
		<?php if ( $primary_guide ) : ?>
			<p>
				<?php
				printf(
					/* translators: 1: version number, 2: last reviewed date */
					esc_html__( 'Version %1$s · Last reviewed %2$s', 'ipfo-country-guidance-portal' ),
					esc_html( $guide_version ),
					esc_html( $guide_updated ? mysql2date( get_option( 'date_format' ), $guide_updated ) : '—' )
				);
				?>
			</p>
			<a class="ipfo-btn ipfo-btn--primary" href="<?php echo esc_url( $guide_url ); ?>"><?php esc_html_e( 'Continue Reading →', 'ipfo-country-guidance-portal' ); ?></a>
		<?php else : ?>
			<p class="ipfo-empty-state"><?php esc_html_e( 'Your guidance is being prepared. We will notify you as soon as it is ready.', 'ipfo-country-guidance-portal' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="ipfo-card">
		<h3><?php esc_html_e( 'Your Checklist', 'ipfo-country-guidance-portal' ); ?></h3>
		<p><?php printf( esc_html__( '%d item(s) remaining to complete.', 'ipfo-country-guidance-portal' ), (int) $checklist_remaining ); ?></p>
		<a class="ipfo-btn ipfo-btn--ghost" href="<?php echo esc_url( $checklist_url ); ?>"><?php esc_html_e( 'Open Checklist', 'ipfo-country-guidance-portal' ); ?></a>
	</div>
</div>
