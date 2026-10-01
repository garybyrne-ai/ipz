<?php
/** @var object[] $notifications */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$labels = [
	'guide_updated'    => __( 'Your guide was updated', 'ipfo-country-guidance-portal' ),
	'acknowledgement'  => __( 'You acknowledged a guide', 'ipfo-country-guidance-portal' ),
	'invitation_use'   => __( 'Your invitation was activated', 'ipfo-country-guidance-portal' ),
	'resource_access'  => __( 'You viewed a resource', 'ipfo-country-guidance-portal' ),
];
?>
<h1><?php esc_html_e( 'Notifications', 'ipfo-country-guidance-portal' ); ?></h1>

<div class="ipfo-card">
	<?php if ( empty( $notifications ) ) : ?>
		<div class="ipfo-empty-state"><?php esc_html_e( 'You have no notifications yet.', 'ipfo-country-guidance-portal' ); ?></div>
	<?php else : ?>
		<?php foreach ( $notifications as $n ) : ?>
			<div class="ipfo-notification">
				<span class="ipfo-notification__dot"></span>
				<div>
					<div><?php echo esc_html( $labels[ $n->event_type ] ?? $n->event_type ); ?></div>
					<div class="ipfo-notification__time"><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $n->created_at ) ); ?></div>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
