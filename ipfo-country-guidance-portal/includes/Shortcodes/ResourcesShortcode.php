<?php
declare( strict_types = 1 );

namespace IPFO\Shortcodes;

use IPFO\Services\AccessControlService;
use IPFO\Services\ResourceDownloadService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResourcesShortcode extends ShortcodeBase {

	public function render( array $atts = [] ): string {
		$gate = $this->require_access();
		if ( true !== $gate ) {
			return $gate;
		}

		$user_id = get_current_user_id();
		$access  = new AccessControlService();

		$resources = [];
		foreach ( $access->resource_ids_for_user( $user_id ) as $resource_id ) {
			$post = get_post( $resource_id );
			if ( ! $post ) {
				continue;
			}

			$type          = get_post_meta( $resource_id, '_ipfo_resource_type', true ) ?: 'file';
			$view_enabled  = get_post_meta( $resource_id, '_ipfo_view_enabled', true ) !== '0';
			$download_ok   = (bool) get_post_meta( $resource_id, '_ipfo_download_enabled', true );
			$external_url  = get_post_meta( $resource_id, '_ipfo_external_url', true );

			$resources[] = [
				'post'         => $post,
				'category'     => get_post_meta( $resource_id, '_ipfo_category', true ),
				'type'         => $type,
				'view_url'     => 'external_link' === $type ? $external_url : ( $view_enabled ? ResourceDownloadService::build_url( $resource_id, false ) : '' ),
				'download_url' => $download_ok && 'external_link' !== $type ? ResourceDownloadService::build_url( $resource_id, true ) : '',
			];
		}

		return $this->wrap(
			ipfo_render_template( 'resources.php', [ 'resources' => $resources ] ),
			'ipfo-resources'
		);
	}
}
