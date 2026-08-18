/**
 * Footer newsletter form (FR-08.12).
 *
 * Posts to the plugin's REST route and reports the result inline, in place,
 * without a page reload. Validation runs on both sides: this is the
 * courtesy layer, the endpoint is the one that decides.
 */
( function () {
	'use strict';

	var form = document.querySelector( '[data-mk-subscribe]' );
	if ( ! form || ! window.mkSubscribe ) {
		return;
	}

	var status = form.querySelector( '.mk-subscribe__status' );
	var email = form.querySelector( 'input[type="email"]' );
	var trap = form.querySelector( 'input[name="website"]' );
	var button = form.querySelector( '.mk-subscribe__button' );
	var nonceField = form.querySelector( 'input[name="mk_subscribe_nonce"]' );

	var say = function ( message, isError ) {
		status.textContent = message;
		status.classList.toggle( 'mk-subscribe__status--error', !! isError );
	};

	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();

		var value = email.value.trim();
		if ( ! value || value.indexOf( '@' ) < 1 ) {
			say( window.mkSubscribe.invalid, true );
			email.focus();
			return;
		}

		button.disabled = true;
		say( window.mkSubscribe.sending, false );

		window.fetch( window.mkSubscribe.endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( {
				email: value,
				website: trap ? trap.value : '',
				nonce: nonceField ? nonceField.value : ''
			} )
		} )
			.then( function ( response ) {
				return response.json().then( function ( body ) {
					return { ok: response.ok, body: body };
				} );
			} )
			.then( function ( result ) {
				var message = ( result.body && result.body.message ) || window.mkSubscribe.failed;
				say( message, ! result.ok );
				if ( result.ok ) {
					form.reset();
				}
			} )
			.catch( function () {
				say( window.mkSubscribe.failed, true );
			} )
			.finally( function () {
				button.disabled = false;
			} );
	} );
} )();
