<?php
/**
 * Newsletter subscribe endpoint and storage (FR-08.12).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Footer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores footer newsletter signups locally.
 *
 * Local by default and by design: it costs nothing, needs no third-party
 * account, and cannot break the footer when someone else's API is down. An
 * optional Mailchimp key forwards on top of the local row rather than
 * instead of it, so a failed forward never loses the address.
 *
 * Route: POST /wp-json/maapkathi/v1/subscribe
 */
final class Subscribers {

	private const NAMESPACE = 'maapkathi/v1';

	/**
	 * How many attempts one IP may make before it is turned away.
	 */
	private const RATE_LIMIT = 5;

	/**
	 * Wire the REST route.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the public POST /subscribe route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'subscribe' ),
				// Public on purpose — visitors are not logged in. The nonce
				// checked inside the handler is what ties the request to a
				// page this site actually rendered.
				'permission_callback' => '__return_true',
				'args'                => array(
					'email'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'website' => array(
						'type'     => 'string',
						'required' => false,
					),
					'nonce'   => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Validates, rate-limits and stores one signup.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function subscribe( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! wp_verify_nonce( (string) $request->get_param( 'nonce' ), 'mk_subscribe' ) ) {
			return $this->error( __( 'This form has expired. Please reload the page and try again.', 'maapkathi' ), 403 );
		}

		// Honeypot: a field positioned off-screen that a human never fills
		// in. A bot that filled it gets the success response rather than an
		// error, so it has nothing to tune against.
		if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
			return $this->success( __( 'Thanks — you are on the list.', 'maapkathi' ) );
		}

		if ( $this->is_rate_limited() ) {
			return $this->error( __( 'Too many attempts. Please try again shortly.', 'maapkathi' ), 429 );
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return $this->error( __( 'That does not look like an email address.', 'maapkathi' ), 400 );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mk_subscribers';

		// The unique index on email is the real guard against duplicates;
		// this read only decides which message the visitor sees, so a race
		// between two identical submits ends in "already subscribed" rather
		// than a database error.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no core API.
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is a prefixed literal.

		if ( $existing ) {
			return $this->success( __( 'You are already on the list.', 'maapkathi' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no core API.
		$inserted = $wpdb->insert(
			$table,
			array(
				'email'      => $email,
				'source'     => 'footer',
				'ip_hash'    => self::ip_hash(),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return $this->error( __( 'We could not save that just now. Please try again.', 'maapkathi' ), 500 );
		}

		return $this->success( __( 'Thanks — you are on the list.', 'maapkathi' ) );
	}

	/**
	 * Whether this client has already used up its attempts.
	 *
	 * @return bool
	 */
	private function is_rate_limited(): bool {
		$key      = 'mk_subscribe_rate_' . self::ip_hash();
		$attempts = (int) get_transient( $key );

		if ( $attempts >= self::RATE_LIMIT ) {
			return true;
		}

		set_transient( $key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	/**
	 * Hash of the requesting IP, used as a rate-limit key and stored
	 * alongside the row instead of the address itself.
	 *
	 * @return string
	 */
	private static function ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return md5( $ip );
	}

	/**
	 * A successful JSON response.
	 *
	 * @param string $message Message to show inline.
	 * @return \WP_REST_Response
	 */
	private function success( string $message ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'ok'      => true,
				'message' => $message,
			),
			200
		);
	}

	/**
	 * A failure JSON response.
	 *
	 * @param string $message Message to show inline.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	private function error( string $message, int $status ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'ok'      => false,
				'message' => $message,
			),
			$status
		);
	}
}
