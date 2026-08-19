/**
 * Partner logo marquee (FR-10.4).
 *
 * The scroll itself is a CSS animation; this only handles the one thing
 * CSS cannot see — whether the tab is still in front of the user. Hover
 * and reduced-motion pauses are handled in the stylesheet, so the band
 * still behaves correctly if this script never runs.
 */
( function () {
	'use strict';

	var marquees = document.querySelectorAll( '[data-mk-marquee]' );
	if ( ! marquees.length ) {
		return;
	}

	var sync = function () {
		var hidden = document.hidden;
		Array.prototype.forEach.call( marquees, function ( marquee ) {
			marquee.classList.toggle( 'is-paused', hidden );
		} );
	};

	document.addEventListener( 'visibilitychange', sync );
	sync();
} )();
