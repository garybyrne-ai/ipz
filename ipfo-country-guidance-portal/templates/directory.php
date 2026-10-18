<?php
/**
 * @var \WP_Post[] $businesses
 * @var int $total
 * @var int $max_pages
 * @var int $paged
 * @var array{category:int,country:int,q:string} $filters
 * @var \WP_Term[] $categories
 * @var object[] $countries
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ipfo-welcome">
	<h1><?php esc_html_e( 'Recommended Providers', 'ipfo-country-guidance-portal' ); ?></h1>
	<p><?php esc_html_e( 'IVF clinics, fertility lawyers, genetic testing, psychologists, fertility counsellors and supplement providers we recommend to our Intended Parents.', 'ipfo-country-guidance-portal' ); ?></p>
</div>

<form method="get" class="ipfo-directory-filters">
	<?php foreach ( $_GET as $key => $value ) :
		if ( in_array( $key, [ 'ipfo_category', 'ipfo_country', 'ipfo_q', 'ipfo_paged' ], true ) ) {
			continue;
		}
		?>
		<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( is_array( $value ) ? '' : $value ); ?>" />
	<?php endforeach; ?>

	<input type="search" name="ipfo_q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'Search by name…', 'ipfo-country-guidance-portal' ); ?>" />

	<select name="ipfo_category">
		<option value=""><?php esc_html_e( 'All categories', 'ipfo-country-guidance-portal' ); ?></option>
		<?php foreach ( $categories as $term ) : ?>
			<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $filters['category'], $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
		<?php endforeach; ?>
	</select>

	<select name="ipfo_country">
		<option value=""><?php esc_html_e( 'All countries', 'ipfo-country-guidance-portal' ); ?></option>
		<?php foreach ( $countries as $country ) : ?>
			<option value="<?php echo esc_attr( $country->id ); ?>" <?php selected( $filters['country'], $country->id ); ?>><?php echo esc_html( $country->name ); ?></option>
		<?php endforeach; ?>
	</select>

	<button type="submit" class="ipfo-btn ipfo-btn--primary"><?php esc_html_e( 'Filter', 'ipfo-country-guidance-portal' ); ?></button>
	<?php if ( $filters['category'] || $filters['country'] || $filters['q'] ) : ?>
		<a class="ipfo-btn ipfo-btn--ghost" href="<?php echo esc_url( remove_query_arg( [ 'ipfo_category', 'ipfo_country', 'ipfo_q', 'ipfo_paged' ] ) ); ?>"><?php esc_html_e( 'Clear', 'ipfo-country-guidance-portal' ); ?></a>
	<?php endif; ?>
</form>

<?php if ( empty( $businesses ) ) : ?>
	<div class="ipfo-empty-state"><?php esc_html_e( 'No listings match your search yet.', 'ipfo-country-guidance-portal' ); ?></div>
<?php else : ?>
	<div class="ipfo-directory-grid">
		<?php foreach ( $businesses as $business ) : ?>
			<?php echo ipfo_render_template( 'partials/business-card.php', [ 'business' => $business ] ); ?>
		<?php endforeach; ?>
	</div>

	<?php if ( $max_pages > 1 ) : ?>
		<nav class="ipfo-pagination">
			<?php for ( $i = 1; $i <= $max_pages; $i++ ) : ?>
				<a class="ipfo-pagination__link <?php echo $i === $paged ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'ipfo_paged', $i ) ); ?>"><?php echo (int) $i; ?></a>
			<?php endfor; ?>
		</nav>
	<?php endif; ?>
<?php endif; ?>
