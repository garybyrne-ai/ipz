<?php
/**
 * Reusable "which countries does this business serve" picker, shared by the
 * business registration form and the business owner's own dashboard. A
 * plain checkbox list rather than a native <select multiple> — the latter
 * gives no visual confirmation of what's selected unless you already know
 * to Ctrl/Cmd-click, which is exactly what caused confusion when this was
 * first built. The "serves worldwide" checkbox is a convenience: checking
 * it is equivalent to leaving every individual country unselected, which
 * the rest of the plugin already treats as "available everywhere" (see
 * BusinessDirectoryService::search()).
 *
 * @var object[] $countries
 * @var int[] $selected_country_ids
 * @var bool $worldwide_checked
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-field">
	<label><?php esc_html_e( 'Countries You Serve', 'ipfo-country-guidance-portal' ); ?></label>

	<label class="ipfo-field--checkbox ipfo-worldwide-toggle">
		<input type="checkbox" name="ipfo_serves_worldwide" value="1" data-ipfo-worldwide-toggle <?php checked( $worldwide_checked ); ?> />
		<span><?php esc_html_e( 'Serves all countries (worldwide)', 'ipfo-country-guidance-portal' ); ?></span>
	</label>

	<div class="ipfo-country-checklist" data-ipfo-country-checklist>
		<?php if ( empty( $countries ) ) : ?>
			<p class="ipfo-empty-state" style="padding:12px 0;"><?php esc_html_e( 'No countries have been added yet.', 'ipfo-country-guidance-portal' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $countries as $country ) : ?>
			<label class="ipfo-country-checklist__item">
				<input type="checkbox" name="ipfo_country_ids[]" value="<?php echo esc_attr( $country->id ); ?>" <?php checked( in_array( (int) $country->id, $selected_country_ids, true ) ); ?> <?php disabled( $worldwide_checked ); ?> />
				<?php echo esc_html( $country->name ); ?>
			</label>
		<?php endforeach; ?>
	</div>
</div>
