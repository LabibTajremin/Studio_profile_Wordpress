<?php
/**
 * Users screen markup.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by UsersScreen::render().
 *
 * @var \WP_User[] $users
 * @var string $notice
 */
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Users', 'maapkathi' ); ?></h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'The people who can sign in. Admins can change anything; Editors can edit content but their changes go to Approvals first.', 'maapkathi' ); ?></p>
	<?php
	if ( $notice ) :
		?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<h2><?php esc_html_e( 'Invite a user', 'maapkathi' ); ?></h2>
	<form method="post" class="mk-card" style="max-width:480px">
		<?php wp_nonce_field( 'mk_invite_user', 'mk_invite_nonce' ); ?>
		<p><label><?php esc_html_e( 'Name', 'maapkathi' ); ?> <input type="text" name="display_name" class="regular-text" /></label></p>
		<p><label><?php esc_html_e( 'Email', 'maapkathi' ); ?> <input type="email" name="email" required class="regular-text" /></label></p>
		<p>
			<label><?php esc_html_e( 'Role', 'maapkathi' ); ?>
				<select name="role">
					<option value="<?php echo esc_attr( Roles::EDITOR_ROLE ); ?>"><?php esc_html_e( 'Editor', 'maapkathi' ); ?></option>
					<option value="<?php echo esc_attr( Roles::ADMIN_ROLE ); ?>"><?php esc_html_e( 'Admin', 'maapkathi' ); ?></option>
				</select>
			</label>
		</p>
		<?php submit_button( __( 'Invite', 'maapkathi' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'Existing users', 'maapkathi' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th><?php esc_html_e( 'Name', 'maapkathi' ); ?></th><th><?php esc_html_e( 'Email', 'maapkathi' ); ?></th><th><?php esc_html_e( 'Role', 'maapkathi' ); ?></th><th><?php esc_html_e( 'Active', 'maapkathi' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $users as $user ) : ?>
			<tr>
				<td><?php echo esc_html( $user->display_name ); ?></td>
				<td><?php echo esc_html( $user->user_email ); ?></td>
				<td>
					<form method="post" style="display:inline-flex;gap:0.5rem">
						<?php wp_nonce_field( 'mk_toggle_user', 'mk_toggle_nonce' ); ?>
						<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>" />
						<input type="hidden" name="is_active" value="<?php echo esc_attr( get_user_meta( $user->ID, 'mk_is_active', true ) ? '1' : '0' ); ?>" />
						<select name="role" onchange="this.form.submit()">
							<option value="<?php echo esc_attr( Roles::EDITOR_ROLE ); ?>" <?php selected( in_array( Roles::EDITOR_ROLE, $user->roles, true ) ); ?>><?php esc_html_e( 'Editor', 'maapkathi' ); ?></option>
							<option value="<?php echo esc_attr( Roles::ADMIN_ROLE ); ?>" <?php selected( in_array( Roles::ADMIN_ROLE, $user->roles, true ) ); ?>><?php esc_html_e( 'Admin', 'maapkathi' ); ?></option>
						</select>
					</form>
				</td>
				<td>
					<form method="post">
						<?php wp_nonce_field( 'mk_toggle_user', 'mk_toggle_nonce' ); ?>
						<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>" />
						<button type="submit" name="is_active" value="<?php echo esc_attr( get_user_meta( $user->ID, 'mk_is_active', true ) ? '0' : '1' ); ?>" class="button">
							<?php echo get_user_meta( $user->ID, 'mk_is_active', true ) ? esc_html__( 'Deactivate', 'maapkathi' ) : esc_html__( 'Activate', 'maapkathi' ); ?>
						</button>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
