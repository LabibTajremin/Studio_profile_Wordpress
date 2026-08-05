<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Users screen (§3.3 #13, §7): invite editor, toggle active, change role.
 */
final class UsersScreen {

	public function render(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_USERS ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice = '';

		if ( isset( $_POST['mk_invite_nonce'] ) && check_admin_referer( 'mk_invite_user', 'mk_invite_nonce' ) ) {
			$notice = $this->handle_invite();
		}

		if ( isset( $_POST['mk_toggle_nonce'] ) && check_admin_referer( 'mk_toggle_user', 'mk_toggle_nonce' ) ) {
			$this->handle_toggle();
			$notice = __( 'Updated.', 'maapkathi' );
		}

		$users = get_users( array( 'role__in' => array( Roles::ADMIN_ROLE, Roles::EDITOR_ROLE, 'administrator' ) ) );

		require MK_PLUGIN_DIR . 'src/Admin/views/users.php';
	}

	private function handle_invite(): string {
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name  = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
		$role  = sanitize_key( $_POST['role'] ?? Roles::EDITOR_ROLE );

		if ( ! is_email( $email ) ) {
			return __( 'A valid email is required.', 'maapkathi' );
		}
		if ( email_exists( $email ) ) {
			return __( 'A user with that email already exists.', 'maapkathi' );
		}
		if ( ! in_array( $role, array( Roles::ADMIN_ROLE, Roles::EDITOR_ROLE ), true ) ) {
			$role = Roles::EDITOR_ROLE;
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => sanitize_user( strstr( $email, '@', true ) ?: $email ),
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 20 ),
				'display_name' => $name ?: $email,
				'role'         => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id->get_error_message();
		}

		update_user_meta( $user_id, 'mk_is_active', 1 );
		update_user_meta( $user_id, 'mk_must_change_password', 1 );

		wp_new_user_notification( $user_id, null, 'user' );

		return __( 'User invited.', 'maapkathi' );
	}

	private function handle_toggle(): void {
		$user_id = absint( $_POST['user_id'] ?? 0 );
		if ( ! $user_id ) {
			return;
		}

		if ( isset( $_POST['is_active'] ) ) {
			update_user_meta( $user_id, 'mk_is_active', absint( $_POST['is_active'] ) );
		}

		if ( isset( $_POST['role'] ) ) {
			$role = sanitize_key( $_POST['role'] );
			if ( in_array( $role, array( Roles::ADMIN_ROLE, Roles::EDITOR_ROLE ), true ) ) {
				$user = new \WP_User( $user_id );
				$user->set_role( $role );
			}
		}
	}
}
