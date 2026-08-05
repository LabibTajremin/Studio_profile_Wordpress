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
		var toggle = document.querySelector( '.mk-theme-toggle' );
		if ( ! toggle ) {
			return;
		}

		toggle.addEventListener( 'click', function () {
			var current = root.getAttribute( 'data-theme' ) === 'dark' ? 'dark' : 'light';
			var next = current === 'dark' ? 'light' : 'dark';
			applyTheme( next );
			persist( next );
		} );

		var menuToggle = document.querySelector( '.mk-menu-toggle' );
		var mobileMenu = document.getElementById( 'mk-mobile-menu' );
		if ( menuToggle && mobileMenu ) {
			menuToggle.addEventListener( 'click', function () {
				var isOpen = ! mobileMenu.hidden;
				mobileMenu.hidden = isOpen;
				mobileMenu.classList.toggle( 'is-open', ! isOpen );
				menuToggle.setAttribute( 'aria-expanded', String( ! isOpen ) );
			} );
			mobileMenu.querySelectorAll( 'a' ).forEach( function ( link ) {
				link.addEventListener( 'click', function () {
					mobileMenu.hidden = true;
					mobileMenu.classList.remove( 'is-open' );
					menuToggle.setAttribute( 'aria-expanded', 'false' );
				} );
			} );
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
