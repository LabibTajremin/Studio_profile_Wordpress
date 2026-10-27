/**
 * Featured-work rail arrows.
 *
 * The rail itself scrolls with no JavaScript — touch, trackpad, scrollbar
 * and keyboard all work from CSS alone. This file only adds the pointer
 * affordance on top, which is why the buttons are created here rather than
 * rendered in PHP: if the script never loads, there are no dead controls
 * sitting on the page.
 */
( function () {
	'use strict';

	var ARROWS = {
		prev: 'M15 5 8 12l7 7',
		next: 'M9 5l7 7-7 7'
	};

	function button( direction, label ) {
		var el = document.createElement( 'button' );
		el.type = 'button';
		el.className = 'mk-showcase-rail__nav mk-showcase-rail__nav--' + direction;
		el.setAttribute( 'aria-label', label );
		el.innerHTML =
			'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
			'<path d="' + ARROWS[ direction ] + '" stroke-linecap="round" stroke-linejoin="round" />' +
			'</svg>';
		return el;
	}

	function setup( rail ) {
		var track = rail.querySelector( '.mk-showcase' );
		if ( ! track ) {
			return;
		}

		var strings = window.mkShowcase || {};
		var prev = button( 'prev', strings.previous || 'Previous' );
		var next = button( 'next', strings.next || 'Next' );

		function step() {
			// One tile plus its gap, so a click advances by exactly one
			// snap position rather than an arbitrary pixel amount.
			var item = track.querySelector( '.mk-showcase__item' );
			if ( ! item ) {
				return track.clientWidth;
			}
			var styles = window.getComputedStyle( track );
			var gap = parseFloat( styles.columnGap || styles.gap || '0' ) || 0;
			return item.getBoundingClientRect().width + gap;
		}

		function sync() {
			// A 2px tolerance: fractional layout widths mean scrollLeft can
			// stop just short of the true maximum, which would leave the
			// arrow enabled at the end of the rail with nothing to do.
			var max = track.scrollWidth - track.clientWidth;
			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft >= max - 2;

			// With everything already visible there is nothing to scroll,
			// so neither arrow earns its place.
			var overflows = max > 2;
			prev.hidden = ! overflows;
			next.hidden = ! overflows;
		}

		function scrollBy( amount ) {
			var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			track.scrollBy( { left: amount, behavior: reduce ? 'auto' : 'smooth' } );
		}

		prev.addEventListener( 'click', function () {
			scrollBy( -step() );
		} );
		next.addEventListener( 'click', function () {
			scrollBy( step() );
		} );

		track.addEventListener( 'scroll', sync, { passive: true } );
		window.addEventListener( 'resize', sync );

		rail.appendChild( prev );
		rail.appendChild( next );
		sync();

		// Images arriving late change scrollWidth, so re-check once they do.
		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( sync ).observe( track );
		}
	}

	function init() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-mk-showcase]' ), setup );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
