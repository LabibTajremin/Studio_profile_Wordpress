/**
 * Visitor light/dark toggle (§3.5 ThemeToggle.tsx port). Independent of the
 * admin's `mode` setting. Persists via localStorage + cookie so header.php's
 * pre-paint script can read it before first render (no flash of wrong mode).
 */
( function () {
	'use strict';

	var STORAGE_KEY = 'mk-theme';
	var root = document.documentElement;

	function systemPrefersDark() {
		return window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
	}

	function applyTheme( theme ) {
		root.setAttribute( 'data-theme', theme );
	}

	function persist( theme ) {
		try {
			localStorage.setItem( STORAGE_KEY, theme );
		} catch ( e ) {
			/* localStorage may be unavailable (private mode) — cookie still works */
		}
		document.cookie = STORAGE_KEY + '=' + theme + ';path=/;max-age=' + ( 60 * 60 * 24 * 365 ) + ';SameSite=Lax';
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		// The theme toggle is optional — a site can render a header without
		// it. It used to early-return here, which took the mobile menu's
		// wiring down with it, so the menu would not open at all on those
		// sites. Each feature now stands on its own.
		var toggle = document.querySelector( '.mk-theme-toggle' );
		if ( toggle ) {
			toggle.addEventListener( 'click', function () {
				var current = root.getAttribute( 'data-theme' ) === 'dark' ? 'dark' : 'light';
				var next = current === 'dark' ? 'light' : 'dark';
				applyTheme( next );
				persist( next );
			} );
		}

		var menuToggle = document.querySelector( '.mk-menu-toggle' );
		var mobileMenu = document.getElementById( 'mk-mobile-menu' );

		if ( menuToggle && mobileMenu ) {
			var closeBtn = mobileMenu.querySelector( '.mk-mobile-menu__close' );

			function setMenu( open ) {
				mobileMenu.hidden = ! open;
				mobileMenu.classList.toggle( 'is-open', open );
				menuToggle.setAttribute( 'aria-expanded', String( open ) );

				// The overlay is position:fixed over the whole viewport, so
				// without this the page behind it still scrolls under the
				// menu on touch devices.
				document.body.classList.toggle( 'mk-menu-open', open );

				// Focus follows the overlay: into it on open so the next tab
				// stop is the close button rather than something behind the
				// menu, and back to the hamburger on close.
				if ( open ) {
					if ( closeBtn ) {
						closeBtn.focus();
					}
				} else {
					menuToggle.focus();
				}
			}

			menuToggle.addEventListener( 'click', function () {
				setMenu( mobileMenu.hidden );
			} );

			// The close button existed in the markup and in the stylesheet
			// but was never wired up. Together with the overlay covering the
			// hamburger (it sits at z-index 200, the header at 100), that
			// left an opened menu with no way out at all.
			if ( closeBtn ) {
				closeBtn.addEventListener( 'click', function () {
					setMenu( false );
				} );
			}

			Array.prototype.forEach.call( mobileMenu.querySelectorAll( 'a' ), function ( link ) {
				link.addEventListener( 'click', function () {
					setMenu( false );
				} );
			} );

			document.addEventListener( 'keydown', function ( event ) {
				if ( ( 'Escape' === event.key || 'Esc' === event.key ) && ! mobileMenu.hidden ) {
					setMenu( false );
				}
			} );

			// Rotating to landscape can cross the 900px breakpoint, where the
			// overlay is display:none and the inline nav takes over. Leaving
			// the open state behind would restore a stuck menu on rotating
			// back, and would leave the body scroll-locked in the meantime.
			if ( window.matchMedia ) {
				var wide = window.matchMedia( '(min-width: 900px)' );
				var onWide = function ( event ) {
					if ( event.matches && ! mobileMenu.hidden ) {
						setMenu( false );
					}
				};
				if ( wide.addEventListener ) {
					wide.addEventListener( 'change', onWide );
				} else if ( wide.addListener ) {
					wide.addListener( onWide );
				}
			}
		}

		var copyBtn = document.querySelector( '.mk-copy-login' );
		if ( copyBtn ) {
			copyBtn.addEventListener( 'click', function () {
				var url = copyBtn.getAttribute( 'data-login-url' ) || '';
				if ( navigator.clipboard ) {
					navigator.clipboard.writeText( url );
				}
			} );
		}
	} );
} )();
