<?php
/**
 * @var array $items
 * @var int $percent
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h1><?php esc_html_e( 'Your Checklist', 'ipfo-country-guidance-portal' ); ?></h1>

<div class="ipfo-card" style="margin-bottom:20px;">
	<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
		<span><?php esc_html_e( 'Overall progress', 'ipfo-country-guidance-portal' ); ?></span>
		<strong><?php echo (int) $percent; ?>%</strong>
	</div>
	<div class="ipfo-progress-bar">
		<div class="ipfo-progress-bar__fill" style="width:<?php echo (int) $percent; ?>%;"></div>
	</div>
</div>

<div class="ipfo-card">
	<?php if ( empty( $items ) ) : ?>
		<div class="ipfo-empty-state"><?php esc_html_e( 'No checklist items are available yet for your country.', 'ipfo-country-guidance-portal' ); ?></div>
	<?php else : ?>
		<ul class="ipfo-checklist-list">
			<?php foreach ( $items as $item ) : ?>
				<li class="ipfo-checklist-item <?php echo $item['complete'] ? 'is-complete' : ''; ?>">
					<input type="checkbox" id="ipfo-check-<?php echo esc_attr( $item['id'] ); ?>" data-ipfo-checklist-item="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( $item['complete'] ); ?> />
					<div>
						<label class="ipfo-checklist-item__label" for="ipfo-check-<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['label'] ); ?></label>
						<?php if ( $item['description'] ) : ?>
							<div class="ipfo-checklist-item__desc"><?php echo esc_html( $item['description'] ); ?></div>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
