/**
 * Section Builder (FR-03).
 *
 * Reordering, enabling, duplicating and deleting all happen against the
 * rows on screen; nothing is written until Save. The hidden payload field
 * is kept in step with the table so the screen still submits correctly
 * when this script is unavailable.
 */
( function () {
	'use strict';

	var form = document.getElementById( 'mk-builder-form' );
	var tbody = document.getElementById( 'mk-builder-rows' );
	var payload = document.getElementById( 'mk-builder-payload' );

	if ( ! form || ! tbody || ! payload || ! window.mkBuilder ) {
		return;
	}

	var status = form.querySelector( '.mk-builder__status' );
	var dirty = false;

	var say = function ( message, isError ) {
		if ( ! status ) {
			return;
		}
		status.textContent = message;
		status.classList.toggle( 'mk-builder__status--error', !! isError );
	};

	/**
	 * Reads the table back into the payload field.
	 *
	 * The table is the single source of truth — deriving the payload from
	 * it means a drag, a toggle and a delete cannot disagree about what the
	 * layout is.
	 */
	var sync = function () {
		var rows = [];

		Array.prototype.forEach.call( tbody.querySelectorAll( '.mk-builder__row' ), function ( row ) {
			var toggle = row.querySelector( '.mk-builder__enabled' );
			rows.push( {
				id: row.getAttribute( 'data-id' ),
				type: row.getAttribute( 'data-type' ),
				enabled: toggle ? toggle.checked : true
			} );
		} );

		payload.value = JSON.stringify( rows );
		return rows;
	};

	var markDirty = function () {
		dirty = true;
		sync();
		say( window.mkBuilder.unsaved, false );
	};

	/**
	 * A fresh instance id. Mirrors the server's format; the server
	 * regenerates anything it does not like, so a collision here is
	 * corrected rather than trusted.
	 *
	 * @param {string} type Section type.
	 * @return {string} New id.
	 */
	var newId = function ( type ) {
		return type + '-' + Math.random().toString( 36 ).slice( 2, 8 );
	};

	var rowTemplate = function ( type, label, id, isCopy ) {
		var tr = document.createElement( 'tr' );
		tr.className = 'mk-builder__row';
		tr.setAttribute( 'data-id', id );
		tr.setAttribute( 'data-type', type );
		tr.innerHTML =
			'<td class="mk-builder__handle" aria-hidden="true">⋮⋮</td>' +
			'<td><strong></strong> <span class="mk-sections__badge"></span>' +
			'<div class="mk-builder__meta"><code></code></div></td>' +
			'<td></td>' +
			'<td><label><input type="checkbox" class="mk-builder__enabled" /></label></td>' +
			'<td class="mk-builder__actions">' +
			'<button type="button" class="button-link mk-builder__duplicate"></button> ' +
			'<button type="button" class="button-link delete mk-builder__delete"></button></td>';

		tr.querySelector( 'strong' ).textContent = label;
		tr.querySelector( '.mk-sections__badge' ).textContent = isCopy ? window.mkBuilder.copyBadge : window.mkBuilder.newBadge;
		tr.querySelector( 'code' ).textContent = id;
		tr.querySelector( '.mk-builder__duplicate' ).textContent = window.mkBuilder.duplicate;
		tr.querySelector( '.mk-builder__delete' ).textContent = window.mkBuilder.remove;

		// New and duplicated sections start switched off, so nothing
		// appears on the live site by accident (FR-03.3).
		tr.querySelector( '.mk-builder__enabled' ).checked = false;

		return tr;
	};

	tbody.addEventListener( 'change', function ( event ) {
		if ( event.target.classList.contains( 'mk-builder__enabled' ) ) {
			markDirty();
		}
	} );

	tbody.addEventListener( 'click', function ( event ) {
		var row = event.target.closest( '.mk-builder__row' );
		if ( ! row ) {
			return;
		}

		if ( event.target.classList.contains( 'mk-builder__duplicate' ) ) {
			var type = row.getAttribute( 'data-type' );
			var label = row.querySelector( 'strong' ).textContent;
			var copy = rowTemplate( type, label, newId( type ), true );
			row.parentNode.insertBefore( copy, row.nextSibling );
			markDirty();
			return;
		}

		if ( event.target.classList.contains( 'mk-builder__delete' ) ) {
			// Named in the prompt, so it is clear which section is going.
			var name = row.querySelector( 'strong' ).textContent;
			if ( ! window.confirm( window.mkBuilder.confirmDelete.replace( '%s', name ) ) ) {
				return;
			}
			row.parentNode.removeChild( row );
			markDirty();
		}
	} );

	var addButton = document.getElementById( 'mk-builder-add' );
	var addSelect = document.getElementById( 'mk-builder-add-type' );

	if ( addButton && addSelect ) {
		addButton.addEventListener( 'click', function () {
			var type = addSelect.value;
			var label = addSelect.options[ addSelect.selectedIndex ].textContent;
			tbody.appendChild( rowTemplate( type, label, newId( type ), false ) );
			markDirty();
		} );
	}

	// Drag and drop, using the sortable WordPress already ships rather
	// than hand-rolling pointer maths.
	if ( window.jQuery && window.jQuery.fn && window.jQuery.fn.sortable ) {
		window.jQuery( tbody ).sortable( {
			handle: '.mk-builder__handle',
			axis: 'y',
			cursor: 'move',
			update: markDirty
		} );
	}

	form.addEventListener( 'submit', function ( event ) {
		var rows = sync();

		if ( ! window.fetch ) {
			// No fetch: let the form post normally with the payload field.
			return;
		}

		event.preventDefault();

		var body = new window.FormData();
		body.append( 'action', 'mk_save_section_layout' );
		body.append( 'nonce', window.mkBuilder.nonce );
		rows.forEach( function ( row, index ) {
			body.append( 'layout[' + index + '][id]', row.id );
			body.append( 'layout[' + index + '][type]', row.type );
			body.append( 'layout[' + index + '][enabled]', row.enabled ? '1' : '' );
		} );

		say( window.mkBuilder.saving, false );

		window.fetch( window.mkBuilder.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( response ) {
				return response.json().then( function ( json ) {
					return { ok: response.ok && json.success, json: json };
				} );
			} )
			.then( function ( result ) {
				if ( result.ok ) {
					dirty = false;
					say( result.json.data.message, false );
					return;
				}

				// A failed save must not clear the table: the admin's edits
				// are still on screen and still unsaved (FR-03.8).
				say( ( result.json.data && result.json.data.message ) || window.mkBuilder.failed, true );
			} )
			.catch( function () {
				say( window.mkBuilder.failed, true );
			} );
	} );

	window.addEventListener( 'beforeunload', function ( event ) {
		if ( ! dirty ) {
			return undefined;
		}
		event.preventDefault();
		event.returnValue = '';
		return '';
	} );

	sync();
} )();
