<?php
/**
 * Plugin Name:       Maapkathi Core
 * Description:       Content, storage, roles, approval workflow and business logic for the Maapkathi Studio site. The theme renders; this plugin owns everything else.
 * Version:            0.1.0
 * Requires at least:  6.4
 * Requires PHP:       8.1
 * Author:             Maapkathi Studio
 * Text Domain:        maapkathi
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MK_PLUGIN_FILE', __FILE__ );
define( 'MK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MK_DB_VERSION', '1.1.0' );

// Sane defaults for every §5 constant — a site that never touches
// wp-config.php still boots on the documented defaults.
if ( ! defined( 'MK_STORAGE_DRIVER' ) ) {
	define( 'MK_STORAGE_DRIVER', 3 );
}
if ( ! defined( 'MK_VIDEO_DRIVER' ) ) {
	define( 'MK_VIDEO_DRIVER', 0 );
}
if ( ! defined( 'MK_CACHE_DRIVER' ) ) {
	define( 'MK_CACHE_DRIVER', 1 );
}
if ( ! defined( 'MK_MAIL_DRIVER' ) ) {
	define( 'MK_MAIL_DRIVER', 0 );
}
if ( ! defined( 'MK_LOCAL_STORAGE_DIR' ) ) {
	define( 'MK_LOCAL_STORAGE_DIR', WP_CONTENT_DIR . '/uploads/maapkathi' );
}
if ( ! defined( 'MK_MAX_VIDEO_BYTES' ) ) {
	define( 'MK_MAX_VIDEO_BYTES', 200 * 1024 * 1024 );
}
if ( ! defined( 'MK_MAX_IMAGE_BYTES' ) ) {
	define( 'MK_MAX_IMAGE_BYTES', 10 * 1024 * 1024 );
}
if ( ! defined( 'MK_MAX_GIF_BYTES' ) ) {
	define( 'MK_MAX_GIF_BYTES', 8 * 1024 * 1024 );
}
if ( ! defined( 'MK_CHUNK_BYTES' ) ) {
	define( 'MK_CHUNK_BYTES', 2 * 1024 * 1024 );
}
if ( ! defined( 'MK_HERO_SLIDE_SECONDS' ) ) {
	define( 'MK_HERO_SLIDE_SECONDS', 6 );
}
if ( ! defined( 'MK_MAX_HERO_HOLD_SECONDS' ) ) {
	define( 'MK_MAX_HERO_HOLD_SECONDS', 20 );
}
if ( ! defined( 'MK_MAIL_FROM_EMAIL' ) ) {
	define( 'MK_MAIL_FROM_EMAIL', '' );
}
if ( ! defined( 'MK_MAIL_FROM_NAME' ) ) {
	define( 'MK_MAIL_FROM_NAME', '' );
}
if ( ! defined( 'MK_SMTP_HOST' ) ) {
	define( 'MK_SMTP_HOST', '' );
}
if ( ! defined( 'MK_SMTP_PORT' ) ) {
	define( 'MK_SMTP_PORT', 587 );
}
if ( ! defined( 'MK_SMTP_USERNAME' ) ) {
	define( 'MK_SMTP_USERNAME', '' );
}
if ( ! defined( 'MK_SMTP_PASSWORD' ) ) {
	define( 'MK_SMTP_PASSWORD', '' );
}
if ( ! defined( 'MK_SMTP_ENCRYPTION' ) ) {
	define( 'MK_SMTP_ENCRYPTION', 'tls' );
}

$mk_autoload = MK_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $mk_autoload ) ) {
	require_once $mk_autoload;
} else {
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = 'Maapkathi\\Core\\';
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				return;
			}
			$relative = substr( $class_name, strlen( $prefix ) );
			$path     = MK_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( file_exists( $path ) ) {
				require $path;
			}
		}
	);
}

register_activation_hook( MK_PLUGIN_FILE, array( \Maapkathi\Core\Plugin::class, 'activate' ) );
register_deactivation_hook( MK_PLUGIN_FILE, array( \Maapkathi\Core\Plugin::class, 'deactivate' ) );

add_action( 'plugins_loaded', array( \Maapkathi\Core\Plugin::class, 'boot' ) );
