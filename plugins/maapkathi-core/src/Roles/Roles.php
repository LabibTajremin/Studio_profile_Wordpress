<?php
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

	public function register_hooks(): void {
		add_action( 'wp_login', array( $this, 'block_inactive_users' ), 10, 2 );
		add_filter( 'authenticate', array( $this, 'block_if_rate_limited' ), 30, 1 );
		add_action( 'wp_login_failed', array( $this, 'record_failed_login' ), 10, 1 );
	}

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
			'read'                        => true,
			'edit_posts'                  => true,
			self::CAP_MANAGE_SETTINGS     => true,
			self::CAP_MANAGE_APPEARANCE   => true,
			self::CAP_MANAGE_USERS        => true,
			self::CAP_APPROVE_REVISIONS   => true,
			self::CAP_PUBLISH_CONTENT     => true,
			self::CAP_EDIT_CONTENT        => true,
			'upload_files'                => true,
		);

		$editor_caps = array(
			'read'                    => true,
			'edit_posts'              => true,
			self::CAP_EDIT_CONTENT    => true,
			'upload_files'            => true,
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

	public static function bootstrap_admin_user(): void {
		if ( ! defined( 'MK_ADMIN_EMAIL' ) || ! defined( 'MK_ADMIN_PASSWORD' ) ) {
			return;
		}

		if ( email_exists( MK_ADMIN_EMAIL ) ) {
			return;
		}

		$name    = defined( 'MK_ADMIN_NAME' ) ? (string) MK_ADMIN_NAME : 'Maapkathi Admin';
		$user_id = wp_insert_user(
			array(
				'user_login'   => sanitize_user( str_contains( (string) MK_ADMIN_EMAIL, '@' ) ? strstr( (string) MK_ADMIN_EMAIL, '@', true ) : 'maapkathi-admin' ),
				'user_email'   => (string) MK_ADMIN_EMAIL,
				'user_pass'    => (string) MK_ADMIN_PASSWORD,
				'display_name' => $name,
				'role'         => self::ADMIN_ROLE,
			)
		);

		if ( is_int( $user_id ) ) {
			update_user_meta( $user_id, 'mk_must_change_password', 1 );
			update_user_meta( $user_id, 'mk_is_active', 1 );
		}
	}

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

	public function record_failed_login( string $username ): void {
		$attempts = $this->attempts_for_current_ip();
		set_transient( $this->rate_limit_key(), $attempts + 1, 15 * MINUTE_IN_SECONDS * ( 1 + $attempts ) );
	}

	private function attempts_for_current_ip(): int {
		return (int) get_transient( $this->rate_limit_key() );
	}

	private function rate_limit_key(): string {
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'mk_login_attempts_' . md5( $ip );
	}
}
