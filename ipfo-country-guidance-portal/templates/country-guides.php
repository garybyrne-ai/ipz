<?php
/** @var array $countries */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h1><?php esc_html_e( 'Country Guidance', 'ipfo-country-guidance-portal' ); ?></h1>

<?php if ( empty( $countries ) ) : ?>
	<div class="ipfo-empty-state"><?php esc_html_e( 'No country guidance has been assigned to your account yet.', 'ipfo-country-guidance-portal' ); ?></div>
<?php endif; ?>

<?php foreach ( $countries as $entry ) : ?>
	<div class="ipfo-card" style="margin-bottom:20px;">
		<h2><?php echo esc_html( $entry['country']->name ); ?> <span class="ipfo-badge"><?php echo esc_html( $entry['country']->code ); ?></span></h2>
		<?php if ( $entry['country']->description ) : ?>
			<p><?php echo wp_kses_post( $entry['country']->description ); ?></p>
		<?php endif; ?>

		<?php if ( empty( $entry['guides'] ) ) : ?>
			<p class="ipfo-empty-state"><?php esc_html_e( 'Guidance for this country is being prepared.', 'ipfo-country-guidance-portal' ); ?></p>
		<?php else : ?>
			<div class="ipfo-grid ipfo-grid--2">
				<?php foreach ( $entry['guides'] as $g ) : ?>
					<div class="ipfo-card">
						<h3><?php echo esc_html( get_the_title( $g['post'] ) ); ?></h3>
						<p class="ipfo-badge"><?php printf( esc_html__( 'Version %s', 'ipfo-country-guidance-portal' ), esc_html( $g['meta']['version'] ) ); ?></p>
						<p><a class="ipfo-btn ipfo-btn--primary ipfo-btn--sm" href="<?php echo esc_url( $g['url'] ); ?>"><?php esc_html_e( 'Open Guide', 'ipfo-country-guidance-portal' ); ?></a></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $entry['faqs'] ) ) : ?>
			<h3 style="margin-top:20px;"><?php esc_html_e( 'Frequently Asked Questions', 'ipfo-country-guidance-portal' ); ?></h3>
			<div>
				<?php foreach ( $entry['faqs'] as $faq ) : ?>
					<div class="ipfo-faq-item">
						<button type="button" class="ipfo-faq-item__q"><span><?php echo esc_html( get_the_title( $faq ) ); ?></span><span class="ipfo-faq-icon">+</span></button>
						<div class="ipfo-faq-item__a"><?php echo apply_filters( 'the_content', $faq->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput -- trusted admin-authored content. ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
