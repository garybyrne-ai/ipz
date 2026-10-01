/**
 * IPFO admin screen behaviour: drag-to-reorder checklist items.
 * The Guide/Resource media picker is inlined on its own meta box since it
 * only needs a few lines of wp.media glue.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $list = $( '[data-ipfo-sortable]' );
		if ( ! $list.length || typeof $list.sortable !== 'function' ) {
			return;
		}

		$list.sortable( { axis: 'y', handle: '.dashicons-move' } );

		$list.closest( 'form' ).on( 'submit', function () {
			var ids = $list.find( 'li' ).map( function () {
				return $( this ).data( 'id' );
			} ).get();

			$( '[data-ipfo-order-field]' ).val( ids.join( ',' ) );
		} );
	} );
} )( jQuery );
