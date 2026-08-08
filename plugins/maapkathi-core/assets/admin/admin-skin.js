/**
 * Injects the "View public site" link and the avatar/name/role/sign-out
 * block into the top and bottom of the re-skinned admin menu.
 */
( function () {
	'use strict';

	if ( typeof mkAdminSkin === 'undefined' ) {
		return;
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var menu = document.getElementById( 'adminmenu' );
		if ( ! menu ) {
			return;
		}

		var viewSite = document.createElement( 'a' );
		viewSite.className = 'mk-admin-skin-view-site';
		viewSite.href = mkAdminSkin.siteUrl;
		viewSite.target = '_blank';
		viewSite.rel = 'noopener noreferrer';
		viewSite.textContent = mkAdminSkin.viewSite;
		menu.insertBefore( viewSite, menu.firstChild );

		var footer = document.createElement( 'div' );
		footer.className = 'mk-admin-skin-footer';

		var avatar = document.createElement( 'img' );
		avatar.src = mkAdminSkin.avatarUrl;
		avatar.width = 32;
		avatar.height = 32;
		avatar.alt = '';
		footer.appendChild( avatar );

		var details = document.createElement( 'div' );
		var name = document.createElement( 'div' );
		name.textContent = mkAdminSkin.name;
		var role = document.createElement( 'div' );
		role.style.opacity = '0.7';
		role.textContent = mkAdminSkin.role;
		details.appendChild( name );
		details.appendChild( role );
		footer.appendChild( details );

		var signOut = document.createElement( 'a' );
		signOut.href = mkAdminSkin.logoutUrl;
		signOut.textContent = mkAdminSkin.signOut;
		signOut.style.marginLeft = 'auto';
		signOut.style.color = 'inherit';
		footer.appendChild( signOut );

		menu.appendChild( footer );
	} );
} )();
