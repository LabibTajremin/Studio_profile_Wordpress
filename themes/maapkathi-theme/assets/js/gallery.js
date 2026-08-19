/**
 * Projects gallery: masonry packing and "Load more" (FR-04).
 *
 * The stylesheet already renders a correct, uncropped, responsive grid on
 * its own. This upgrades it to true masonry — items sitting directly under
 * their neighbour rather than on a shared row baseline — and appends
 * further pages without disturbing what is already on screen.
 */
( function () {
	'use strict';

	var galleries = document.querySelectorAll( '[data-mk-gallery]' );
	if ( ! galleries.length ) {
		return;
	}

	/**
	 * Gives every item a row span matching its rendered height, which is
	 * what turns a uniform grid into a packed one.
	 *
	 * @param {HTMLElement} gallery The gallery list.
	 */
	var pack = function ( gallery ) {
		var styles = window.getComputedStyle( gallery );
		var columns = styles.getPropertyValue( 'grid-template-columns' ).split( ' ' ).length;

		// One column is already a single stack; packing it would only add
		// rounding error.
		if ( columns < 2 ) {
			gallery.classList.remove( 'is-packed' );
			Array.prototype.forEach.call( gallery.children, function ( item ) {
				item.style.gridRowEnd = '';
			} );
			return;
		}

		// The column gap is the gutter the admin configured; row-gap is
		// forced to zero in packed mode (see the stylesheet), so the
		// vertical gutter has to be reserved as extra rows inside each
		// item's own span instead.
		var gutter = parseFloat( styles.getPropertyValue( 'column-gap' ) ) || 0;
		var items = Array.prototype.slice.call( gallery.children );

		// Three separate passes, deliberately. Clearing a span and reading a
		// height in the same loop measures a grid that is halfway through
		// being relaid out, which produces nonsense spans and enormous
		// gaps. So: unpack everything, read everything, then write
		// everything.
		gallery.classList.remove( 'is-packed' );
		items.forEach( function ( item ) {
			item.style.gridRowEnd = '';
		} );

		var heights = items.map( function ( item ) {
			return item.getBoundingClientRect().height;
		} );

		gallery.classList.add( 'is-packed' );
		items.forEach( function ( item, index ) {
			// Rows are 1px, so the span is the photo's height plus the
			// gutter. Rounded up, never down: a span half a pixel short
			// clips the bottom of a photo.
			item.style.gridRowEnd = 'span ' + Math.max( 1, Math.ceil( heights[ index ] + gutter ) );
		} );
	};

	var repackAll = function () {
		Array.prototype.forEach.call( galleries, pack );
	};

	// Images can change an item's measured width (scrollbar appearing as
	// the page grows), so pack again once they have all settled.
	repackAll();
	window.addEventListener( 'load', repackAll );

	var resizeTimer = null;
	window.addEventListener( 'resize', function () {
		window.clearTimeout( resizeTimer );
		resizeTimer = window.setTimeout( repackAll, 150 );
	} );

	if ( ! window.mkGallery ) {
		return;
	}

	// Each button names the gallery it belongs to. A page may carry more
	// than one Featured work section, and a document-wide selector here
	// would have every button appending into the first one (FR-03.5).
	Array.prototype.forEach.call( document.querySelectorAll( '[data-mk-gallery-more]' ), function ( button ) {
	var gallery = document.getElementById( button.getAttribute( 'data-mk-gallery-more' ) );
	if ( ! gallery ) {
		return;
	}

	button.addEventListener( 'click', function () {
		var offset = parseInt( button.getAttribute( 'data-offset' ), 10 ) || 0;
		var perPage = parseInt( button.getAttribute( 'data-per-page' ), 10 ) || 12;

		button.disabled = true;
		button.textContent = window.mkGallery.loading;

		window.fetch( window.mkGallery.endpoint + '?offset=' + offset + '&per_page=' + perPage )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.html ) {
					button.remove();
					return;
				}

				// Appending into a template first means the new items are
				// parsed once, and the existing children are never touched
				// — so nothing already on screen moves.
				var holder = document.createElement( 'template' );
				holder.innerHTML = data.html;
				var added = holder.content.children.length;
				gallery.appendChild( holder.content );

				button.setAttribute( 'data-offset', String( offset + added ) );
				pack( gallery );

				if ( data.remaining > 0 && added > 0 ) {
					button.disabled = false;
					button.textContent = window.mkGallery.label;
				} else {
					button.remove();
				}
			} )
			.catch( function () {
				button.disabled = false;
				button.textContent = window.mkGallery.label;
			} );
	} );
	} );
} )();
