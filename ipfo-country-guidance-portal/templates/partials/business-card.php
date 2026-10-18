<?php
/** @var \WP_Post $business */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category = current( wp_get_post_terms( $business->ID, \IPFO\CPT\BusinessCPT::TAXONOMY ) ) ?: null;
$featured = '1' === get_post_meta( $business->ID, '_ipfo_featured', true );
$excerpt  = wp_trim_words( wp_strip_all_tags( $business->post_content ), 20 );
?>
<a class="ipfo-business-card <?php echo $featured ? 'ipfo-business-card--featured' : ''; ?>" href="<?php echo esc_url( get_permalink( $business ) ); ?>">
	<?php if ( $featured ) : ?>
		<span class="ipfo-pill ipfo-pill--featured"><?php esc_html_e( 'Featured', 'ipfo-country-guidance-portal' ); ?></span>
	<?php endif; ?>
	<div class="ipfo-business-card__logo">
		<?php if ( has_post_thumbnail( $business ) ) : ?>
			<?php echo get_the_post_thumbnail( $business, [ 72, 72 ] ); ?>
		<?php else : ?>
			<span class="ipfo-business-card__logo-fallback"><?php echo esc_html( mb_substr( $business->post_title, 0, 1 ) ); ?></span>
		<?php endif; ?>
	</div>
	<h3 class="ipfo-business-card__name"><?php echo esc_html( $business->post_title ); ?></h3>
	<?php if ( $category ) : ?>
		<span class="ipfo-business-card__category"><?php echo esc_html( $category->name ); ?></span>
	<?php endif; ?>
	<?php if ( $excerpt ) : ?>
		<p class="ipfo-business-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
	<?php endif; ?>
	<span class="ipfo-business-card__cta"><?php esc_html_e( 'View Profile →', 'ipfo-country-guidance-portal' ); ?></span>
</a>
