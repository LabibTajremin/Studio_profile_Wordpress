/**
 * Project gallery lightbox (§3.5 Lightbox.tsx port). Escape to close,
 * arrow keys to navigate, swipe on touch, focus trapped while open and
 * restored to the trigger on close.
 */
( function () {
	'use strict';

	var lastTrigger = null;
	var currentIndex = 0;
	var images = [];
	var overlay = null;

	function buildOverlay() {
		overlay = document.createElement( 'div' );
		overlay.className = 'mk-lightbox';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.innerHTML =
			'<button class="mk-lightbox__close" aria-label="Close">&times;</button>' +
			'<button class="mk-lightbox__prev" aria-label="Previous">&larr;</button>' +
			'<img class="mk-lightbox__image" alt="" />' +
			'<button class="mk-lightbox__next" aria-label="Next">&rarr;</button>';
		document.body.appendChild( overlay );

		overlay.querySelector( '.mk-lightbox__close' ).addEventListener( 'click', close );
		overlay.querySelector( '.mk-lightbox__prev' ).addEventListener( 'click', function () {
			show( currentIndex - 1 );
		} );
		overlay.querySelector( '.mk-lightbox__next' ).addEventListener( 'click', function () {
			show( currentIndex + 1 );
		} );
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				close();
			}
		} );

		var touchStartX = null;
		overlay.addEventListener( 'touchstart', function ( e ) {
			touchStartX = e.changedTouches[ 0 ].clientX;
		} );
		overlay.addEventListener( 'touchend', function ( e ) {
			if ( null === touchStartX ) {
				return;
			}
			var delta = e.changedTouches[ 0 ].clientX - touchStartX;
			if ( Math.abs( delta ) > 40 ) {
				show( currentIndex + ( delta < 0 ? 1 : -1 ) );
			}
			touchStartX = null;
		} );
	}

	function show( index ) {
		currentIndex = ( index + images.length ) % images.length;
		var img = overlay.querySelector( '.mk-lightbox__image' );
		img.src = images[ currentIndex ].getAttribute( 'href' ) || images[ currentIndex ].src;
		img.alt = images[ currentIndex ].getAttribute( 'data-alt' ) || '';
	}

	function open( gallery, index ) {
		images = Array.prototype.slice.call( gallery.querySelectorAll( '[data-lightbox-item]' ) );
		if ( ! overlay ) {
			buildOverlay();
		}
		overlay.classList.add( 'is-open' );
		show( index );
		overlay.querySelector( '.mk-lightbox__close' ).focus();
		document.addEventListener( 'keydown', onKeydown );
	}

	function close() {
		if ( ! overlay ) {
			return;
		}
		overlay.classList.remove( 'is-open' );
		document.removeEventListener( 'keydown', onKeydown );
		if ( lastTrigger ) {
			lastTrigger.focus();
		}
	}

	function onKeydown( e ) {
		if ( 'Escape' === e.key ) {
			close();
		} else if ( 'ArrowLeft' === e.key ) {
			show( currentIndex - 1 );
		} else if ( 'ArrowRight' === e.key ) {
			show( currentIndex + 1 );
		} else if ( 'Tab' === e.key ) {
			var focusable = overlay.querySelectorAll( 'button' );
			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '[data-lightbox-gallery]' ).forEach( function ( gallery ) {
			var items = gallery.querySelectorAll( '[data-lightbox-item]' );
			items.forEach( function ( item, index ) {
				item.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					lastTrigger = item;
					open( gallery, index );
				} );
			} );
		} );
	} );
} )();
