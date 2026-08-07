<?php
/**
 * Email-change confirmation and the verified-email gate on account recovery.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Users;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A user's registered email is never changed directly from the profile
 * screen — a change request emails a confirmation link to the NEW address
 * first, and the address only takes effect once that link is clicked.
 * Account recovery (`allow_password_reset`) is blocked for any account
 * whose current email has never been verified this way; completing a
 * password reset via an emailed link counts as verification too, since it
 * already proves control of the mailbox.
 */
final class EmailVerification {

	private const META_VERIFIED = 'mk_email_verified';
	private const META_PENDING  = 'mk_pending_email';
	private const META_TOKEN    = 'mk_email_verify_token';
	private const META_EXPIRES  = 'mk_email_verify_expires';

	/**
	 * Registers every hook this class needs.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'show_user_profile', array( $this, 'render_email_change_section' ) );
		add_action( 'edit_user_profile', array( $this, 'render_email_change_section' ) );
		add_action( 'personal_options_update', array( $this, 'handle_email_change_request' ) );
		add_action( 'edit_user_profile_update', array( $this, 'handle_email_change_request' ) );
		add_action( 'admin_post_mk_verify_email', array( $this, 'handle_verify' ) );
		add_action( 'admin_post_nopriv_mk_verify_email', array( $this, 'handle_verify' ) );
		add_filter( 'allow_password_reset', array( $this, 'gate_password_reset' ), 10, 2 );
		add_action( 'after_password_reset', array( $this, 'mark_verified_after_reset' ), 10, 1 );
	}

	/**
	 * Marks a user's current email as verified, e.g. right after initial
	 * account setup where the address was entered directly by an
	 * already-authenticated administrator.
	 *
	 * @param int $user_id User to mark verified.
	 * @return void
	 */
	public static function mark_verified( int $user_id ): void {
		update_user_meta( $user_id, self::META_VERIFIED, 1 );
	}

	/**
	 * Renders the "change email" section on the profile screen, below
	 * WordPress's own (read-only-in-effect) email field.
	 *
	 * @param \WP_User $user Profile being viewed.
	 * @return void
	 */
	public function render_email_change_section( \WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$pending  = get_user_meta( $user->ID, self::META_PENDING, true );
		$verified = (bool) get_user_meta( $user->ID, self::META_VERIFIED, true );
		?>
		<h2><?php esc_html_e( 'Email verification', 'maapkathi' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Status', 'maapkathi' ); ?></th>
				<td>
					<?php if ( $pending ) : ?>
						<p>
							<?php
							printf(
								/* translators: %s: the new, not-yet-confirmed email address. */
								esc_html__( 'Change requested to %s — check that inbox for a confirmation link.', 'maapkathi' ),
								'<strong>' . esc_html( (string) $pending ) . '</strong>'
							);
							?>
						</p>
					<?php elseif ( $verified ) : ?>
						<p><?php esc_html_e( 'Verified.', 'maapkathi' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Not verified. Account recovery will not work until this email is verified.', 'maapkathi' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="mk_new_email"><?php esc_html_e( 'Change email address', 'maapkathi' ); ?></label></th>
				<td>
					<?php wp_nonce_field( 'mk_change_email_' . $user->ID, 'mk_change_email_nonce' ); ?>
					<input type="email" name="mk_new_email" id="mk_new_email" class="regular-text" placeholder="<?php echo esc_attr( $user->user_email ); ?>" />
					<p class="description"><?php esc_html_e( 'A confirmation link is sent to the new address; it only takes effect once you click that link.', 'maapkathi' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Handles a submitted "change email" request from the profile screen.
	 *
	 * A no-op when the field was left blank, so this can hook unconditionally
	 * into every profile save without disturbing unrelated saves.
	 *
	 * @param int $user_id Profile being saved.
	 * @return void
	 */
	public function handle_email_change_request( int $user_id ): void {
		if ( empty( $_POST['mk_new_email'] ) ) {
			return;
		}

		check_admin_referer( 'mk_change_email_' . $user_id, 'mk_change_email_nonce' );

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above via check_admin_referer().
		$new_email = sanitize_email( wp_unslash( $_POST['mk_new_email'] ) );
		$user      = get_userdata( $user_id );

		if ( ! $user || ! is_email( $new_email ) ) {
			return;
		}

		if ( strtolower( $new_email ) === strtolower( $user->user_email ) ) {
			return;
		}

		$existing = email_exists( $new_email );
		if ( $existing && (int) $existing !== $user_id ) {
			return;
		}

		$token = wp_generate_password( 32, false );

		update_user_meta( $user_id, self::META_PENDING, $new_email );
		update_user_meta( $user_id, self::META_TOKEN, wp_hash_password( $token ) );
		update_user_meta( $user_id, self::META_EXPIRES, time() + DAY_IN_SECONDS );

		$verify_url = add_query_arg(
			array(
				'action' => 'mk_verify_email',
				'uid'    => $user_id,
				'token'  => rawurlencode( $token ),
			),
			admin_url( 'admin-post.php' )
		);

		wp_mail(
			$new_email,
			sprintf(
				/* translators: %s: site name. */
				__( '[%s] Confirm your new email address', 'maapkathi' ),
				get_bloginfo( 'name' )
			),
			sprintf(
				/* translators: %s: verification link URL. */
				__( "Click the link below to confirm this email address:\n\n%s\n\nThis link expires in 24 hours. If you didn't request this change, you can ignore this email.", 'maapkathi' ),
				$verify_url
			)
		);
	}

	/**
	 * Verifies a change-email confirmation link and applies the pending address.
	 *
	 * @return void
	 */
	public function handle_verify(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the single-use, expiring, hashed token itself IS the security mechanism here, not a nonce; the link is meant to work from a different device/session than the one that requested the change.
		$user_id = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above.
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		$stored_hash = $user_id ? get_user_meta( $user_id, self::META_TOKEN, true ) : '';
		$pending     = $user_id ? get_user_meta( $user_id, self::META_PENDING, true ) : '';
		$expires     = $user_id ? (int) get_user_meta( $user_id, self::META_EXPIRES, true ) : 0;

		if ( ! $user_id || ! $token || ! $stored_hash || ! $pending || time() > $expires || ! wp_check_password( $token, (string) $stored_hash ) ) {
			wp_die( esc_html__( 'This verification link is invalid or has expired. Request the email change again from your profile screen.', 'maapkathi' ) );
		}

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => $pending,
			)
		);

		update_user_meta( $user_id, self::META_VERIFIED, 1 );
		delete_user_meta( $user_id, self::META_PENDING );
		delete_user_meta( $user_id, self::META_TOKEN );
		delete_user_meta( $user_id, self::META_EXPIRES );

		wp_safe_redirect( add_query_arg( 'mk_email_verified', '1', admin_url( 'profile.php' ) ) );
		exit;
	}

	/**
	 * Blocks account recovery for any account whose current email has never
	 * been verified — an unverified inbox has no business receiving a
	 * password-reset link.
	 *
	 * @param bool|\WP_Error $allow   The current, possibly-already-denied recovery decision.
	 * @param int            $user_id Account requesting recovery.
	 * @return bool|\WP_Error
	 */
	public function gate_password_reset( $allow, int $user_id ) {
		if ( true !== $allow ) {
			return $allow;
		}

		if ( get_user_meta( $user_id, self::META_VERIFIED, true ) ) {
			return true;
		}

		return new \WP_Error( 'mk_email_unverified', __( 'This account\'s email address has not been verified, so it cannot be used for account recovery.', 'maapkathi' ) );
	}

	/**
	 * Successfully completing a password reset via an emailed link already
	 * proves control of the registered mailbox, so it counts as verification.
	 *
	 * @param \WP_User $user The user whose password was just reset.
	 * @return void
	 */
	public function mark_verified_after_reset( \WP_User $user ): void {
		self::mark_verified( $user->ID );
	}
}
