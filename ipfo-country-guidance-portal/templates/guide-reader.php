<?php
/**
 * @var \WP_Post $guide
 * @var array $meta
 * @var \WP_Post[] $chapters
 * @var \WP_Post|null $chapter
 * @var int[] $bookmarks
 * @var int $progress
 * @var int $reading_time
 * @var bool $requires_ack
 * @var string $download_url
 * @var string $disclaimer
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$base_url = remove_query_arg( [ 'ipfo_chapter', '_wpnonce' ] );
$index    = 0;
if ( $chapter ) {
	foreach ( $chapters as $i => $c ) {
		if ( $c->ID === $chapter->ID ) {
			$index = $i;
			break;
		}
	}
}
$prev = $chapters[ $index - 1 ] ?? null;
$next = $chapters[ $index + 1 ] ?? null;
?>
<div class="ipfo-booklet" data-guide-id="<?php echo esc_attr( $guide->ID ); ?>" data-chapter-id="<?php echo esc_attr( $chapter?->ID ?? 0 ); ?>">
	<aside class="ipfo-booklet__toc ipfo-no-print">
		<h3><?php esc_html_e( 'Contents', 'ipfo-country-guidance-portal' ); ?></h3>
		<ul class="ipfo-toc-list">
			<?php foreach ( $chapters as $i => $c ) : ?>
				<li>
					<a href="<?php echo esc_url( add_query_arg( [ 'ipfo_chapter' => $c->ID ], $base_url ) ); ?>"
					   class="<?php echo ( $chapter && $chapter->ID === $c->ID ) ? 'is-active' : ''; ?> <?php echo in_array( $c->ID, $bookmarks, true ) ? 'ipfo-bookmarked' : ''; ?>">
						<span><?php echo esc_html( ( $i + 1 ) . '. ' . get_the_title( $c ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</aside>

	<div class="ipfo-booklet__main">
		<div class="ipfo-booklet__toolbar ipfo-no-print">
			<div class="ipfo-booklet__search">
				<input type="search" placeholder="<?php esc_attr_e( 'Search this guide…', 'ipfo-country-guidance-portal' ); ?>" data-ipfo-booklet-search />
				<div data-ipfo-booklet-results></div>
			</div>
			<button type="button" class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm" data-ipfo-theme-toggle><?php esc_html_e( 'Light / Dark', 'ipfo-country-guidance-portal' ); ?></button>
			<?php if ( $meta['download_enabled'] && $download_url ) : ?>
				<a class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm" href="<?php echo esc_url( $download_url ); ?>"><?php esc_html_e( 'Download PDF', 'ipfo-country-guidance-portal' ); ?></a>
			<?php endif; ?>
			<button type="button" class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm" data-ipfo-print><?php esc_html_e( 'Print', 'ipfo-country-guidance-portal' ); ?></button>
		</div>

		<div class="ipfo-progress-bar ipfo-no-print" style="margin-bottom:24px;">
			<div class="ipfo-progress-bar__fill" style="width:<?php echo (int) $progress; ?>%;"></div>
		</div>

		<div class="ipfo-booklet__content">
			<?php if ( ! $chapter ) : ?>
				<div class="ipfo-booklet__cover">
					<?php if ( has_post_thumbnail( $guide ) ) : ?>
						<img src="<?php echo esc_url( get_the_post_thumbnail_url( $guide, 'large' ) ); ?>" alt="" style="max-width:280px;border-radius:var(--ipfo-radius);margin-bottom:24px;" />
					<?php endif; ?>
					<h1><?php echo esc_html( get_the_title( $guide ) ); ?></h1>
					<p class="ipfo-badge"><?php printf( esc_html__( 'Version %s', 'ipfo-country-guidance-portal' ), esc_html( $meta['version'] ) ); ?></p>
					<div class="ipfo-chapter-body" style="text-align:left;margin-top:24px;">
						<?php echo apply_filters( 'the_content', $guide->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput -- trusted admin-authored content, same as core the_content(). ?>
					</div>
					<p style="margin-top:24px;color:var(--ipfo-text);font-size:13px;">
						<?php printf( esc_html__( 'Estimated reading time: %d minutes', 'ipfo-country-guidance-portal' ), (int) $reading_time ); ?>
					</p>
					<?php if ( ! empty( $chapters ) ) : ?>
						<p><a class="ipfo-btn ipfo-btn--primary" href="<?php echo esc_url( add_query_arg( [ 'ipfo_chapter' => $chapters[0]->ID ], $base_url ) ); ?>"><?php esc_html_e( 'Start Reading', 'ipfo-country-guidance-portal' ); ?></a></p>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="ipfo-notice ipfo-notice--disclaimer ipfo-no-print"><?php echo esc_html( $disclaimer ); ?></div>

				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
					<h1><?php echo esc_html( get_the_title( $chapter ) ); ?></h1>
					<button type="button" class="ipfo-btn ipfo-btn--ghost ipfo-btn--sm ipfo-no-print" data-ipfo-bookmark="<?php echo esc_attr( $chapter->ID ); ?>">
						<?php esc_html_e( '★ Bookmark', 'ipfo-country-guidance-portal' ); ?>
					</button>
				</div>

				<div class="ipfo-chapter-body">
					<?php echo apply_filters( 'the_content', $chapter->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput -- trusted admin-authored content, same as core the_content(). ?>
				</div>

				<div class="ipfo-booklet__nav ipfo-no-print">
					<?php if ( $prev ) : ?>
						<a class="ipfo-btn ipfo-btn--ghost" href="<?php echo esc_url( add_query_arg( [ 'ipfo_chapter' => $prev->ID ], $base_url ) ); ?>">&larr; <?php echo esc_html( get_the_title( $prev ) ); ?></a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>

					<?php if ( $next ) : ?>
						<a class="ipfo-btn ipfo-btn--primary" href="<?php echo esc_url( add_query_arg( [ 'ipfo_chapter' => $next->ID ], $base_url ) ); ?>"><?php echo esc_html( get_the_title( $next ) ); ?> &rarr;</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $requires_ack ) : ?>
				<div class="ipfo-ack-box ipfo-no-print">
					<p><?php esc_html_e( 'I confirm that I have read and understood this guidance.', 'ipfo-country-guidance-portal' ); ?></p>
					<p class="ipfo-notice--disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
					<button type="button" class="ipfo-btn ipfo-btn--primary" data-ipfo-acknowledge><?php esc_html_e( 'I Acknowledge', 'ipfo-country-guidance-portal' ); ?></button>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
