/**
 * Knowlio Docs frontend bundle.
 *
 * Navigation is plain links, so the knowledge base works with JavaScript
 * disabled. This file only adds two conveniences on top.
 */
( function () {
	'use strict';

	var helper = window.knowlio_helper || {};
	var i18n   = helper.i18n || {};

	/* ------------------------------------------------------ Grid / list view */

	var VIEW_KEY = 'knowlio_article_view';

	function readStoredView() {
		try {
			var stored = window.localStorage.getItem( VIEW_KEY );
			return ( stored === 'list' || stored === 'grid' ) ? stored : null;
		} catch ( error ) {
			// Private mode, or storage disabled: fall back to the default.
			return null;
		}
	}

	function storeView( view ) {
		try {
			window.localStorage.setItem( VIEW_KEY, view );
		} catch ( error ) {
			// Preference simply will not persist; the page still works.
		}
	}

	function applyView( view ) {
		document.querySelectorAll( '[data-knowlio-article-list]' ).forEach( function ( list ) {
			list.classList.toggle( 'knowlio-view-grid', view === 'grid' );
			list.classList.toggle( 'knowlio-view-list', view === 'list' );
		} );

		document.querySelectorAll( '[data-knowlio-view]' ).forEach( function ( button ) {
			var isActive = button.dataset.knowlioView === view;
			button.classList.toggle( 'is-active', isActive );
			button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var heads = document.querySelectorAll( '[data-knowlio-list-head]' );

		if ( ! heads.length ) {
			return;
		}

		// The switcher ships hidden so it is never a dead control without JS.
		heads.forEach( function ( head ) {
			head.hidden = false;
		} );

		applyView( readStoredView() || 'grid' );
	} );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-knowlio-view]' );

		if ( ! button ) {
			return;
		}

		event.preventDefault();

		var view = button.dataset.knowlioView;

		applyView( view );
		storeView( view );
	} );

	/* ------------------------------------------------- Active TOC highlighting */

	document.addEventListener( 'DOMContentLoaded', function () {
		var links = document.querySelectorAll( '.knowlio-doc-toc-list a' );

		if ( ! links.length || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var byId = {};

		links.forEach( function ( link ) {
			var href = link.getAttribute( 'href' ) || '';

			if ( href.charAt( 0 ) === '#' ) {
				byId[ href.slice( 1 ) ] = link;
			}
		} );

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					var link = byId[ entry.target.id ];

					if ( link ) {
						link.classList.toggle( 'knowlio-toc-active', entry.isIntersecting );
					}
				} );
			},
			{ rootMargin: '-5% 0px -80% 0px' }
		);

		Object.keys( byId ).forEach( function ( id ) {
			var heading = document.getElementById( id );

			if ( heading ) {
				observer.observe( heading );
			}
		} );
	} );

	/* ------------------------------------------------------- Copy code blocks */

	document.addEventListener( 'DOMContentLoaded', function () {
		var copyLabel = i18n.copy || 'Copy';
		var copiedLabel = i18n.copied || 'Copied';

		document.querySelectorAll( '.knowlio-doc-body pre' ).forEach( function ( block ) {
			var button = document.createElement( 'button' );

			button.type = 'button';
			button.className = 'knowlio-code-copy';
			button.textContent = copyLabel;
			button.setAttribute( 'aria-label', copyLabel );

			button.addEventListener( 'click', function () {
				var code = block.querySelector( 'code' );
				var text = code ? code.textContent : block.textContent;

				var done = function () {
					button.textContent = copiedLabel;
					setTimeout( function () {
						button.textContent = copyLabel;
					}, 1600 );
				};

				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( text ).then( done );
					return;
				}

				// Fallback for plain-http sites, where the clipboard API is unavailable.
				var area = document.createElement( 'textarea' );
				area.value = text;
				area.setAttribute( 'readonly', '' );
				area.style.position = 'fixed';
				area.style.opacity = '0';
				document.body.appendChild( area );
				area.select();

				try {
					document.execCommand( 'copy' );
					done();
				} catch ( error ) {
					// Nothing sensible to do; leave the label alone.
				}

				area.remove();
			} );

			block.style.position = 'relative';
			block.appendChild( button );
		} );
	} );
} )();
