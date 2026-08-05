<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Rest;

use Maapkathi\Core\Support\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uptime health check (§3.5). Public and unauthenticated by design, so an
 * external monitor can poll it — which is exactly why it must leak
 * nothing: no WordPress version, no PHP version, no plugin list, no
 * configuration detail. Just "is the app up and can it reach the
 * database".
 *
 * Route: /wp-json/maapkathi/v1/health
 */
final class HealthController {

	private const NAMESPACE = 'maapkathi/v1';

	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'status' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function status(): \WP_REST_Response {
		global $wpdb;

		$db_ok = false;
		try {
			$db_ok = ( '1' === (string) $wpdb->get_var( 'SELECT 1' ) );
		} catch ( \Throwable $e ) {
			$db_ok = false;
		}

		$schema_ok = $db_ok && Migrations::tables_present();
		$healthy   = $db_ok && $schema_ok;

		$response = new \WP_REST_Response(
			array(
				'status'   => $healthy ? 'ok' : 'degraded',
				'database' => $db_ok ? 'ok' : 'error',
				'schema'   => $schema_ok ? 'ok' : 'error',
			),
			$healthy ? 200 : 503
		);

		$response->header( 'Cache-Control', 'no-store, max-age=0' );

		return $response;
	}
}
