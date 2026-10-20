<?php
/** @var \WP_Post $business */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category    = current( wp_get_post_terms( $business->ID, \IPFO\CPT\BusinessCPT::TAXONOMY ) ) ?: null;
$country_ids = (array) json_decode( (string) get_post_meta( $business->ID, '_ipfo_country_ids', true ), true );
$countries   = array_filter( array_map( static fn( $id ) => ( new \IPFO\Repositories\CountryRepository() )->get( (int) $id ), $country_ids ) );
$phone       = get_post_meta( $business->ID, '_ipfo_phone', true );
$email       = get_post_meta( $business->ID, '_ipfo_email', true );
$website     = get_post_meta( $business->ID, '_ipfo_website', true );
$address     = get_post_meta( $business->ID, '_ipfo_address', true );
$facebook    = get_post_meta( $business->ID, '_ipfo_social_facebook', true );
$instagram   = get_post_meta( $business->ID, '_ipfo_social_instagram', true );
$linkedin    = get_post_meta( $business->ID, '_ipfo_social_linkedin', true );
$credentials = get_post_meta( $business->ID, '_ipfo_credentials', true );
$directory_url = ipfo_get_portal_page_url( 'directory' );
$nav           = \IPFO\Frontend\PortalNav::render( 'directory' );
?>
<div class="ipfo-portal ipfo-portal--public ipfo-business-profile<?php echo $nav ? ' ipfo-has-nav' : ''; ?>" data-ipfo-theme="light">
<?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<div class="ipfo-portal__main">
	<p><a href="<?php echo esc_url( $directory_url ); ?>">&larr; <?php esc_html_e( 'Back to the directory', 'ipfo-country-guidance-portal' ); ?></a></p>

	<div class="ipfo-business-profile__header">
		<?php if ( has_post_thumbnail( $business ) ) : ?>
			<div class="ipfo-business-profile__logo"><?php echo get_the_post_thumbnail( $business, [ 120, 120 ] ); ?></div>
		<?php endif; ?>
		<div>
			<h1><?php echo esc_html( get_the_title( $business ) ); ?></h1>
			<?php if ( $category ) : ?>
				<span class="ipfo-business-card__category"><?php echo esc_html( $category->name ); ?></span>
			<?php endif; ?>
			<?php if ( $countries ) : ?>
				<p><?php esc_html_e( 'Serves:', 'ipfo-country-guidance-portal' ); ?> <?php echo esc_html( implode( ', ', wp_list_pluck( $countries, 'name' ) ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="ipfo-grid ipfo-grid--2" style="margin-top:24px;">
		<div class="ipfo-card">
			<h3><?php esc_html_e( 'About', 'ipfo-country-guidance-portal' ); ?></h3>
			<div class="ipfo-business-profile__description"><?php echo wp_kses_post( wpautop( $business->post_content ) ); ?></div>

			<?php if ( $credentials ) : ?>
				<h3><?php esc_html_e( 'Credentials & Certifications', 'ipfo-country-guidance-portal' ); ?></h3>
				<p><?php echo nl2br( esc_html( $credentials ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="ipfo-card">
			<h3><?php esc_html_e( 'Contact', 'ipfo-country-guidance-portal' ); ?></h3>
			<ul class="ipfo-business-profile__contact">
				<?php if ( $phone ) : ?><li><?php esc_html_e( 'Phone:', 'ipfo-country-guidance-portal' ); ?> <a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
				<?php if ( $email ) : ?><li><?php esc_html_e( 'Email:', 'ipfo-country-guidance-portal' ); ?> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
				<?php if ( $website ) : ?><li><?php esc_html_e( 'Website:', 'ipfo-country-guidance-portal' ); ?> <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website ); ?></a></li><?php endif; ?>
				<?php if ( $address ) : ?><li><?php esc_html_e( 'Address:', 'ipfo-country-guidance-portal' ); ?> <?php echo nl2br( esc_html( $address ) ); ?></li><?php endif; ?>
			</ul>

			<?php if ( $facebook || $instagram || $linkedin ) : ?>
				<div class="ipfo-business-profile__social">
					<?php if ( $facebook ) : ?><a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Facebook', 'ipfo-country-guidance-portal' ); ?></a><?php endif; ?>
					<?php if ( $instagram ) : ?><a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Instagram', 'ipfo-country-guidance-portal' ); ?></a><?php endif; ?>
					<?php if ( $linkedin ) : ?><a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LinkedIn', 'ipfo-country-guidance-portal' ); ?></a><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<p class="ipfo-notice ipfo-notice--disclaimer" style="margin-top:24px;"><?php esc_html_e( 'This listing is provided for your convenience. Inclusion in this directory is not an endorsement or guarantee of services; please conduct your own due diligence before engaging any provider.', 'ipfo-country-guidance-portal' ); ?></p>
</div>
</div>
