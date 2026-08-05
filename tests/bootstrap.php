<?php
/**
 * PHPUnit bootstrap.
 *
 * Unit tests run with Brain\Monkey (no WordPress). Integration tests require
 * the WP PHPUnit test suite + a real MariaDB instance (see docs/TESTING.md)
 * and are skipped automatically when WP_TESTS_DIR is not set.
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wp_tests_dir ) {
	// Minimal function stubs so pure-logic unit tests (registries, the
	// approval policy, the video resolver) run standalone via Brain Monkey
	// without a full WordPress install. Integration tests below load the
	// real WP PHPUnit suite instead, where these are never used.
	if ( ! function_exists( 'esc_url_raw' ) ) {
		function esc_url_raw( $url ) {
			return filter_var( $url, FILTER_SANITIZE_URL );
		}
	}
	if ( ! function_exists( 'wp_parse_url' ) ) {
		function wp_parse_url( $url, $component = -1 ) {
			return -1 === $component ? parse_url( $url ) : parse_url( $url, $component );
		}
	}
	if ( ! function_exists( 'sanitize_hex_color' ) ) {
		function sanitize_hex_color( $color ) {
			return preg_match( '/^#[0-9a-fA-F]{3,6}$/', (string) $color ) ? $color : null;
		}
	}
	if ( ! function_exists( 'absint' ) ) {
		function absint( $value ) {
			return abs( (int) $value );
		}
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
}

if ( $wp_tests_dir && file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
    require_once $wp_tests_dir . '/includes/functions.php';

    tests_add_filter(
        'muplugins_loaded',
        static function () {
            require dirname( __DIR__ ) . '/plugins/maapkathi-core/maapkathi-core.php';
        }
    );

    require $wp_tests_dir . '/includes/bootstrap.php';
}
