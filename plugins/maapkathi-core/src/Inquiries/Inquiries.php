<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Inquiries;

use Maapkathi\Core\Support\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact form handling (§3.2 /contact, §12). Server-side validation,
 * honeypot + rate limiting, storage in mk_inquiries, optional email per
 * MK_MAIL_DRIVER.
 */
final class Inquiries {

	public function register_hooks(): void {
		add_action( 'admin_post_mk_submit_inquiry', array( $this, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_mk_submit_inquiry', array( $this, 'handle_submit' ) );
	}

	public function handle_submit(): void {
		check_admin_referer( 'mk_inquiry', 'mk_inquiry_nonce' );

		// Honeypot: a hidden field a bot fills in but a human never sees.
		if ( ! empty( $_POST['mk_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'mk_inquiry', 'sent', wp_get_referer() ?: home_url( '/contact/' ) ) );
			exit;
		}

		if ( $this->is_rate_limited() ) {
			wp_die( esc_html__( 'Too many submissions. Please try again later.', 'maapkathi' ), 429 );
		}

		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		$errors = $this->validate( $name, $email, $message );

		if ( ! empty( $errors ) ) {
			set_transient( self::errors_key(), $errors, 5 * MINUTE_IN_SECONDS );
			// Preserve what they typed so the form can be repopulated —
			// making someone retype a long message because of one bad field
			// is how contact forms lose real enquiries.
			// Repopulate from the raw text, not the sanitised values:
			// sanitize_email() turns a typo'd address into an empty string,
			// which would silently wipe the field the visitor most needs to
			// correct. Output is escaped at render time.
			set_transient(
				self::old_input_key(),
				array(
					'name'    => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
					'email'   => sanitize_text_field( wp_unslash( $_POST['email'] ?? '' ) ),
					'phone'   => $phone,
					'message' => $message,
				),
				5 * MINUTE_IN_SECONDS
			);
			wp_safe_redirect( wp_get_referer() ?: home_url( '/contact/' ) );
			exit;
		}

		global $wpdb;
		$wpdb->insert(
			Database::inquiries_table(),
			array(
				'name'       => $name,
				'email'      => $email,
				'phone'      => $phone,
				'message'    => $message,
				'source'     => isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'contact_page',
				'is_read'    => 0,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		$this->maybe_notify( $name, $email, $message );

		wp_safe_redirect( add_query_arg( 'mk_inquiry', 'sent', wp_get_referer() ?: home_url( '/contact/' ) ) );
		exit;
	}

	/**
	 * @return string[]
	 */
	public function validate( string $name, string $email, string $message ): array {
		$errors = array();

		if ( '' === trim( $name ) ) {
			$errors[] = __( 'Name is required.', 'maapkathi' );
		}
		if ( ! is_email( $email ) ) {
			$errors[] = __( 'A valid email is required.', 'maapkathi' );
		}
		if ( strlen( trim( $message ) ) < 10 ) {
			$errors[] = __( 'Message is too short.', 'maapkathi' );
		}

		return $errors;
	}

	private function is_rate_limited(): bool {
		$key      = 'mk_inquiry_rate_' . $this->client_ip_hash();
		$attempts = (int) get_transient( $key );

		if ( $attempts >= 5 ) {
			return true;
		}

		set_transient( $key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	private function client_ip_hash(): string {
		return self::ip_hash();
	}

	private static function ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return md5( $ip );
	}

	private static function errors_key(): string {
		return 'mk_inquiry_errors_' . self::ip_hash();
	}

	private static function old_input_key(): string {
		return 'mk_inquiry_old_' . self::ip_hash();
	}

	/**
	 * Validation errors from the previous submission, consumed once
	 * (flash-message semantics) so a later refresh doesn't re-show them.
	 *
	 * @return string[]
	 */
	public static function take_errors(): array {
		$errors = get_transient( self::errors_key() );
		if ( ! $errors ) {
			return array();
		}
		delete_transient( self::errors_key() );
		return is_array( $errors ) ? $errors : array();
	}

	/**
	 * The previously submitted values, so a rejected form can be
	 * repopulated instead of cleared. Consumed once, like the errors.
	 *
	 * @return array<string,string>
	 */
	public static function take_old_input(): array {
		$old = get_transient( self::old_input_key() );
		if ( ! $old ) {
			return array();
		}
		delete_transient( self::old_input_key() );
		return is_array( $old ) ? $old : array();
	}

	private function maybe_notify( string $name, string $email, string $message ): void {
		$driver = defined( 'MK_MAIL_DRIVER' ) ? (int) MK_MAIL_DRIVER : 0;
		if ( 0 === $driver ) {
			return; // Inbox-only, per §5 default.
		}

		$to = get_option( 'admin_email' );
		wp_mail(
			$to,
			sprintf( '[Maapkathi] New inquiry from %s', $name ),
			sprintf( "%s\n\n%s", $email, $message )
		);
	}

	public function mark_read( int $id, bool $read = true ): void {
		global $wpdb;
		$wpdb->update( Database::inquiries_table(), array( 'is_read' => $read ? 1 : 0 ), array( 'id' => $id ) );
	}
}
