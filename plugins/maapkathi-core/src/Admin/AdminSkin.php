<?php
/**
 * De-clutters and re-skins wp-admin for non-technical users.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\ThemeVarsBuilder;
use Maapkathi\Core\Setup\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Re-skins wp-admin with the site's own brand colours (the exact same CSS
 * custom properties the public site uses) so it feels like part of the
 * product rather than a bolted-on WordPress dashboard, and sends users to
 * the Maapkathi dashboard instead of WordPress's widget screen.
 *
 * This deliberately does NOT hide any of WordPress's own menu items. An
 * earlier version removed Posts, Comments, Tools, Plugins, Themes, the core
 * Settings group and the core Users list on the theory that they were noise
 * for a non-technical client. That was the wrong call: it takes the whole
 * of WordPress away from the site owner, who does need those screens. The
 * Maapkathi menu sits alongside the standard menu rather than replacing it.
 */
final class AdminSkin {

	/**
	 * Registers every hook this class needs.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'maybe_redirect_root' ) );
		add_action( 'admin_head', array( $this, 'inject_styles' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_skin_assets' ) );
	}

	/**
	 * Sends a Maapkathi user straight to the Maapkathi dashboard instead of
	 * WordPress's own widget-cluttered Dashboard screen.
	 *
	 * @return void
	 */
	public function maybe_redirect_root(): void {
		global $pagenow;

		if ( 'index.php' !== $pagenow || ! empty( $_GET ) || wp_doing_ajax() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page-identity check, not a state change.
			return;
		}

		if ( ! SetupWizard::is_complete() || ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=maapkathi' ) );
		exit;
	}

	/**
	 * Inlines the site's own brand-colour CSS custom properties plus the
	 * wp-admin layout overrides that turn the native menu into a rounded,
	 * accent-coloured sidebar card matching the public site.
	 *
	 * @return void
	 */
	public function inject_styles(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			return;
		}

		echo '<style id="mk-admin-skin-vars">' . ThemeVarsBuilder::build() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated CSS, values validated against the registry.
		?>
		<style id="mk-admin-skin">
			#adminmenuback, #adminmenuwrap, #adminmenu, #adminmenu .wp-submenu { background: transparent; }
			#adminmenuwrap {
				padding: 12px;
			}
			#adminmenu {
				background: var( --accent, #1d2327 );
				border-radius: var( --radius, 12px );
				overflow: hidden;
			}
			#adminmenu a { color: var( --accent-foreground, #f0f0f1 ); }
			#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
			#adminmenu li.current a.menu-top,
			#adminmenu .wp-submenu .wp-submenu-head,
			#adminmenu a:hover {
				background: color-mix( in srgb, var( --accent-foreground, #fff ) 15%, transparent );
				color: var( --accent-foreground, #fff );
			}
			#adminmenu .wp-submenu { background: color-mix( in srgb, var( --accent, #1d2327 ) 92%, black ); }
			#wpbody-content {
				background: var( --background, #f0f0f1 );
				border-radius: var( --radius, 12px );
				padding: 1px 20px 20px;
			}
			#wpcontent { padding-left: 0; }
			.mk-admin-skin-footer {
				margin-top: auto;
				padding: 12px 10px;
				border-top: 1px solid color-mix( in srgb, var( --accent-foreground, #fff ) 15%, transparent );
				display: flex;
				align-items: center;
				gap: 8px;
				color: var( --accent-foreground, #f0f0f1 );
				font-size: 12px;
			}
			.mk-admin-skin-footer img { border-radius: 50%; }
			.mk-admin-skin-view-site {
				display: block;
				margin: 10px 10px 4px;
				padding: 8px 10px;
				border: 1px solid color-mix( in srgb, var( --accent-foreground, #fff ) 30%, transparent );
				border-radius: var( --radius, 8px );
				color: var( --accent-foreground, #f0f0f1 ) !important;
				text-decoration: none;
				text-align: center;
				font-size: 12px;
			}
			#adminmenu { display: flex; flex-direction: column; }
			#adminmenu > #menu-highlight { display: none; }

			/* Smoother, less "raw WordPress" content chrome. */
			.mk-admin .wrap,
			#wpbody-content > .wrap {
				background: var( --background, #fff );
				border-radius: var( --radius, 12px );
				padding: clamp( 1rem, 2vw, 2rem );
				margin-top: 1rem;
			}
			.mk-admin h1.wp-heading-inline,
			#wpbody-content > .wrap > h1 {
				font-family: var( --font-headings, inherit );
				color: var( --heading-color, var( --foreground, inherit ) );
			}
			.mk-admin .form-table,
			#wpbody-content .form-table {
				background: color-mix( in srgb, var( --foreground, #000 ) 3%, transparent );
				border-radius: var( --radius, 8px );
				overflow: hidden;
				border-collapse: separate;
			}
			.mk-admin .form-table > tbody > tr,
			#wpbody-content .form-table > tbody > tr {
				border-bottom: 1px solid color-mix( in srgb, var( --foreground, #000 ) 8%, transparent );
			}
			.mk-admin .form-table > tbody > tr:last-child,
			#wpbody-content .form-table > tbody > tr:last-child {
				border-bottom: 0;
			}
			.mk-admin input[type="text"],
			.mk-admin input[type="email"],
			.mk-admin input[type="url"],
			.mk-admin input[type="password"],
			.mk-admin input[type="number"],
			.mk-admin select,
			.mk-admin textarea {
				border-radius: calc( var( --radius, 8px ) / 1.5 );
				border-color: color-mix( in srgb, var( --foreground, #000 ) 20%, transparent );
			}
			.mk-admin input[type="text"]:focus,
			.mk-admin input[type="email"]:focus,
			.mk-admin input[type="url"]:focus,
			.mk-admin input[type="password"]:focus,
			.mk-admin input[type="number"]:focus,
			.mk-admin select:focus,
			.mk-admin textarea:focus {
				border-color: var( --accent, #2271b1 );
				box-shadow: 0 0 0 1px var( --accent, #2271b1 );
			}
			.mk-admin .button,
			.mk-admin .button-primary,
			#wpbody-content .button,
			#wpbody-content .button-primary {
				/* Fixed, not var(--radius): admin chrome shouldn't go full-pill just because someone picked "Pill" for the public site's appearance — matches the reference admin's own fixed rounded-lg nav buttons (AdminNav.tsx). */
				border-radius: 8px;
				transition: transform 0.15s ease, box-shadow 0.15s ease;
			}
			.mk-admin .button-primary,
			#wpbody-content .button-primary {
				background: var( --accent, #2271b1 );
				border-color: var( --accent, #2271b1 );
				color: var( --accent-foreground, #fff );
			}
			.mk-admin .button-primary:hover,
			#wpbody-content .button-primary:hover {
				box-shadow: 0 2px 8px color-mix( in srgb, var( --accent, #2271b1 ) 45%, transparent );
				transform: translateY( -1px );
			}
		</style>
		<?php
	}

	/**
	 * Enqueues the small script that injects the "View public site" link and
	 * the avatar/name/role/sign-out block at the bottom of the admin menu.
	 *
	 * @return void
	 */
	public function enqueue_skin_assets(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			return;
		}

		wp_enqueue_script( 'maapkathi-admin-skin', MK_PLUGIN_URL . 'assets/admin/admin-skin.js', array(), MK_DB_VERSION, true );

		$user = wp_get_current_user();
		wp_localize_script(
			'maapkathi-admin-skin',
			'mkAdminSkin',
			array(
				'siteUrl'   => home_url( '/' ),
				'viewSite'  => __( 'View public site ↗', 'maapkathi' ),
				'logoutUrl' => wp_logout_url(),
				'signOut'   => __( 'Sign out', 'maapkathi' ),
				'avatarUrl' => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
				'name'      => $user->display_name,
				'role'      => ucfirst( str_replace( 'mk_', '', (string) ( $user->roles[0] ?? '' ) ) ),
			)
		);
	}
}
