/**
 * Knowlio Docs admin bundle.
 *
 * One ajax function and a set of delegated handlers. Markup declares intent
 * through data attributes; there is no per-feature JavaScript.
 */
( function () {
	'use strict';

	var helper = window.knowlio_helper || {};
	var i18n   = helper.i18n || {};

	/* ------------------------------------------------------------------ Ajax */

	/**
	 * Call a Knowlio Docs route.
	 *
	 * Params are posted as ordinary form fields rather than packed into one
	 * serialized string, so PHP parses the `article[title]` style names itself
	 * and the server never has to unpack a payload of its own.
	 *
	 * @param {Object}   options        Call options.
	 * @param {string}   options.route  Route name, `controller__action`.
	 * @param {Object|string} options.params Params object or url-encoded string.
	 * @param {Function} options.onDone Called with the parsed JSON response.
	 * @param {Function} options.onFail Called on transport failure.
	 */
	function knowlioCall( options ) {
		var body = new URLSearchParams( options.params || '' );

		// set(), not append(): these four are the envelope and must never be
		// duplicated by a form field that happens to share the name.
		body.set( 'action', helper.route_action );
		body.set( 'route_name', options.route );
		body.set( 'return_format', 'json' );
		body.set( 'layout', 'none' );

		return fetch( helper.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( typeof options.onDone === 'function' ) {
					options.onDone( json );
				}
				return json;
			} )
			.catch( function ( error ) {
				if ( typeof options.onFail === 'function' ) {
					options.onFail( error );
				} else {
					notify( i18n.error || 'Error', 'error' );
				}
			} );
	}

	window.knowlioCall = knowlioCall;

	/* --------------------------------------------------------- Notifications */

	/**
	 * Show a toast. `message` may be a string or an array of strings.
	 *
	 * @param {string|Array} message Message(s).
	 * @param {string}       status  `success` or `error`.
	 */
	function notify( message, status ) {
		var stack = document.querySelector( '.knowlio-notifications-w' );

		if ( ! stack ) {
			return;
		}

		var node = document.createElement( 'div' );
		node.className = 'knowlio-notification knowlio-notification-' + ( status || 'success' );

		if ( Array.isArray( message ) ) {
			var list = document.createElement( 'ul' );
			message.forEach( function ( item ) {
				var li = document.createElement( 'li' );
				li.textContent = item;
				list.appendChild( li );
			} );
			node.appendChild( list );
		} else {
			// textContent, not innerHTML: server messages may echo user input.
			node.textContent = String( message );
		}

		stack.appendChild( node );

		setTimeout( function () {
			node.style.opacity = '0';
			setTimeout( function () {
				node.remove();
			}, 250 );
		}, 4200 );
	}

	window.knowlioNotify = notify;

	/* ------------------------------------------------------------ Side panel */

	function openSidePanel( html ) {
		var wrap = document.querySelector( '.knowlio-side-panel-w' );

		if ( ! wrap ) {
			return null;
		}

		var body = wrap.querySelector( '.knowlio-side-panel-body' );
		body.innerHTML = html;
		wrap.hidden = false;
		document.body.style.overflow = 'hidden';

		var focusable = body.querySelector( 'input:not([type=hidden]), select, textarea, button' );
		if ( focusable ) {
			focusable.focus();
		}

		return body;
	}

	function closeSidePanel() {
		var wrap = document.querySelector( '.knowlio-side-panel-w' );

		if ( ! wrap ) {
			return;
		}

		// Tear the TinyMCE instance down before the markup it is attached to is
		// removed, otherwise re-opening the panel would try to init a second
		// editor on a stale id and silently fail.
		destroyArticleEditor();

		wrap.hidden = true;
		wrap.querySelector( '.knowlio-side-panel-body' ).innerHTML = '';
		document.body.style.overflow = '';
	}

	window.knowlioCloseSidePanel = closeSidePanel;

	function openLightbox( html ) {
		var wrap = document.querySelector( '.knowlio-lightbox-w' );

		if ( ! wrap ) {
			return null;
		}

		var body = wrap.querySelector( '.knowlio-lightbox-body' );
		body.innerHTML = html;
		wrap.hidden = false;

		return body;
	}

	function closeLightbox() {
		var wrap = document.querySelector( '.knowlio-lightbox-w' );

		if ( wrap ) {
			wrap.hidden = true;
			wrap.querySelector( '.knowlio-lightbox-body' ).innerHTML = '';
		}
	}

	/* ------------------------------------------------- Generic action trigger */

	document.addEventListener( 'click', function ( event ) {
		// Ignore clicks that landed on a nested control with its own behaviour.
		if ( event.target.closest( '[data-knowlio-delete], .knowlio-row-delete, a[href]:not([data-knowlio-action])' ) ) {
			return;
		}

		var trigger = event.target.closest( '[data-knowlio-action]' );

		if ( ! trigger ) {
			return;
		}

		event.preventDefault();

		var target = trigger.dataset.knowlioTarget || 'side-panel';

		knowlioCall( {
			route: trigger.dataset.knowlioAction,
			params: trigger.dataset.knowlioParams || '',
			onDone: function ( response ) {
				if ( response.status === 'error' ) {
					notify( response.message, 'error' );
					return;
				}

				var container = null;

				if ( target === 'side-panel' ) {
					container = openSidePanel( response.message );
				} else if ( target === 'lightbox' ) {
					container = openLightbox( response.message );
				} else {
					container = document.querySelector( target );
					if ( container ) {
						container.innerHTML = response.message;
					}
				}

				var callback = trigger.dataset.knowlioAfterCall;

				if ( callback && typeof window[ callback ] === 'function' ) {
					window[ callback ]( container, response );
				}
			}
		} );
	} );

	/* ------------------------------------------------------- Panel dismissal */

	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '[data-knowlio-close-panel]' ) ) {
			event.preventDefault();
			closeSidePanel();
		}

		if ( event.target.closest( '[data-knowlio-close-lightbox]' ) ) {
			event.preventDefault();
			closeLightbox();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Escape' ) {
			return;
		}

		// When a media modal or a fullscreen editor is open, Escape belongs to it;
		// closing the panel underneath as well would be jarring.
		if ( document.querySelector( '.media-modal:not([style*="display: none"])' ) || document.querySelector( '.mce-fullscreen' ) ) {
			return;
		}

		closeSidePanel();
		closeLightbox();
		hideConfirm();
	} );

	/* --------------------------------------------------------- Form submission */

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( 'form[data-route-name]' );

		if ( ! form ) {
			return;
		}

		event.preventDefault();

		var submitButton = form.querySelector( '[type=submit]' );
		var originalText = submitButton ? submitButton.textContent : '';

		if ( submitButton ) {
			submitButton.disabled = true;
			submitButton.textContent = i18n.saving || 'Saving...';
		}

		clearFormMessages( form );

		// TinyMCE holds its content in an iframe and only writes it back to the
		// underlying textarea on save; flush every editor before we serialize.
		if ( window.tinymce ) {
			window.tinymce.triggerSave();
		}

		knowlioCall( {
			route: form.dataset.routeName,
			params: new URLSearchParams( new FormData( form ) ).toString(),
			onDone: function ( response ) {
				if ( submitButton ) {
					submitButton.disabled = false;
					submitButton.textContent = originalText;
				}

				if ( response.status === 'error' ) {
					showFormMessage( form, response.message, 'error' );
					notify( response.message, 'error' );
					return;
				}

				notify( response.message, 'success' );

				if ( form.closest( '.knowlio-side-panel' ) ) {
					closeSidePanel();
				}

				if ( response.reload ) {
					refreshTable();
				}
			},
			onFail: function () {
				if ( submitButton ) {
					submitButton.disabled = false;
					submitButton.textContent = originalText;
				}
				notify( i18n.error || 'Error', 'error' );
			}
		} );
	} );

	function clearFormMessages( form ) {
		form.querySelectorAll( '.knowlio-form-message' ).forEach( function ( node ) {
			node.remove();
		} );
	}

	function showFormMessage( form, message, status ) {
		var content = form.querySelector( '.knowlio-form-content' ) || form;
		var node    = document.createElement( 'div' );

		node.className = 'knowlio-form-message knowlio-form-message-' + status;

		if ( Array.isArray( message ) ) {
			var list = document.createElement( 'ul' );
			message.forEach( function ( item ) {
				var li = document.createElement( 'li' );
				li.textContent = item;
				list.appendChild( li );
			} );
			node.appendChild( list );
		} else {
			node.textContent = String( message );
		}

		content.prepend( node );
		content.scrollTop = 0;
	}

	/* -------------------------------------------------------- Table refresh */

	var filterTimer = null;

	/**
	 * Re-post the current filter set to the table's index route and swap the rows.
	 *
	 * @param {number} pageNumber Optional page to load.
	 */
	function refreshTable( pageNumber ) {
		var table = document.querySelector( '[data-knowlio-table-route]' );

		if ( ! table ) {
			return;
		}

		var params = new URLSearchParams();

		table.querySelectorAll( '.knowlio-table-filter' ).forEach( function ( input ) {
			if ( input.name && input.value !== '' ) {
				params.append( input.name, input.value );
			}
		} );

		var picker = table.querySelector( '.knowlio-page-picker' );
		params.append( 'page_number', pageNumber || ( picker ? picker.value : 1 ) );

		table.classList.add( 'knowlio-is-loading' );

		knowlioCall( {
			route: table.dataset.knowlioTableRoute,
			params: params.toString(),
			onDone: function ( response ) {
				table.classList.remove( 'knowlio-is-loading' );

				if ( response.status === 'error' ) {
					notify( response.message, 'error' );
					return;
				}

				var body = table.querySelector( '.knowlio-table-body' );

				if ( body ) {
					body.innerHTML = response.message;
				}

				updatePaginationLabels( table, response );
			},
			onFail: function () {
				table.classList.remove( 'knowlio-is-loading' );
			}
		} );
	}

	window.knowlioRefreshTable = refreshTable;

	function updatePaginationLabels( table, response ) {
		var map = {
			'.knowlio-pagination-from': response.showing_from,
			'.knowlio-pagination-to': response.showing_to,
			'.knowlio-pagination-total': response.total_records
		};

		Object.keys( map ).forEach( function ( selector ) {
			if ( typeof map[ selector ] === 'undefined' ) {
				return;
			}
			table.querySelectorAll( selector ).forEach( function ( node ) {
				node.textContent = map[ selector ];
			} );
		} );

		var picker = table.querySelector( '.knowlio-page-picker' );

		if ( picker && response.total_pages ) {
			var current = picker.value;
			picker.innerHTML = '';

			for ( var i = 1; i <= response.total_pages; i++ ) {
				var option = document.createElement( 'option' );
				option.value = i;
				option.textContent = i;
				picker.appendChild( option );
			}

			picker.value = Math.min( current, response.total_pages ) || 1;
		}
	}

	document.addEventListener( 'input', function ( event ) {
		if ( ! event.target.classList.contains( 'knowlio-table-filter' ) ) {
			return;
		}

		clearTimeout( filterTimer );
		filterTimer = setTimeout( function () {
			refreshTable( 1 );
		}, 350 );
	} );

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.classList.contains( 'knowlio-table-filter' ) ) {
			clearTimeout( filterTimer );
			refreshTable( 1 );
		}

		if ( event.target.classList.contains( 'knowlio-page-picker' ) ) {
			refreshTable( event.target.value );
		}
	} );

	/* ---------------------------------------------------- Delete confirmation */

	var pendingDelete = null;

	function showConfirm( message ) {
		var wrap = document.querySelector( '.knowlio-confirm-w' );

		if ( ! wrap ) {
			return;
		}

		wrap.querySelector( '.knowlio-confirm-message' ).textContent = message;
		wrap.hidden = false;
	}

	function hideConfirm() {
		var wrap = document.querySelector( '.knowlio-confirm-w' );

		if ( wrap ) {
			wrap.hidden = true;
		}

		pendingDelete = null;
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-knowlio-delete]' );

		if ( trigger ) {
			event.preventDefault();
			event.stopPropagation();

			pendingDelete = {
				route: trigger.dataset.knowlioDelete,
				id: trigger.dataset.knowlioId,
				nonce: trigger.dataset.knowlioNonce
			};

			showConfirm( i18n.confirm_delete || 'Are you sure?' );
			return;
		}

		if ( event.target.closest( '[data-knowlio-confirm-cancel]' ) ) {
			event.preventDefault();
			hideConfirm();
			return;
		}

		if ( event.target.closest( '[data-knowlio-confirm-ok]' ) && pendingDelete ) {
			event.preventDefault();

			var job = pendingDelete;
			hideConfirm();

			knowlioCall( {
				route: job.route,
				params: { id: job.id, _wpnonce: job.nonce },
				onDone: function ( response ) {
					notify( response.message, response.status );

					if ( response.status === 'success' ) {
						refreshTable();
					}
				}
			} );
		}
	} );

	/* ------------------------------------------------------------ Copy buttons */

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-knowlio-copy], [data-knowlio-copy-text]' );

		if ( ! button ) {
			return;
		}

		event.preventDefault();

		var text = button.dataset.knowlioCopyText;

		if ( typeof text === 'undefined' ) {
			var block = button.closest( '.knowlio-code-block' );
			var code  = block ? block.querySelector( 'code' ) : null;
			text = code ? code.textContent : '';
		}

		copyText( text, button );
	} );

	function copyText( text, button ) {
		var label = button.textContent;

		var done = function () {
			button.textContent = i18n.copied || 'Copied';
			setTimeout( function () {
				button.textContent = label;
			}, 1600 );
		};

		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( done );
			return;
		}

		// Fallback for plain-http dev sites, where the clipboard API is unavailable.
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
			// Nothing sensible to do; leave the button label alone.
		}

		area.remove();
	}

	/* -------------------------------------------------------- Mobile menu */

	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '.knowlio-mobile-menu-trigger, .knowlio-menu-fold-trigger' ) ) {
			event.preventDefault();
			var wrapper = document.querySelector( '.knowlio-all-wrapper' );

			if ( wrapper ) {
				wrapper.classList.toggle( 'knowlio-menu-open' );
			}
		}
	} );

	/* ----------------------------------------------------------- Article editor */

	// The id of the textarea the WordPress editor is attached to. It is fixed,
	// because only one article editor is ever open at a time.
	var ARTICLE_EDITOR_ID = 'knowlio-article-content';

	/**
	 * Toolbar configuration passed to wp.editor.initialize().
	 *
	 * `paste_as_text: false` plus the bundled paste/wordfilter plugins is what
	 * lets a .docx or Google-Doc paste keep its headings, bold and lists while
	 * the Word-specific markup is discarded. `formatselect` gives the H2/H3
	 * dropdown that feeds the reader-facing table of contents.
	 *
	 * @return {Object} Settings for wp.editor.initialize.
	 */
	function articleEditorSettings() {
		return {
			mediaButtons: true,
			quicktags: { buttons: 'strong,em,link,ul,ol,li,code,close' },
			tinymce: {
				wpautop: true,
				height: 380,
				menubar: false,
				plugins: 'lists,link,paste,wordpress,wplink,wptextpattern,charmap,hr,image,wpview',
				toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_adv',
				toolbar2: 'strikethrough,hr,pastetext,removeformat,charmap,outdent,indent,undo,redo',
				paste_as_text: false,
				block_formats: 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Preformatted=pre',
				setup: function ( editor ) {
					// Mirror TinyMCE edits back to the textarea live, so the submit
					// handler's serialization always has the current HTML even if
					// triggerSave has not fired yet.
					editor.on( 'change keyup input', function () {
						editor.save();
					} );
				}
			}
		};
	}

	/**
	 * Whether the WordPress editor JS API is available.
	 *
	 * @return {boolean} True when wp.editor can be used.
	 */
	function hasWpEditor() {
		return !! ( window.wp && window.wp.editor && typeof window.wp.editor.initialize === 'function' );
	}

	/**
	 * Attach the WordPress editor to the article content textarea.
	 */
	function initArticleEditor() {
		if ( ! document.getElementById( ARTICLE_EDITOR_ID ) ) {
			return;
		}

		// No editor API (or on a very old WP): the plain textarea is a working
		// fallback that still accepts pasted or hand-written HTML.
		if ( ! hasWpEditor() ) {
			return;
		}

		// Guard against a lingering instance from a previous open.
		destroyArticleEditor();
		window.wp.editor.initialize( ARTICLE_EDITOR_ID, articleEditorSettings() );
	}

	/**
	 * Detach the WordPress editor, flushing its content to the textarea first.
	 */
	function destroyArticleEditor() {
		if ( ! hasWpEditor() ) {
			return;
		}

		if ( window.tinymce ) {
			var editor = window.tinymce.get( ARTICLE_EDITOR_ID );

			if ( editor ) {
				editor.save();
			}
		}

		try {
			window.wp.editor.remove( ARTICLE_EDITOR_ID );
		} catch ( error ) {
			// Nothing to remove; ignore.
		}
	}

	/**
	 * Set the article editor's HTML, whether it is in Visual (TinyMCE) or Text
	 * (plain textarea) mode.
	 *
	 * @param {string} html Content to place in the editor.
	 */
	function setArticleContent( html ) {
		var editor = window.tinymce && window.tinymce.get( ARTICLE_EDITOR_ID );

		if ( editor && ! editor.isHidden() ) {
			editor.setContent( html );
			editor.save();
			return;
		}

		var textarea = document.getElementById( ARTICLE_EDITOR_ID );

		if ( textarea ) {
			textarea.value = html;
		}
	}

	/**
	 * Whether the editor currently holds any content.
	 *
	 * @return {boolean} True if there is something to overwrite.
	 */
	function articleHasContent() {
		var editor = window.tinymce && window.tinymce.get( ARTICLE_EDITOR_ID );

		if ( editor && ! editor.isHidden() ) {
			return '' !== editor.getContent().trim();
		}

		var textarea = document.getElementById( ARTICLE_EDITOR_ID );

		return !! ( textarea && textarea.value.trim() );
	}

	document.addEventListener( 'change', function ( event ) {
		if ( 'knowlio-article-template' !== event.target.id ) {
			return;
		}

		var slug = event.target.value;
		var templates = ( window.knowlio_helper && window.knowlio_helper.templates ) || {};

		if ( ! slug || ! templates[ slug ] ) {
			return;
		}

		// Never silently discard something the author already wrote.
		if ( articleHasContent() && ! window.confirm( i18n.confirm_template || 'Replace the current content?' ) ) {
			event.target.value = '';
			return;
		}

		setArticleContent( templates[ slug ].html );
		event.target.value = '';
	} );

	/**
	 * Runs after the article form is injected into the side panel.
	 *
	 * @param {HTMLElement} container Injected markup root.
	 */
	window.knowlioInitArticleForm = function ( container ) {
		if ( ! container ) {
			return;
		}

		initArticleEditor();

		document.dispatchEvent(
			new CustomEvent( 'knowlio:article-form-ready', { detail: { container: container } } )
		);
	};
} )();
