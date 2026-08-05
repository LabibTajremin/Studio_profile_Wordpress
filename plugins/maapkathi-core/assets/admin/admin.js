document.addEventListener( 'DOMContentLoaded', function () {
	var preset = document.querySelector( 'input[name="mk_theme_settings[motion_preset]"]:checked' );
	var staggerInput = document.querySelector( 'input[name="mk_theme_settings[stagger_ms]"]' );

	if ( ! staggerInput ) {
		return;
	}

	document.querySelectorAll( 'input[name="mk_theme_settings[motion_preset]"]' ).forEach( function ( radio ) {
		radio.addEventListener( 'change', function () {
			staggerInput.disabled = radio.value !== 'custom';
		} );
	} );
} );
