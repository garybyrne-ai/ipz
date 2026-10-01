/**
 * IPFO Country Guidance Portal — front-end behaviour.
 * No framework: progressive enhancement over server-rendered templates.
 * All writes go through the authenticated REST API (ipfo/v1), which WP
 * core already rejects without a valid nonce for cookie-authenticated users.
 */
( function () {
	'use strict';

	if ( typeof window.IPFO_PORTAL === 'undefined' ) {
		return;
	}

	var cfg = window.IPFO_PORTAL;

	function restFetch( path, method, body ) {
		return fetch( cfg.restUrl.replace( /\/$/, '' ) + path, {
			method: method || 'GET',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.restNonce
			},
			body: body ? JSON.stringify( body ) : undefined
		} ).then( function ( res ) {
			return res.json().then( function ( data ) {
				return { ok: res.ok, data: data };
			} );
		} );
	}

	function debounce( fn, wait ) {
		var t;
		return function () {
			var args = arguments, ctx = this;
			clearTimeout( t );
			t = setTimeout( function () { fn.apply( ctx, args ); }, wait );
		};
	}

	/* ---------- Theme (light/dark reading mode) ---------- */
	function initTheme() {
		var portal = document.querySelector( '.ipfo-portal' );
		if ( ! portal ) { return; }

		var toggle = document.querySelector( '[data-ipfo-theme-toggle]' );
		if ( ! toggle ) { return; }

		var stored = '';
		try { stored = window.localStorage.getItem( 'ipfo_theme' ) || ''; } catch ( e ) {}
		if ( stored ) { portal.setAttribute( 'data-ipfo-theme', stored ); }

		toggle.addEventListener( 'click', function () {
			var current = portal.getAttribute( 'data-ipfo-theme' ) === 'dark' ? 'light' : 'dark';
			portal.setAttribute( 'data-ipfo-theme', current );
			try { window.localStorage.setItem( 'ipfo_theme', current ); } catch ( e ) {}
		} );
	}

	/* ---------- Reading progress + bookmarks ---------- */
	function initBooklet() {
		var booklet = document.querySelector( '.ipfo-booklet' );
		if ( ! booklet ) { return; }

		var guideId   = parseInt( booklet.getAttribute( 'data-guide-id' ), 10 );
		var chapterId = parseInt( booklet.getAttribute( 'data-chapter-id' ), 10 );
		var content   = booklet.querySelector( '.ipfo-chapter-body' );

		if ( content && guideId && chapterId ) {
			var sendProgress = debounce( function () {
				var scrollable = content.scrollHeight - window.innerHeight;
				var scrolled   = window.scrollY - content.offsetTop + window.innerHeight;
				var percent    = scrollable > 0 ? Math.min( 100, Math.max( 0, Math.round( ( scrolled / content.scrollHeight ) * 100 ) ) ) : 100;

				restFetch( '/progress', 'POST', { guide_id: guideId, chapter_id: chapterId, percent: percent } );
			}, 800 );

			window.addEventListener( 'scroll', sendProgress, { passive: true } );
			sendProgress();
		}

		booklet.querySelectorAll( '[data-ipfo-bookmark]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var cid = parseInt( btn.getAttribute( 'data-ipfo-bookmark' ), 10 );
				restFetch( '/bookmark', 'POST', { guide_id: guideId, chapter_id: cid } ).then( function ( res ) {
					if ( res.ok ) {
						btn.classList.toggle( 'ipfo-bookmarked' );
					}
				} );
			} );
		} );

		var search = booklet.querySelector( '[data-ipfo-booklet-search]' );
		var results = booklet.querySelector( '[data-ipfo-booklet-results]' );
		if ( search && results ) {
			search.addEventListener( 'input', debounce( function () {
				var q = search.value.trim();
				if ( q.length < 2 ) { results.innerHTML = ''; return; }

				restFetch( '/search?q=' + encodeURIComponent( q ) ).then( function ( res ) {
					if ( ! res.ok ) { return; }
					renderSearchResults( results, res.data.results || [] );
				} );
			}, 300 ) );
		}

		var printBtn = booklet.querySelector( '[data-ipfo-print]' );
		if ( printBtn ) {
			printBtn.addEventListener( 'click', function () { window.print(); } );
		}

		var ackBtn = booklet.querySelector( '[data-ipfo-acknowledge]' );
		if ( ackBtn ) {
			ackBtn.addEventListener( 'click', function () {
				ackBtn.disabled = true;
				restFetch( '/acknowledge', 'POST', { guide_id: guideId } ).then( function ( res ) {
					if ( res.ok ) {
						var box = ackBtn.closest( '.ipfo-ack-box' );
						if ( box ) { box.outerHTML = '<p class="ipfo-form-success">' + cfg.i18n.acknowledged + '</p>'; }
					} else {
						ackBtn.disabled = false;
					}
				} );
			} );
		}
	}

	function renderSearchResults( container, results ) {
		if ( ! results.length ) {
			container.innerHTML = '<p class="ipfo-empty-state">' + cfg.i18n.noResults + '</p>';
			return;
		}

		var html = '<ul class="ipfo-toc-list">';
		results.forEach( function ( r ) {
			html += '<li><a href="#"><span>' + escapeHtml( r.title ) + '</span><span class="ipfo-badge">' + escapeHtml( r.type ) + '</span></a></li>';
		} );
		html += '</ul>';
		container.innerHTML = html;
	}

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.innerText = str || '';
		return div.innerHTML;
	}

	/* ---------- Checklist ---------- */
	function initChecklist() {
		document.querySelectorAll( '[data-ipfo-checklist-item]' ).forEach( function ( checkbox ) {
			checkbox.addEventListener( 'change', function () {
				var itemId = parseInt( checkbox.getAttribute( 'data-ipfo-checklist-item' ), 10 );
				var row    = checkbox.closest( '.ipfo-checklist-item' );

				restFetch( '/checklist', 'POST', { item_id: itemId, complete: checkbox.checked } ).then( function ( res ) {
					if ( res.ok && row ) {
						row.classList.toggle( 'is-complete', checkbox.checked );
					} else if ( ! res.ok ) {
						checkbox.checked = ! checkbox.checked;
					}
				} );
			} );
		} );
	}

	/* ---------- FAQ accordion ---------- */
	function initFaq() {
		document.querySelectorAll( '.ipfo-faq-item__q' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				btn.closest( '.ipfo-faq-item' ).classList.toggle( 'is-open' );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initTheme();
		initBooklet();
		initChecklist();
		initFaq();
	} );
} )();
