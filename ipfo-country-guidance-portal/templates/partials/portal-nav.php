<?php
/**
 * @var array $items
 * @var string $active
 * @var \WP_User $user
 * @var bool $is_business
 * @var string $logout_url
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name    = $user->first_name ?: $user->display_name;
$initial = $name ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : '?';
?>
<button type="button" class="ipfo-nav-toggle" data-ipfo-nav-toggle aria-label="<?php esc_attr_e( 'Open menu', 'ipfo-country-guidance-portal' ); ?>" aria-expanded="false">
	<span class="dashicons dashicons-menu"></span>
	<span class="ipfo-nav-toggle__label"><?php esc_html_e( 'Menu', 'ipfo-country-guidance-portal' ); ?></span>
</button>
<div class="ipfo-nav-backdrop" data-ipfo-nav-backdrop></div>
<nav class="ipfo-sidebar" data-ipfo-sidebar>
	<div class="ipfo-sidebar__brand">
		<span class="ipfo-sidebar__avatar"><?php echo esc_html( $initial ); ?></span>
		<div class="ipfo-sidebar__who">
			<strong><?php echo esc_html( $name ); ?></strong>
			<span class="ipfo-sidebar__role"><?php echo $is_business ? esc_html__( 'Business Partner', 'ipfo-country-guidance-portal' ) : esc_html__( 'Intended Parent', 'ipfo-country-guidance-portal' ); ?></span>
		</div>
	</div>
	<ul class="ipfo-sidebar__nav">
		<?php foreach ( $items as $item ) : ?>
			<li>
				<a href="<?php echo esc_url( $item['url'] ); ?>" class="<?php echo $active === $item['key'] ? 'is-active' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="ipfo-sidebar__footer">
		<a href="<?php echo esc_url( $logout_url ); ?>" class="ipfo-sidebar__logout">
			<span class="dashicons dashicons-migrate"></span>
			<?php esc_html_e( 'Logout', 'ipfo-country-guidance-portal' ); ?>
		</a>
	</div>
</nav>
