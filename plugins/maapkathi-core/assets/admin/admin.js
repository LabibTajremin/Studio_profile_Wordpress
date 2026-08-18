/* global wp, mkAdmin */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initStaggerGating();
		initMediaPickers();
		initHeroKindToggles();
	} );

	/**
	 * stagger_ms only has an effect when the preset is "custom"
	 * (resolveMotionVars ignores it otherwise), so the control is disabled
	 * and labelled rather than silently doing nothing — §11.2.
	 */
	function initStaggerGating() {
		var stagger = document.querySelector( 'input[name="mk_theme_settings[stagger_ms]"]' );
		if ( ! stagger ) {
			return;
		}

		var presets = document.querySelectorAll( 'input[name="mk_theme_settings[motion_preset]"]' );
		var help = document.querySelector( '.mk-stagger-help' );

		function sync() {
			var checked = document.querySelector( 'input[name="mk_theme_settings[motion_preset]"]:checked' );
			var isCustom = checked && 'custom' === checked.value;
			stagger.disabled = ! isCustom;
			if ( help ) {
				help.hidden = isCustom;
			}
		}

		presets.forEach( function ( radio ) {
			radio.addEventListener( 'change', sync );
		} );

		sync();
	}

	/**
	 * Media Library picker for any field marked data-mk-media. Stores the
	 * attachment ID in the hidden input and shows a live preview.
	 */
	function initMediaPickers() {
		if ( 'undefined' === typeof wp || ! wp.media ) {
			return;
		}

		document.querySelectorAll( '[data-mk-media]' ).forEach( function ( wrap ) {
			var input = wrap.querySelector( 'input[type="hidden"]' );
			var preview = wrap.querySelector( '.mk-media__preview' );
			var chooseBtn = wrap.querySelector( '.mk-media__choose' );
			var clearBtn = wrap.querySelector( '.mk-media__clear' );
			var frame;

			if ( ! input || ! chooseBtn ) {
				return;
			}

			chooseBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();

				if ( frame ) {
					frame.open();
					return;
				}

				frame = wp.media( {
					title: mkAdmin && mkAdmin.chooseImage ? mkAdmin.chooseImage : 'Choose image',
					button: { text: mkAdmin && mkAdmin.useImage ? mkAdmin.useImage : 'Use this image' },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					input.value = attachment.id;

					if ( preview ) {
						var src = attachment.sizes && attachment.sizes.medium
							? attachment.sizes.medium.url
							: attachment.url;
						preview.innerHTML = '';
						var img = document.createElement( 'img' );
						img.src = src;
						img.alt = '';
						preview.appendChild( img );
					}
					if ( clearBtn ) {
						clearBtn.hidden = false;
					}
				} );

				frame.open();
			} );

			if ( clearBtn ) {
				clearBtn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					input.value = '';
					if ( preview ) {
						preview.innerHTML = '';
					}
					clearBtn.hidden = true;
				} );
			}
		} );
	}

	/**
	 * Hero slides carry exactly one media kind, so only the relevant
	 * sub-fields are shown — picking a new kind can never leave two
	 * conflicting sources filled in (§6.3).
	 */
	function initHeroKindToggles() {
		var fieldsets = document.querySelectorAll( '[data-hero-slide]' );
		if ( ! fieldsets.length ) {
			return;
		}

		fieldsets.forEach( function ( fieldset ) {
			var radios = fieldset.querySelectorAll( 'input[type="radio"][data-media-kind]' );
			if ( ! radios.length ) {
				return;
			}

			function sync() {
				var checked = fieldset.querySelector( 'input[type="radio"][data-media-kind]:checked' );
				var kind = checked ? checked.value : 'image';

				fieldset.querySelectorAll( '[data-kind-field]' ).forEach( function ( row ) {
					var kinds = ( row.getAttribute( 'data-kind-field' ) || '' ).split( ' ' );
					row.hidden = kinds.indexOf( kind ) === -1;
				} );

				var note = fieldset.querySelector( '.mk-hero-length-note' );
				if ( note ) {
					note.hidden = ( 'image' === kind );
				}
			}

			radios.forEach( function ( radio ) {
				radio.addEventListener( 'change', sync );
			} );

			sync();
		} );

		initVideoDurationWarning();
	}

	/**
	 * Reads a chosen clip's duration client-side and warns — never blocks —
	 * when it runs past the carousel interval (§6.3 rule 1).
	 */
	function initVideoDurationWarning() {
		document.querySelectorAll( 'input[data-video-url-field]' ).forEach( function ( input ) {
			input.addEventListener( 'change', function () {
				var url = input.value.trim();
				var warn = input.parentNode.querySelector( '.mk-video-duration-warning' );

				if ( ! warn || ! url || ! /\.(mp4|webm)(\?|$)/i.test( url ) ) {
					if ( warn ) {
						warn.hidden = true;
					}
					return;
				}

				var probe = document.createElement( 'video' );
				probe.preload = 'metadata';
				probe.onloadedmetadata = function () {
					var slideSeconds = parseInt(
						document.querySelector( 'input[name="hero_slide_duration"]' ) ?
							document.querySelector( 'input[name="hero_slide_duration"]' ).value : '6',
						10
					) || 6;

					if ( probe.duration > 10 ) {
						warn.textContent =
							'This clip is ' + Math.round( probe.duration ) + 's but slides change every ' +
							slideSeconds + 's. Viewers will only see the first ' + slideSeconds +
							' seconds. Trim it, or turn on "Hold slide until video ends" below.';
						warn.hidden = false;
					} else {
						warn.hidden = true;
					}
				};
				probe.onerror = function () {
					warn.hidden = true;
				};
				probe.src = url;
			} );
		} );
	}
} )();

/**
 * Header colour controls (FR-01.3).
 *
 * The palette and hex inputs are never removed from the page — ticking
 * "follows accent colour" only disables them, so the admin's previous
 * custom choice is still there when they untick it again.
 */
( function () {
	var follow = document.getElementById( 'mk-header-follow-accent' );
	if ( ! follow ) {
		return;
	}

	var fieldset = document.querySelector( '.mk-header-colour' );
	var hint = document.querySelector( '.mk-header-colour__hint' );
	if ( ! fieldset ) {
		return;
	}

	var sync = function () {
		fieldset.disabled = follow.checked;
		if ( hint ) {
			hint.hidden = ! follow.checked;
		}
	};

	follow.addEventListener( 'change', sync );
	sync();

	// Live preview chip beside the hex field.
	var hex = fieldset.querySelector( 'input[name="mk_theme_settings[header_hex]"]' );
	var chip = fieldset.querySelector( '.mk-colour-chip' );
	if ( hex && chip ) {
		hex.addEventListener( 'input', function () {
			var value = hex.value.trim().replace( /^#/, '' );
			chip.style.background = /^([0-9a-f]{3}|[0-9a-f]{6})$/i.test( value ) ? '#' + value : 'transparent';
		} );
	}
} )();
