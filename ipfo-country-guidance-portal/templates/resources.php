<?php
/** @var array $resources */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$icons = [
	'file'          => '📄',
	'video'         => '🎬',
	'external_link' => '🔗',
];
?>
<h1><?php esc_html_e( 'My Resources', 'ipfo-country-guidance-portal' ); ?></h1>

<?php if ( empty( $resources ) ) : ?>
	<div class="ipfo-empty-state"><?php esc_html_e( 'No resources have been shared with your account yet.', 'ipfo-country-guidance-portal' ); ?></div>
<?php else : ?>
	<div class="ipfo-grid ipfo-grid--3">
		<?php foreach ( $resources as $r ) : ?>
			<div class="ipfo-card ipfo-resource-card">
				<div class="ipfo-resource-card__icon"><?php echo esc_html( $icons[ $r['type'] ] ?? '📄' ); ?></div>
				<h3><?php echo esc_html( get_the_title( $r['post'] ) ); ?></h3>
				<?php if ( $r['category'] ) : ?>
					<span class="ipfo-badge"><?php echo esc_html( $r['category'] ); ?></span>
				<?php endif; ?>
				<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $r['post']->post_content ), 18 ) ); ?></p>

				<div style="display:flex;gap:8px;flex-wrap:wrap;">
					<?php if ( $r['view_url'] ) : ?>
						<a class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm" target="_blank" rel="noopener" href="<?php echo esc_url( $r['view_url'] ); ?>"><?php esc_html_e( 'View', 'ipfo-country-guidance-portal' ); ?></a>
					<?php endif; ?>
					<?php if ( $r['download_url'] ) : ?>
						<a class="ipfo-btn ipfo-btn--primary ipfo-btn--sm" href="<?php echo esc_url( $r['download_url'] ); ?>"><?php esc_html_e( 'Download', 'ipfo-country-guidance-portal' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
