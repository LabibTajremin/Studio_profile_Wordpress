<?php
/**
 * PHPStan-only bootstrap.
 *
 * Declares the constants normally defined by the plugin's own bootstrap file
 * (plugins/maapkathi-core/maapkathi-core.php, not scanned by static analysis)
 * and the wp-cli class, which is only present at runtime inside a real
 * `wp` invocation. Never loaded outside of `composer analyse`.
 *
 * Values are read through getenv() rather than written as literals so
 * PHPStan infers plain `int`/`string` types instead of narrowing to a
 * specific literal — a literal (e.g. `0`) would make deployment-configurable
 * branches like `MK_MAIL_DRIVER` look permanently dead code to the analyser.
 *
 * @package Maapkathi\Core
 */

define( 'MK_PLUGIN_FILE', __DIR__ . '/plugins/maapkathi-core/maapkathi-core.php' );
define( 'MK_PLUGIN_DIR', __DIR__ . '/plugins/maapkathi-core/' );
define( 'MK_PLUGIN_URL', (string) getenv( 'PHPSTAN_STUB_URL' ) );
define( 'MK_DB_VERSION', (string) getenv( 'PHPSTAN_STUB_STRING' ) );

define( 'MK_STORAGE_DRIVER', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_VIDEO_DRIVER', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_CACHE_DRIVER', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_MAIL_DRIVER', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_LOCAL_STORAGE_DIR', __DIR__ . '/wp-content/uploads/maapkathi' );
define( 'MK_MAX_VIDEO_BYTES', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_MAX_IMAGE_BYTES', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_MAX_GIF_BYTES', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_CHUNK_BYTES', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_HERO_SLIDE_SECONDS', (int) getenv( 'PHPSTAN_STUB_INT' ) );
define( 'MK_MAX_HERO_HOLD_SECONDS', (int) getenv( 'PHPSTAN_STUB_INT' ) );

define( 'MK_ADMIN_USERNAME', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_ADMIN_PASSWORD', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_ADMIN_EMAIL', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_ADMIN_NAME', (string) getenv( 'PHPSTAN_STUB_STRING' ) );

define( 'MK_SUPABASE_URL', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_SUPABASE_SERVICE_KEY', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_SUPABASE_BUCKET', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_BUNNY_STORAGE_ZONE', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_BUNNY_ACCESS_KEY', (string) getenv( 'PHPSTAN_STUB_STRING' ) );
define( 'MK_GDRIVE_SERVICE_ACCOUNT_JSON', (string) getenv( 'PHPSTAN_STUB_STRING' ) );

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Minimal stand-in for the wp-cli framework class, present only at
	 * runtime inside an actual `wp` command invocation.
	 */
	class WP_CLI {
		/**
		 * @param string $message
		 */
		public static function log( $message ): void {}

		/**
		 * @param string $message
		 */
		public static function success( $message ): void {}

		/**
		 * @param string $message
		 */
		public static function warning( $message ): void {}

		/**
		 * @param string $message
		 */
		public static function error( $message ): void {}

		/**
		 * @param string $name
		 * @param callable|class-string $callable
		 * @param array<string,mixed>   $args
		 */
		public static function add_command( $name, $callable, array $args = array() ): void {}
	}
}
