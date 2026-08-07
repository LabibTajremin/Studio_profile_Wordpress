<?php
/**
 * Custom roles, capabilities, admin bootstrap and login rate limiting.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The two custom roles (§7) and their capabilities. Every admin screen and
 * every write handler must call current_user_can() independently — never
 * rely on a hidden menu item as access control.
 */
final class Roles {

	public const ADMIN_ROLE  = 'mk_admin';
	public const EDITOR_ROLE = 'mk_editor';

	public const CAP_MANAGE_SETTINGS   = 'mk_manage_settings';
	public const CAP_MANAGE_APPEARANCE = 'mk_manage_appearance';
	public const CAP_MANAGE_USERS      = 'mk_manage_users';
	public const CAP_APPROVE_REVISIONS = 'mk_approve_revisions';
	public const CAP_PUBLISH_CONTENT   = 'mk_publish_content';
	public const CAP_EDIT_CONTENT      = 'mk_edit_content';

	/**
	 * Registers every hook this class needs: login gating, rate limiting
	 * and the password-change nag.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_login', array( $this, 'block_inactive_users' ), 10, 2 );
		add_filter( 'authenticate', array( $this, 'block_if_rate_limited' ), 30, 1 );
		add_action( 'wp_login_failed', array( $this, 'record_failed_login' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'password_change_notice' ) );
		add_action( 'after_password_reset', array( $this, 'clear_password_notice' ), 10, 1 );
		add_action( 'profile_update', array( $this, 'maybe_clear_password_notice' ), 10, 2 );
	}

	/**
	 * (Re)creates both custom roles with their capability sets, and grants
	 * every Maapkathi capability to core Administrators too.
	 *
	 * @return void
	 */
	public static function register_roles(): void {
		// Every CPT's per-post permissions are enforced through our own
		// mk_* capabilities via map_meta_cap (§PostTypes::args_for). But
		// WordPress's own admin bootstrap (wp-admin/includes/menu.php ->
		// user_can_access_admin_page(), verified live against a real
		// WordPress install) independently gates whether an "Add New" /
		// list screen is reachable at all on the LITERAL 'edit_posts'
		// capability, regardless of any custom capability_type mapping.
		// Without it here, both roles get a hard 403 on every custom post
		// type's admin screens — confirmed by direct testing, not a
		// theoretical concern.
		$admin_caps = array(
			'read'                      => true,
			'edit_posts'                => true,
			self::CAP_MANAGE_SETTINGS   => true,
			self::CAP_MANAGE_APPEARANCE => true,
			self::CAP_MANAGE_USERS      => true,
			self::CAP_APPROVE_REVISIONS => true,
			self::CAP_PUBLISH_CONTENT   => true,
			self::CAP_EDIT_CONTENT      => true,
			'upload_files'              => true,
		);

		$editor_caps = array(
			'read'                 => true,
			'edit_posts'           => true,
			self::CAP_EDIT_CONTENT => true,
			'upload_files'         => true,
		);

		remove_role( self::ADMIN_ROLE );
		remove_role( self::EDITOR_ROLE );

		add_role( self::ADMIN_ROLE, __( 'Maapkathi Admin', 'maapkathi' ), $admin_caps );
		add_role( self::EDITOR_ROLE, __( 'Maapkathi Editor', 'maapkathi' ), $editor_caps );

		// WordPress core administrators get every Maapkathi capability too,
		// so an existing WP install remains usable without role juggling.
		$wp_admin = get_role( 'administrator' );
		if ( $wp_admin ) {
			foreach ( array_keys( $admin_caps ) as $cap ) {
				$wp_admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Creates the first Maapkathi admin from wp-config.php constants, so a
	 * site can be deployed with known credentials without anyone clicking
	 * through an installer.
	 *
	 * Runs on activation only, and never touches an existing account:
	 * if either the username or the email is already taken it bails, which
	 * makes reactivating the plugin safe and stops a stale constant from
	 * silently resetting a live admin's password.
	 */
	public static function bootstrap_admin_user(): void {
		if ( ! defined( 'MK_ADMIN_EMAIL' ) || ! defined( 'MK_ADMIN_PASSWORD' ) ) {
			return;
		}

		$email    = (string) MK_ADMIN_EMAIL;
		$password = (string) MK_ADMIN_PASSWORD;

		if ( ! is_email( $email ) || '' === $password ) {
			return;
		}

		// Username: explicit constant wins; otherwise fall back to the part
		// of the email before the @, which is what most people expect.
		$login = defined( 'MK_ADMIN_USERNAME' ) && '' !== (string) MK_ADMIN_USERNAME
			? (string) MK_ADMIN_USERNAME
			: ( str_contains( $email, '@' ) ? (string) strstr( $email, '@', true ) : 'maapkathi-admin' );

		$login = sanitize_user( $login, true );
		if ( '' === $login ) {
			$login = 'maapkathi-admin';
		}

		if ( email_exists( $email ) || username_exists( $login ) ) {
			return;
		}

		$name    = defined( 'MK_ADMIN_NAME' ) ? (string) MK_ADMIN_NAME : 'Maapkathi Admin';
		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $name,
				'nickname'     => $name,
				'role'         => self::ADMIN_ROLE,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return;
		}

		update_user_meta( $user_id, 'mk_is_active', 1 );

		// Only nag for a password change when the shipped sentinel is still
		// in place — someone who set a real password in wp-config.php has
		// already made a deliberate choice and shouldn't be hassled.
		$sentinels = array( 'change-this-immediately', 'change-me-immediately', 'ChangeMe123!' );
		if ( in_array( $password, $sentinels, true ) ) {
			update_user_meta( $user_id, 'mk_must_change_password', 1 );
		}
	}

	/**
	 * Persistent reminder while a bootstrap account is still on the
	 * shipped sentinel password (§7).
	 */
	public function password_change_notice(): void {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! get_user_meta( $user_id, 'mk_must_change_password', true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
			esc_html__( 'Security:', 'maapkathi' ),
			esc_html__( 'This account is still using the default setup password.', 'maapkathi' ),
			esc_url( admin_url( 'profile.php' ) ),
			esc_html__( 'Change it now', 'maapkathi' )
		);
	}

	/**
	 * Clears the nag once the password has actually been changed.
	 *
	 * @param int|\WP_User $user_id User ID, or the WP_User whose password reset just fired.
	 * @return void
	 */
	public function clear_password_notice( $user_id ): void {
		$id = $user_id instanceof \WP_User ? $user_id->ID : (int) $user_id;
		delete_user_meta( $id, 'mk_must_change_password' );
	}

	/**
	 * Profile_update fires on any profile save, so only clear the nag when
	 * the password hash actually changed.
	 *
	 * @param int      $user_id       Updated user.
	 * @param \WP_User $old_user_data User before the update.
	 * @return void
	 */
	public function maybe_clear_password_notice( int $user_id, $old_user_data ): void {
		if ( ! $old_user_data instanceof \WP_User ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( $user && $user->user_pass !== $old_user_data->user_pass ) {
			delete_user_meta( $user_id, 'mk_must_change_password' );
		}
	}

	/**
	 * Logs a user straight back out if their `mk_is_active` user meta has
	 * been explicitly set to a falsy value.
	 *
	 * @param string   $user_login Username used to log in (unused, required by the `wp_login` hook signature).
	 * @param \WP_User $user       The user who just logged in.
	 * @return void
	 */
	public function block_inactive_users( string $user_login, \WP_User $user ): void {
		$is_active = get_user_meta( $user->ID, 'mk_is_active', true );
		if ( '' !== $is_active && ! $is_active ) {
			wp_logout();
			wp_die( esc_html__( 'This account has been deactivated. Contact the site administrator.', 'maapkathi' ) );
		}
	}

	/**
	 * Login rate limiting: max 5 attempts per IP per 15 minutes, then
	 * exponential backoff via a transient counter.
	 *
	 * Deliberately split across two hooks rather than counting failures
	 * inside the 'authenticate' filter itself: WordPress core can invoke
	 * that filter chain more than once within a single login request (its
	 * own default callbacks re-enter it), which — verified live — silently
	 * double-counted even a successful login as a failed attempt and could
	 * lock a legitimate user out. 'wp_login_failed' is WordPress's own
	 * canonical hook for "a login attempt genuinely failed," fired exactly
	 * once per failed wp_signon() call, which is what this needs.
	 */
	public function block_if_rate_limited( $user ) {
		if ( $this->attempts_for_current_ip() >= 5 ) {
			return new \WP_Error( 'mk_rate_limited', __( 'Too many login attempts. Please try again later.', 'maapkathi' ) );
		}

		return $user;
	}

	/**
	 * Increments the failed-attempt counter for the current IP, with the
	 * transient TTL backing off further the more attempts accumulate.
	 *
	 * @param string $username Username attempted (unused, required by the `wp_login_failed` hook signature).
	 * @return void
	 */
	public function record_failed_login( string $username ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- required by the wp_login_failed hook signature.
		$attempts = $this->attempts_for_current_ip();
		set_transient( $this->rate_limit_key(), $attempts + 1, 15 * MINUTE_IN_SECONDS * ( 1 + $attempts ) );
	}

	/**
	 * Reads the current failed-login count for the requesting IP.
	 *
	 * @return int Number of recorded failed attempts within the current window.
	 */
	private function attempts_for_current_ip(): int {
		return (int) get_transient( $this->rate_limit_key() );
	}

	/**
	 * Builds the transient key used to track failed login attempts for the
	 * current request's IP address.
	 *
	 * @return string Transient key, unique per IP.
	 */
	private function rate_limit_key(): string {
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'mk_login_attempts_' . md5( $ip );
	}
}
