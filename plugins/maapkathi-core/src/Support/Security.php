<?php
/**
 * Site hardening: header stripping, XML-RPC/enumeration lockdown, security
 * headers and upload MIME allowlisting.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site hardening (§13). Everything here is safe to run on shared hosting
 * and needs no server config access.
 *
 * Note on DISALLOW_FILE_EDIT: that must be a wp-config.php constant (it is
 * read before plugins load), so it lives in the deployment docs and the
 * shipped wp-config snippet rather than here. This class covers everything
 * that CAN be done from a plugin.
 */
final class Security {

	/**
	 * Registers every hardening hook: header stripping, XML-RPC lockdown,
	 * enumeration blocking, security headers and upload restrictions.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Remove the WordPress version string from <head> and from feeds —
		// it tells an attacker exactly which exploits to try.
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );

		// Strip the version query arg off enqueued assets for the same
		// reason (it exposes core/plugin versions to anyone reading source).
		add_filter( 'style_loader_src', array( $this, 'strip_version_arg' ), 9999 );
		add_filter( 'script_loader_src', array( $this, 'strip_version_arg' ), 9999 );

		// XML-RPC is a standing brute-force and pingback-DDoS surface and
		// nothing in this build uses it.
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );
		add_action( 'init', array( $this, 'disable_xmlrpc_methods' ) );

		// Block unauthenticated REST user enumeration (/wp-json/wp/v2/users),
		// which otherwise hands out valid usernames for a brute-force list.
		add_filter( 'rest_endpoints', array( $this, 'block_user_enumeration' ) );
		// Same via the ?author=N redirect probe.
		add_action( 'template_redirect', array( $this, 'block_author_scan' ) );

		// Don't confirm which half of the credentials was wrong.
		add_filter( 'login_errors', array( $this, 'generic_login_error' ) );

		add_action( 'send_headers', array( $this, 'send_security_headers' ) );

		// Keep the admin area out of search indexes.
		add_action( 'admin_head', array( $this, 'noindex_admin' ) );

		add_filter( 'upload_mimes', array( $this, 'restrict_upload_mimes' ) );
	}

	/**
	 * Strips the `?ver=` query arg from an enqueued asset URL.
	 *
	 * @param string $src Enqueued script/style source URL.
	 * @return string
	 */
	public function strip_version_arg( string $src ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Removes the X-Pingback header, since XML-RPC is disabled.
	 *
	 * @param array<string,string> $headers Response headers keyed by name.
	 * @return array<string,string>
	 */
	public function remove_pingback_header( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Empties the XML-RPC method list so no methods remain callable even
	 * if something re-enables the xmlrpc_enabled filter.
	 *
	 * @return void
	 */
	public function disable_xmlrpc_methods(): void {
		add_filter(
			'xmlrpc_methods',
			static function () {
				return array();
			}
		);
	}

	/**
	 * Removes the unauthenticated REST users endpoints, so anonymous
	 * requests cannot enumerate valid usernames.
	 *
	 * @param array<string,mixed> $endpoints Registered REST endpoints keyed by route.
	 * @return array<string,mixed>
	 */
	public function block_user_enumeration( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		unset( $endpoints['/wp/v2/users'] );
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

		return $endpoints;
	}

	/**
	 * Redirects anonymous `?author=N` requests home, closing off the
	 * classic username-enumeration probe.
	 *
	 * @return void
	 */
	public function block_author_scan(): void {
		if ( is_admin() || is_user_logged_in() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only probe check.
		if ( ! empty( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/**
	 * Generic login-failure message that doesn't reveal which credential
	 * (username or password) was wrong.
	 *
	 * @return string
	 */
	public function generic_login_error(): string {
		return __( 'The username or password you entered is incorrect.', 'maapkathi' );
	}

	/**
	 * Sends hardening HTTP response headers (clickjacking, MIME-sniffing,
	 * referrer, permissions, and a restrictive CSP on the front end).
	 *
	 * @return void
	 */
	public function send_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()' );

		// CSP frame-src is limited to the video-embed allowlist so that even
		// a validation bypass cannot frame an arbitrary origin (§13).
		if ( ! is_admin() ) {
			header(
				"Content-Security-Policy: frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com; "
				. "frame-ancestors 'self'; "
				. "object-src 'none'; "
				. "base-uri 'self'"
			);
		}
	}

	public function noindex_admin(): void {
		echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
	}

	/**
	 * Allowlist only the media types this build actually uses (§13).
	 * Everything else — including SVG, which is an XSS vector when
	 * uploadable by non-trusted roles — is rejected.
	 *
	 * @param array<string,string> $mimes
	 * @return array<string,string>
	 */
	public function restrict_upload_mimes( array $mimes ): array {
		return array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'gif'          => 'image/gif',
			'mp4|m4v'      => 'video/mp4',
			'webm'         => 'video/webm',
			'ico'          => 'image/x-icon',
		);
	}
}
