<?php
/**
 * Outbound mail configuration: sender identity and the SMTP transport.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Mail;

use Maapkathi\Core\Config\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires wp_mail's sender identity (info@maapkathi.com by default, per §5) and,
 * when MK_MAIL_DRIVER=1, a real SMTP transport via PHPMailer — the shared
 * hosting default PHP mail() function is unreliable for delivery on Hostinger
 * and elsewhere, so account-recovery and verification email need this to
 * actually arrive.
 */
final class Mailer {

	/**
	 * Registers the wp_mail_from/wp_mail_from_name filters, and the
	 * phpmailer_init SMTP wiring when the SMTP driver is active.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'wp_mail_from', array( $this, 'from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ) );

		if ( 1 === Config::instance()->mail_driver() ) {
			add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		}
	}

	/**
	 * The configured sender address, or WordPress's own default when unset.
	 *
	 * @param string $default_email WordPress's own default From address.
	 * @return string
	 */
	public function from_email( string $default_email ): string {
		$configured = defined( 'MK_MAIL_FROM_EMAIL' ) ? trim( (string) MK_MAIL_FROM_EMAIL ) : '';
		return $configured && is_email( $configured ) ? $configured : $default_email;
	}

	/**
	 * The configured sender display name, or the site name when unset.
	 *
	 * @param string $default_name WordPress's own default From name.
	 * @return string
	 */
	public function from_name( string $default_name ): string {
		$configured = defined( 'MK_MAIL_FROM_NAME' ) ? trim( (string) MK_MAIL_FROM_NAME ) : '';
		return $configured ? $configured : $default_name;
	}

	/**
	 * Configures PHPMailer to send over SMTP using the MK_SMTP_* constants.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The PHPMailer instance about to send.
	 * @return void
	 */
	public function configure_smtp( \PHPMailer\PHPMailer\PHPMailer $phpmailer ): void {
		$host       = defined( 'MK_SMTP_HOST' ) ? (string) MK_SMTP_HOST : '';
		$username   = defined( 'MK_SMTP_USERNAME' ) ? (string) MK_SMTP_USERNAME : '';
		$password   = defined( 'MK_SMTP_PASSWORD' ) ? (string) MK_SMTP_PASSWORD : '';
		$port       = defined( 'MK_SMTP_PORT' ) ? (int) MK_SMTP_PORT : 587;
		$encryption = defined( 'MK_SMTP_ENCRYPTION' ) ? strtolower( (string) MK_SMTP_ENCRYPTION ) : 'tls';

		if ( '' === $host || '' === $username || '' === $password ) {
			return;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer's own public API, not ours to rename.
		$phpmailer->isSMTP();
		$phpmailer->Host        = $host;
		$phpmailer->Port        = $port;
		$phpmailer->SMTPAuth    = true;
		$phpmailer->Username    = $username;
		$phpmailer->Password    = $password;
		$phpmailer->SMTPSecure  = in_array( $encryption, array( 'tls', 'ssl' ), true ) ? $encryption : '';
		$phpmailer->SMTPAutoTLS = 'tls' === $phpmailer->SMTPSecure;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
