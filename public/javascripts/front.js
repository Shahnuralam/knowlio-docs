/**
 * MiniDocs frontend bundle.
 *
 * Navigation is plain links, so the knowledge base works with JavaScript
 * disabled. This file only adds two conveniences on top.
 */
( function () {
	'use strict';

	/* ------------------------------------------------- Active TOC highlighting */

	document.addEventListener( 'DOMContentLoaded', function () {
		var links = document.querySelectorAll( '.md-doc-toc-list a' );

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
						link.classList.toggle( 'md-toc-active', entry.isIntersecting );
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
		document.querySelectorAll( '.md-doc-body pre' ).forEach( function ( block ) {
			var button = document.createElement( 'button' );

			button.type = 'button';
			button.className = 'md-code-copy';
			button.textContent = 'Copy';

			button.addEventListener( 'click', function () {
				var code = block.querySelector( 'code' );
				var text = code ? code.textContent : block.textContent;

				var done = function () {
					button.textContent = 'Copied';
					setTimeout( function () {
						button.textContent = 'Copy';
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
