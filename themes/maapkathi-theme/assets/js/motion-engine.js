/**
 * ~300-line vanilla motion engine (§1). IntersectionObserver-driven scroll
 * reveals, hero carousel with per-slide hold-until-video-ends, reduced-GIF
 * handling, and the cursor/loader/scroll-progress data-* toggles. No scroll
 * listeners for reveals — IntersectionObserver only (§11.3).
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var animateOnce = root.getAttribute( 'data-animate-once' ) !== '0';

	function initScrollReveal() {
		var style = root.getAttribute( 'data-scroll-reveal-style' );
		if ( ! style || 'none' === style || reducedMotion ) {
			return;
		}

		var targets = document.querySelectorAll( '[data-scroll-reveal] > *' );
		if ( ! targets.length ) {
			return;
		}

		targets.forEach( function ( el ) {
			el.classList.add( 'mk-reveal' );
		} );

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						if ( animateOnce ) {
							observer.unobserve( entry.target );
						}
					} else if ( ! animateOnce ) {
						entry.target.classList.remove( 'is-visible' );
					}
				} );
			},
			{ threshold: 0.2 }
		);

		targets.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	function initHeroCarousel() {
		var hero = document.querySelector( '[data-hero]' );
		if ( ! hero ) {
			return;
		}

		var slides = Array.prototype.slice.call( hero.querySelectorAll( '.mk-hero__slide' ) );
		if ( ! slides.length ) {
			return;
		}

		slides[ 0 ].classList.add( 'is-active' );

		if ( slides.length < 2 || '1' === hero.getAttribute( 'data-single' ) ) {
			return; // Single-slide case: no rotation, no length warning (§6.3 rule 4).
		}

		var intervalSeconds = parseInt( hero.getAttribute( 'data-slide-seconds' ), 10 ) || 6;
		var index = 0;
		var maxHold = 20;

		function advance() {
			var current = slides[ index ];
			current.classList.remove( 'is-active' );
			index = ( index + 1 ) % slides.length;
			slides[ index ].classList.add( 'is-active' );
			scheduleNext();
		}

		function scheduleNext() {
			var current = slides[ index ];
			var video = current.querySelector( 'video' );
			var holdUntilVideoEnds = '1' === current.getAttribute( 'data-hold' );

			if ( holdUntilVideoEnds && video ) {
				var onLoaded = function () {
					var duration = Math.min( video.duration || intervalSeconds, maxHold );
					setTimeout( advance, duration * 1000 );
				};
				if ( video.readyState >= 1 ) {
					onLoaded();
				} else {
					video.addEventListener( 'loadedmetadata', onLoaded, { once: true } );
				}
			} else {
				setTimeout( advance, intervalSeconds * 1000 );
			}
		}

		scheduleNext();
	}

	function initReducedMotionGifs() {
		if ( ! reducedMotion ) {
			return;
		}
		document.querySelectorAll( '.mk-hero__media--gif[data-reduced-src]' ).forEach( function ( img ) {
			img.src = img.getAttribute( 'data-reduced-src' );
		} );
	}

	function initScrollProgress() {
		if ( '1' !== root.getAttribute( 'data-scroll-progress' ) ) {
			return;
		}
		var bar = document.createElement( 'div' );
		bar.className = 'mk-scroll-progress';
		document.body.appendChild( bar );

		window.addEventListener( 'scroll', function () {
			var scrolled = window.scrollY;
			var height = document.documentElement.scrollHeight - window.innerHeight;
			var pct = height > 0 ? ( scrolled / height ) * 100 : 0;
			bar.style.setProperty( '--mk-scroll-progress', pct + '%' );
		}, { passive: true } );
	}

	function initCursor() {
		var style = root.getAttribute( 'data-cursor-style' );
		if ( ! style || 'none' === style || ! window.matchMedia( '(pointer: fine)' ).matches ) {
			return;
		}
		var cursor = document.createElement( 'div' );
		cursor.className = 'mk-cursor mk-cursor--' + style;
		document.body.appendChild( cursor );

		document.addEventListener( 'mousemove', function ( e ) {
			cursor.style.left = e.clientX + 'px';
			cursor.style.top = e.clientY + 'px';
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initScrollReveal();
		initHeroCarousel();
		initReducedMotionGifs();
		initScrollProgress();
		initCursor();
	} );
} )();
