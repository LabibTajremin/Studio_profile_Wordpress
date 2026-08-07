<?php
/**
 * First-run setup wizard markup.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by SetupWizard::render().
 *
 * @var string[]  $errors
 * @var \WP_User $user
 */
?>
<div class="wrap mk-admin" style="max-width:640px">
	<h1><?php esc_html_e( 'Maapkathi Setup', 'maapkathi' ); ?></h1>
	<p><?php esc_html_e( 'One-time setup — this creates the studio\'s admin login. The public site is already live; this screen is only shown to administrators until setup is complete.', 'maapkathi' ); ?></p>

	<?php if ( ! empty( $errors ) ) : ?>
		<div class="notice notice-error">
			<ul>
				<?php foreach ( $errors as $setup_error ) : ?>
					<li><?php echo esc_html( $setup_error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_run_setup', 'mk_setup_nonce' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="mk_username"><?php esc_html_e( 'Username', 'maapkathi' ); ?></label></th>
				<td><input type="text" name="mk_username" id="mk_username" class="regular-text" value="<?php echo esc_attr( $user->user_login ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="mk_email"><?php esc_html_e( 'Email', 'maapkathi' ); ?></label></th>
				<td><input type="email" name="mk_email" id="mk_email" class="regular-text" value="<?php echo esc_attr( $user->user_email ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="mk_full_name"><?php esc_html_e( 'Full name', 'maapkathi' ); ?></label></th>
				<td><input type="text" name="mk_full_name" id="mk_full_name" class="regular-text" value="<?php echo esc_attr( $user->display_name ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="mk_password"><?php esc_html_e( 'Password', 'maapkathi' ); ?></label></th>
				<td>
					<input type="password" name="mk_password" id="mk_password" class="regular-text" autocomplete="new-password" required />
					<button type="button" class="button" onclick="['mk_password','mk_password_confirm'].forEach(function(id){var f=document.getElementById(id);f.type=f.type==='password'?'text':'password';});this.textContent=this.textContent==='<?php echo esc_js( __( 'Show', 'maapkathi' ) ); ?>'?'<?php echo esc_js( __( 'Hide', 'maapkathi' ) ); ?>':'<?php echo esc_js( __( 'Show', 'maapkathi' ) ); ?>';"><?php esc_html_e( 'Show', 'maapkathi' ); ?></button>
					<p class="description"><?php esc_html_e( 'At least 16 characters, with upper case, lower case, a digit, and a symbol.', 'maapkathi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="mk_password_confirm"><?php esc_html_e( 'Confirm password', 'maapkathi' ); ?></label></th>
				<td><input type="password" name="mk_password_confirm" id="mk_password_confirm" class="regular-text" autocomplete="new-password" required /></td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Create admin account', 'maapkathi' ); ?></button>
		</p>
	</form>
</div>
