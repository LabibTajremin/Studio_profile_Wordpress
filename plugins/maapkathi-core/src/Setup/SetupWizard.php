<?php
/**
 * First-run setup wizard: turns the WordPress installer's admin account
 * into the site's first Maapkathi admin.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Setup;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Users\EmailVerification;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public site is reachable the whole time — this only ever gates
 * wp-admin. Whoever WordPress's own installer made an administrator sees a
 * single "Maapkathi Setup" screen the first time they open wp-admin, and
 * nothing else, until they set the studio's real admin username, email,
 * password, and full name here. No wp-config.php editing required.
 */
final class SetupWizard {

	public const OPTION_COMPLETE = 'mk_setup_complete';

	private const PAGE_SLUG = 'mk-setup';

	/**
	 * Registers every hook this class needs.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
	}

	/**
	 * Registers the setup screen as a hidden page — reachable by URL, but
	 * not shown in any menu, matching how WordPress itself hides one-time
	 * screens like the media-upload popup.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page( null, __( 'Maapkathi Setup', 'maapkathi' ), __( 'Maapkathi Setup', 'maapkathi' ), 'manage_options', self::PAGE_SLUG, array( $this, 'render' ) );
	}

	/**
	 * Sends any administrator to the setup screen until it has been
	 * completed, unless they are already on it (or WordPress is mid AJAX
	 * request, where a redirect would just break the call).
	 *
	 * @return void
	 */
	public function maybe_redirect(): void {
		if ( self::is_complete() || wp_doing_ajax() || ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page-identity check, not a state change.
		if ( self::PAGE_SLUG === $current_page ) {
			return;
		}

		global $pagenow;
		if ( 'admin-ajax.php' === $pagenow || 'admin-post.php' === $pagenow ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Whether the first-run setup has already been completed.
	 *
	 * @return bool
	 */
	public static function is_complete(): bool {
		return (bool) get_option( self::OPTION_COMPLETE );
	}

	/**
	 * Handles the form submission, then renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$errors = array();

		if ( isset( $_POST['mk_setup_nonce'] ) && check_admin_referer( 'mk_run_setup', 'mk_setup_nonce' ) ) {
			$errors = $this->save();
			if ( empty( $errors ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=maapkathi&mk_setup=1' ) );
				exit;
			}
		}

		$user = wp_get_current_user();

		require MK_PLUGIN_DIR . 'src/Setup/view.php';
	}

	/**
	 * Validates and applies the posted setup form.
	 *
	 * Called only from render(), which has already verified the
	 * mk_run_setup nonce before invoking this method.
	 *
	 * @return string[] Human-readable validation errors; empty means success.
	 */
	private function save(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce already verified in render() before this method is called.
		$login = sanitize_user( wp_unslash( $_POST['mk_username'] ?? '' ), true );
		$email = sanitize_email( wp_unslash( $_POST['mk_email'] ?? '' ) );
		$name  = sanitize_text_field( wp_unslash( $_POST['mk_full_name'] ?? '' ) );
		$pass  = (string) wp_unslash( $_POST['mk_password'] ?? '' );
		$pass2 = (string) wp_unslash( $_POST['mk_password_confirm'] ?? '' );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$current = wp_get_current_user();
		$errors  = array();

		if ( '' === $login ) {
			$errors[] = __( 'Username is required.', 'maapkathi' );
		} elseif ( username_exists( $login ) && (int) username_exists( $login ) !== $current->ID ) {
			$errors[] = __( 'That username is already taken.', 'maapkathi' );
		}

		if ( ! is_email( $email ) ) {
			$errors[] = __( 'A valid email address is required.', 'maapkathi' );
		} elseif ( email_exists( $email ) && (int) email_exists( $email ) !== $current->ID ) {
			$errors[] = __( 'That email address is already in use.', 'maapkathi' );
		}

		if ( '' === $name ) {
			$errors[] = __( 'Full name is required.', 'maapkathi' );
		}

		if ( $pass !== $pass2 ) {
			$errors[] = __( 'Passwords do not match.', 'maapkathi' );
		} elseif ( ! $this->is_strong_password( $pass ) ) {
			$errors[] = __( 'Password must be at least 16 characters and include upper case, lower case, a digit, and a symbol.', 'maapkathi' );
		}

		if ( ! empty( $errors ) ) {
			return $errors;
		}

		if ( $login !== $current->user_login ) {
			global $wpdb;
			$wpdb->update( $wpdb->users, array( 'user_login' => $login ), array( 'ID' => $current->ID ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- wp_update_user() cannot change user_login; this is WordPress's own documented workaround, followed by clean_user_cache().
			clean_user_cache( $current->ID );
		}

		wp_update_user(
			array(
				'ID'           => $current->ID,
				'user_email'   => $email,
				'display_name' => $name,
				'nickname'     => $name,
			)
		);

		wp_set_password( $pass, $current->ID );
		wp_set_auth_cookie( $current->ID, true );

		$user = new \WP_User( $current->ID );
		$user->add_role( Roles::ADMIN_ROLE );

		update_user_meta( $current->ID, 'mk_is_active', 1 );
		delete_user_meta( $current->ID, 'mk_must_change_password' );
		EmailVerification::mark_verified( $current->ID );

		update_option( self::OPTION_COMPLETE, true );

		return array();
	}

	/**
	 * Checks a password against the same 16+/mixed-case/digit/symbol policy
	 * documented for MK_ADMIN_PASSWORD in deploy/deploy.env.example.
	 *
	 * @param string $password Candidate password.
	 * @return bool
	 */
	private function is_strong_password( string $password ): bool {
		return strlen( $password ) >= 16
			&& preg_match( '/[a-z]/', $password )
			&& preg_match( '/[A-Z]/', $password )
			&& preg_match( '/[0-9]/', $password )
			&& preg_match( '/[^a-zA-Z0-9]/', $password );
	}
}
