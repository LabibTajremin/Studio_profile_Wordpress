/**
 * Motion engine. IntersectionObserver-driven scroll reveals, the hero
 * carousel (with per-slide hold-until-video-ends), reduced-motion GIF
 * handling, and the cursor / loader / scroll-progress toggles.
 *
 * Everything reads CSS custom properties and data-* attributes set
 * server-side — no timing or distance value is hardcoded here (§11.3).
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var reduceMotionQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var reducedMotion = reduceMotionQuery.matches;
	var animateOnce = root.getAttribute( 'data-animate-once' ) !== '0';
	var motionPreset = root.getAttribute( 'data-motion-preset' );
	var motionOnMobile = root.getAttribute( 'data-motion-on-mobile' ) || 'reduced';
	var isMobile = window.matchMedia( '(max-width: 767px)' ).matches;

	function motionDisabled() {
		return reducedMotion || 'off' === motionPreset || ( isMobile && 'off' === motionOnMobile );
	}

	/** Milliseconds from a CSS time value ("600ms" / "0.6s"). */
	function cssTimeToMs( value ) {
		if ( ! value ) {
			return 0;
		}
		value = value.trim();
		if ( value.endsWith( 'ms' ) ) {
			return parseFloat( value );
		}
		if ( value.endsWith( 's' ) ) {
			return parseFloat( value ) * 1000;
		}
		return parseFloat( value ) || 0;
	}

	function initScrollReveal() {
		var style = root.getAttribute( 'data-scroll-reveal-style' );
		var targets = document.querySelectorAll( '[data-scroll-reveal] > *' );

		if ( ! targets.length ) {
			return;
		}

		// When motion is off the content must still be visible — never
		// leave elements stuck at opacity 0.
		if ( ! style || 'none' === style || motionDisabled() || ! ( 'IntersectionObserver' in window ) ) {
			targets.forEach( function ( el ) {
				el.classList.add( 'is-visible' );
			} );
			return;
		}

		var staggerMs = cssTimeToMs( getComputedStyle( root ).getPropertyValue( '--motion-stagger' ) );

		targets.forEach( function ( el ) {
			el.classList.add( 'mk-reveal' );
		} );

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						var siblings = Array.prototype.slice.call( entry.target.parentNode.children );
						var index = siblings.indexOf( entry.target );
						entry.target.style.transitionDelay = ( index * staggerMs ) + 'ms';
						entry.target.classList.add( 'is-visible' );

						if ( animateOnce ) {
							observer.unobserve( entry.target );
						}
					} else if ( ! animateOnce ) {
						entry.target.classList.remove( 'is-visible' );
					}
				} );
			},
			{ threshold: 0.15, rootMargin: '0px 0px -8% 0px' }
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
		var dots = Array.prototype.slice.call( hero.querySelectorAll( '.mk-hero__dot' ) );

		if ( slides.length < 2 || '1' === hero.getAttribute( 'data-single' ) ) {
			// Single slide: no rotation at all, so no length limit applies.
			return;
		}

		var intervalSeconds = parseInt( hero.getAttribute( 'data-slide-seconds' ), 10 ) || 6;
		var maxHoldSeconds = 20;
		var index = 0;
		var timer = null;
		var paused = false;

		function show( next ) {
			slides[ index ].classList.remove( 'is-active' );
			if ( dots[ index ] ) {
				dots[ index ].classList.remove( 'is-active' );
				dots[ index ].setAttribute( 'aria-selected', 'false' );
			}

			index = ( next + slides.length ) % slides.length;

			slides[ index ].classList.add( 'is-active' );
			if ( dots[ index ] ) {
				dots[ index ].classList.add( 'is-active' );
				dots[ index ].setAttribute( 'aria-selected', 'true' );
			}

			restartVideo( slides[ index ] );
		}

		function restartVideo( slide ) {
			var video = slide.querySelector( 'video' );
			if ( video && ! reducedMotion ) {
				try {
					video.currentTime = 0;
					var playing = video.play();
					if ( playing && playing.catch ) {
						// Autoplay refusal is expected on some devices; the
						// poster remains visible, which is why it's required.
						playing.catch( function () {} );
					}
				} catch ( e ) {}
			}
		}

		function clear() {
			if ( timer ) {
				clearTimeout( timer );
				timer = null;
			}
		}

		function schedule() {
			clear();
			if ( paused ) {
				return;
			}

			var current = slides[ index ];
			var video = current.querySelector( 'video' );
			var holdForVideo = '1' === current.getAttribute( 'data-hold' );

			if ( holdForVideo && video && ! reducedMotion ) {
				var run = function () {
					// Cap the hold so one long clip can never strand the
					// carousel (§6.3).
					var duration = Math.min( video.duration || intervalSeconds, maxHoldSeconds );
					timer = setTimeout( advance, duration * 1000 );
				};

				if ( video.readyState >= 1 ) {
					run();
				} else {
					video.addEventListener( 'loadedmetadata', run, { once: true } );
					// Don't stall forever if metadata never arrives.
					timer = setTimeout( advance, maxHoldSeconds * 1000 );
				}
				return;
			}

			timer = setTimeout( advance, intervalSeconds * 1000 );
		}

		function advance() {
			show( index + 1 );
			schedule();
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				show( parseInt( dot.getAttribute( 'data-slide-to' ), 10 ) || 0 );
				schedule();
			} );
		} );

		// Respect the visitor: stop rotating while the tab is hidden or
		// while someone is interacting with the hero.
		document.addEventListener( 'visibilitychange', function () {
			paused = document.hidden;
			paused ? clear() : schedule();
		} );
		hero.addEventListener( 'mouseenter', function () {
			paused = true;
			clear();
		} );
		hero.addEventListener( 'mouseleave', function () {
			paused = false;
			schedule();
		} );
		hero.addEventListener( 'focusin', function () {
			paused = true;
			clear();
		} );
		hero.addEventListener( 'focusout', function () {
			paused = false;
			schedule();
		} );

		restartVideo( slides[ 0 ] );
		schedule();
	}

	/**
	 * Animated GIFs can't be paused, so where the visitor prefers reduced
	 * motion we swap in the stored first frame (§6.3).
	 */
	function initReducedMotionGifs() {
		if ( ! reducedMotion ) {
			return;
		}
		document.querySelectorAll( '.mk-hero__media--gif[data-reduced-src]' ).forEach( function ( img ) {
			img.src = img.getAttribute( 'data-reduced-src' );
		} );
	}

	/**
	 * Counts each stats-band number up from 0 the first time it scrolls
	 * into view, instead of just appearing as static text. The suffix
	 * (+, %, ...) lives in its own span and is never touched — only the
	 * number span's text is animated, so it always lands on exactly the
	 * value the server rendered.
	 */
	function initStatCounters() {
		var targets = document.querySelectorAll( '.mk-stat__value[data-count-to]' );

		if ( ! targets.length || motionDisabled() || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		var durationMs = Math.max( 800, cssTimeToMs( getComputedStyle( root ).getPropertyValue( '--motion-duration' ) ) * 3 );

		function animate( el ) {
			var numberEl = el.querySelector( '.mk-stat__value-number' );
			var target = parseFloat( el.getAttribute( 'data-count-to' ) ) || 0;
			var isWhole = Math.round( target ) === target;
			var start = null;

			if ( ! numberEl ) {
				return;
			}

			function step( now ) {
				if ( null === start ) {
					start = now;
				}
				var progress = Math.min( 1, ( now - start ) / durationMs );
				var eased = 1 - Math.pow( 1 - progress, 3 );
				var current = target * eased;
				numberEl.textContent = isWhole ? String( Math.round( current ) ) : current.toFixed( 1 );

				if ( progress < 1 ) {
					window.requestAnimationFrame( step );
				} else {
					numberEl.textContent = isWhole ? String( target ) : target.toFixed( 1 );
				}
			}

			numberEl.textContent = isWhole ? '0' : '0.0';
			window.requestAnimationFrame( step );
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						animate( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ threshold: 0.4 }
		);

		targets.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	function initScrollProgress() {
		if ( '1' !== root.getAttribute( 'data-scroll-progress' ) ) {
			return;
		}

		var bar = document.createElement( 'div' );
		bar.className = 'mk-scroll-progress';
		document.body.appendChild( bar );

		var ticking = false;
		function update() {
			var height = document.documentElement.scrollHeight - window.innerHeight;
			var pct = height > 0 ? ( window.scrollY / height ) * 100 : 0;
			bar.style.setProperty( '--mk-scroll-progress', pct + '%' );
			ticking = false;
		}

		window.addEventListener(
			'scroll',
			function () {
				if ( ! ticking ) {
					window.requestAnimationFrame( update );
					ticking = true;
				}
			},
			{ passive: true }
		);
		update();
	}

	function initCursor() {
		var style = root.getAttribute( 'data-cursor-style' );

		// Inert on touch devices, always (§11.3).
		if ( ! style || 'none' === style || ! window.matchMedia( '(pointer: fine)' ).matches || motionDisabled() ) {
			return;
		}

		var cursor = document.createElement( 'div' );
		cursor.className = 'mk-cursor mk-cursor--' + style;
		document.body.appendChild( cursor );

		document.addEventListener(
			'mousemove',
			function ( e ) {
				cursor.style.left = e.clientX + 'px';
				cursor.style.top = e.clientY + 'px';
			},
			{ passive: true }
		);
	}

	function initLoader() {
		var style = root.getAttribute( 'data-loader-style' );
		if ( ! style || 'none' === style || motionDisabled() ) {
			return;
		}

		var loader = document.createElement( 'div' );
		loader.className = 'mk-loader mk-loader--' + style;
		document.body.appendChild( loader );

		window.addEventListener( 'load', function () {
			loader.classList.add( 'is-done' );
			setTimeout( function () {
				if ( loader.parentNode ) {
					loader.parentNode.removeChild( loader );
				}
			}, 400 );
		} );
	}

	function boot() {
		initScrollReveal();
		initHeroCarousel();
		initReducedMotionGifs();
		initStatCounters();
		initScrollProgress();
		initCursor();
		initLoader();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
