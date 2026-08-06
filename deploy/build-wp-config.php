<?php
/**
 * Generates a production wp-config.php from deploy/deploy.env.
 *
 * Usage:
 *   php deploy/build-wp-config.php                 # writes deploy/wp-config.php
 *   php deploy/build-wp-config.php --print         # prints to stdout instead
 *   php deploy/build-wp-config.php --env=path.env  # use a different env file
 *
 * Salts are generated locally with a cryptographically secure RNG, so this
 * works offline and never depends on the WordPress salt API being
 * reachable.
 *
 * @package Maapkathi
 */

declare( strict_types = 1 );

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit( "This script is CLI-only.\n" );
}

$options   = getopt( '', array( 'env::', 'out::', 'print' ) );
$env_path  = $options['env'] ?? __DIR__ . '/deploy.env';
$out_path  = $options['out'] ?? __DIR__ . '/wp-config.php';
$to_stdout = array_key_exists( 'print', $options );

if ( ! is_readable( $env_path ) ) {
	fwrite( STDERR, "Cannot read env file: {$env_path}\n" );
	exit( 1 );
}

/**
 * Minimal .env parser: KEY=VALUE, # comments, optional surrounding quotes.
 * Values are taken literally — no variable interpolation — so a password
 * containing $ or # after the first character survives intact.
 *
 * @return array<string,string>
 */
function mk_parse_env( string $path ): array {
	$out = array();

	foreach ( file( $path, FILE_IGNORE_NEW_LINES ) as $line ) {
		$line = trim( $line );

		if ( '' === $line || str_starts_with( $line, '#' ) ) {
			continue;
		}

		$pos = strpos( $line, '=' );
		if ( false === $pos ) {
			continue;
		}

		$key   = trim( substr( $line, 0, $pos ) );
		$value = trim( substr( $line, $pos + 1 ) );

		// Strip matching surrounding quotes, if present.
		if ( strlen( $value ) >= 2 ) {
			$first = $value[0];
			$last  = $value[ strlen( $value ) - 1 ];
			if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
				$value = substr( $value, 1, -1 );
			}
		}

		$out[ $key ] = $value;
	}

	return $out;
}

/** Cryptographically secure 64-char salt from the WordPress charset. */
function mk_salt(): string {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 !@#$%^&*()-_[]{}<>~`+=,.;:/?|';
	$max   = strlen( $chars ) - 1;
	$salt  = '';

	for ( $i = 0; $i < 64; $i++ ) {
		$salt .= $chars[ random_int( 0, $max ) ];
	}

	return $salt;
}

/** Escapes a value for embedding in a single-quoted PHP string literal. */
function mk_php_quote( string $value ): string {
	return "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value ) . "'";
}

function mk_bool( array $env, string $key, bool $default ): bool {
	$raw = strtolower( trim( $env[ $key ] ?? '' ) );
	if ( '' === $raw ) {
		return $default;
	}
	return in_array( $raw, array( '1', 'true', 'yes', 'on' ), true );
}

function mk_int( array $env, string $key, int $default ): int {
	$raw = trim( $env[ $key ] ?? '' );
	return is_numeric( $raw ) ? (int) $raw : $default;
}

$env = mk_parse_env( $env_path );

// ---------------------------------------------------------------------
// Validation — fail loudly rather than emitting a broken config.
// ---------------------------------------------------------------------
$errors   = array();
$required = array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'SITE_URL' );

foreach ( $required as $key ) {
	$value = trim( $env[ $key ] ?? '' );
	if ( '' === $value || str_contains( $value, 'CHANGE_ME' ) ) {
		$errors[] = "{$key} is not set (still CHANGE_ME or empty).";
	}
}

$admin_enabled = mk_bool( $env, 'ADMIN_ENABLED', false );

if ( $admin_enabled ) {
	foreach ( array( 'ADMIN_USERNAME', 'ADMIN_PASSWORD', 'ADMIN_EMAIL' ) as $key ) {
		$value = trim( $env[ $key ] ?? '' );
		if ( '' === $value || str_contains( $value, 'CHANGE_ME' ) ) {
			$errors[] = "{$key} is not set (ADMIN_ENABLED=true requires it).";
		}
	}

	$admin_user = trim( $env['ADMIN_USERNAME'] ?? '' );
	$admin_pass = trim( $env['ADMIN_PASSWORD'] ?? '' );
	$admin_mail = trim( $env['ADMIN_EMAIL'] ?? '' );

	if ( $admin_mail && ! filter_var( $admin_mail, FILTER_VALIDATE_EMAIL ) ) {
		$errors[] = 'ADMIN_EMAIL is not a valid email address.';
	}

	if ( $admin_pass && strlen( $admin_pass ) < 12 ) {
		$errors[] = 'ADMIN_PASSWORD is shorter than 12 characters — use 16+.';
	}

	if ( in_array( strtolower( $admin_user ), array( 'admin', 'administrator', 'root', 'wordpress' ), true ) ) {
		$errors[] = "ADMIN_USERNAME '{$admin_user}' is one of the first names credential-stuffing bots try. Pick something else.";
	}
}

if ( $errors ) {
	fwrite( STDERR, "\nCannot generate wp-config.php:\n\n" );
	foreach ( $errors as $error ) {
		fwrite( STDERR, "  ✗ {$error}\n" );
	}
	fwrite( STDERR, "\nEdit {$env_path} and run again.\n\n" );
	exit( 1 );
}

// ---------------------------------------------------------------------
// Warnings — worth flagging but not fatal.
// ---------------------------------------------------------------------
$warnings = array();

if ( ! str_starts_with( strtolower( trim( $env['SITE_URL'] ?? '' ) ), 'https://' ) ) {
	$warnings[] = 'SITE_URL is not https:// — install the Hostinger SSL certificate and update this before launch.';
}
if ( mk_bool( $env, 'WP_DEBUG', false ) && 'production' === strtolower( $env['WP_ENVIRONMENT'] ?? 'production' ) ) {
	$warnings[] = 'WP_DEBUG is true while WP_ENVIRONMENT is production — debug output will stay suppressed, but turn it off.';
}
if ( $admin_enabled ) {
	$warnings[] = 'ADMIN_* is enabled. After your first successful login, set ADMIN_ENABLED=false, regenerate, and re-upload wp-config.php.';
}

// ---------------------------------------------------------------------
// Build
// ---------------------------------------------------------------------
$site_url = rtrim( trim( $env['SITE_URL'] ), '/' );
$prefix   = trim( $env['DB_TABLE_PREFIX'] ?? 'wp_' ) ?: 'wp_';
$is_prod  = 'production' === strtolower( trim( $env['WP_ENVIRONMENT'] ?? 'production' ) );
$debug    = ! $is_prod && mk_bool( $env, 'WP_DEBUG', false );

$salt_keys = array(
	'AUTH_KEY',
	'SECURE_AUTH_KEY',
	'LOGGED_IN_KEY',
	'NONCE_KEY',
	'AUTH_SALT',
	'SECURE_AUTH_SALT',
	'LOGGED_IN_SALT',
	'NONCE_SALT',
);

$salt_lines = '';
foreach ( $salt_keys as $key ) {
	$salt_lines .= sprintf( "define( '%s', %s );\n", $key, mk_php_quote( mk_salt() ) );
}

$admin_block = "// Disabled — the admin account already exists.\n";
if ( $admin_enabled ) {
	$admin_block = sprintf(
		"define( 'MK_ADMIN_USERNAME', %s );\ndefine( 'MK_ADMIN_PASSWORD', %s );\ndefine( 'MK_ADMIN_EMAIL', %s );\ndefine( 'MK_ADMIN_NAME', %s );\n",
		mk_php_quote( trim( $env['ADMIN_USERNAME'] ) ),
		mk_php_quote( trim( $env['ADMIN_PASSWORD'] ) ),
		mk_php_quote( trim( $env['ADMIN_EMAIL'] ) ),
		mk_php_quote( trim( $env['ADMIN_NAME'] ?? 'Studio Admin' ) )
	);
}

$generated = gmdate( 'Y-m-d H:i:s' ) . ' UTC';

$config = <<<'PHPCONFIG'
<?php
/**
 * WordPress configuration — Maapkathi Studio.
 *
 * GENERATED by deploy/build-wp-config.php on {$generated}.
 * Regenerate with `php deploy/build-wp-config.php` after editing
 * deploy/deploy.env — hand edits here will be overwritten.
 *
 * @package Maapkathi
 */

// ---------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------
define( 'DB_NAME', {$q_db_name} );
define( 'DB_USER', {$q_db_user} );
define( 'DB_PASSWORD', {$q_db_pass} );
define( 'DB_HOST', {$q_db_host} );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = {$q_prefix};

// ---------------------------------------------------------------------
// Security salts — unique to this install, generated at build time
// ---------------------------------------------------------------------
{$salt_lines}
// ---------------------------------------------------------------------
// Site URLs — pinned so a database move can't hijack the domain
// ---------------------------------------------------------------------
define( 'WP_HOME', {$q_site_url} );
define( 'WP_SITEURL', {$q_site_url} );

// ---------------------------------------------------------------------
// Maapkathi configuration
// ---------------------------------------------------------------------
define( 'MK_STORAGE_DRIVER', {$storage_driver} );
define( 'MK_VIDEO_DRIVER', {$video_driver} );
define( 'MK_CACHE_DRIVER', {$cache_driver} );
define( 'MK_MAIL_DRIVER', {$mail_driver} );

define( 'MK_LOCAL_STORAGE_DIR', WP_CONTENT_DIR . '/uploads/maapkathi' );

define( 'MK_MAX_VIDEO_BYTES', {$max_video} );
define( 'MK_MAX_IMAGE_BYTES', {$max_image} );
define( 'MK_MAX_GIF_BYTES', {$max_gif} );
define( 'MK_CHUNK_BYTES', {$chunk_bytes} );
define( 'MK_HERO_SLIDE_SECONDS', {$hero_seconds} );
define( 'MK_MAX_HERO_HOLD_SECONDS', {$hero_hold} );

// ---------------------------------------------------------------------
// First admin account (created once, on plugin activation)
// ---------------------------------------------------------------------
{$admin_block}
// ---------------------------------------------------------------------
// Environment & hardening
// ---------------------------------------------------------------------
define( 'WP_ENVIRONMENT_TYPE', {$q_environment} );

// No plugin/theme file editor in wp-admin: a stolen admin session must not
// be able to write PHP onto the server.
define( 'DISALLOW_FILE_EDIT', true );

// Security releases apply automatically; feature updates stay manual.
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

define( 'FORCE_SSL_ADMIN', {$force_ssl} );

// Trust Hostinger's proxy header so is_ssl() is correct behind their load
// balancer — without this WordPress can emit http:// URLs on an https://
// site and cause redirect loops.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

define( 'WP_DEBUG', {$wp_debug} );
define( 'WP_DEBUG_LOG', {$wp_debug} );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', '0' );

// Keep the database lean on shared hosting.
define( 'WP_POST_REVISIONS', 10 );
define( 'EMPTY_TRASH_DAYS', 30 );
define( 'AUTOSAVE_INTERVAL', 120 );

define( 'WP_MEMORY_LIMIT', {$q_memory} );
define( 'WP_MAX_MEMORY_LIMIT', '384M' );

// ---------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';

PHPCONFIG;

// The template above is a NOWDOC, so PHP performs no interpolation in it —
// every {$placeholder} is substituted here instead. That keeps the escaping
// explicit and auditable, and means a value containing $ or {} can never
// be evaluated as code.
$config = strtr(
	$config,
	array(
		'{$generated}'     => $generated,
		'{$q_db_name}'     => mk_php_quote( trim( $env['DB_NAME'] ) ),
		'{$q_db_user}'     => mk_php_quote( trim( $env['DB_USER'] ) ),
		'{$q_db_pass}'     => mk_php_quote( $env['DB_PASSWORD'] ),
		'{$q_db_host}'     => mk_php_quote( trim( $env['DB_HOST'] ?? 'localhost' ) ?: 'localhost' ),
		'{$q_prefix}'      => mk_php_quote( $prefix ),
		'{$q_site_url}'    => mk_php_quote( $site_url ),
		'{$q_environment}' => mk_php_quote( $is_prod ? 'production' : strtolower( trim( $env['WP_ENVIRONMENT'] ?? 'staging' ) ) ),
		'{$q_memory}'      => mk_php_quote( trim( $env['WP_MEMORY_LIMIT'] ?? '256M' ) ?: '256M' ),
		'{$storage_driver}' => (string) mk_int( $env, 'MK_STORAGE_DRIVER', 3 ),
		'{$video_driver}'  => (string) mk_int( $env, 'MK_VIDEO_DRIVER', 0 ),
		'{$cache_driver}'  => (string) mk_int( $env, 'MK_CACHE_DRIVER', 1 ),
		'{$mail_driver}'   => (string) mk_int( $env, 'MK_MAIL_DRIVER', 0 ),
		'{$max_video}'     => (string) mk_int( $env, 'MK_MAX_VIDEO_BYTES', 209715200 ),
		'{$max_image}'     => (string) mk_int( $env, 'MK_MAX_IMAGE_BYTES', 10485760 ),
		'{$max_gif}'       => (string) mk_int( $env, 'MK_MAX_GIF_BYTES', 8388608 ),
		'{$chunk_bytes}'   => (string) mk_int( $env, 'MK_CHUNK_BYTES', 2097152 ),
		'{$hero_seconds}'  => (string) mk_int( $env, 'MK_HERO_SLIDE_SECONDS', 6 ),
		'{$hero_hold}'     => (string) mk_int( $env, 'MK_MAX_HERO_HOLD_SECONDS', 20 ),
		'{$force_ssl}'     => mk_bool( $env, 'FORCE_SSL_ADMIN', true ) ? 'true' : 'false',
		'{$wp_debug}'      => $debug ? 'true' : 'false',
		'{$salt_lines}'    => $salt_lines,
		'{$admin_block}'   => $admin_block,
	)
);

if ( $to_stdout ) {
	echo $config;
	exit( 0 );
}

if ( false === file_put_contents( $out_path, $config ) ) {
	fwrite( STDERR, "Could not write {$out_path}\n" );
	exit( 1 );
}

@chmod( $out_path, 0600 );

echo "\n  ✓ Wrote {$out_path}\n";
echo "    Unique salts generated. Upload this to public_html/ as wp-config.php.\n";

if ( $warnings ) {
	echo "\n  Notes:\n";
	foreach ( $warnings as $warning ) {
		echo "    • {$warning}\n";
	}
}

echo "\n";
exit( 0 );
