<?php
/**
 * Maapkathi Studio — wp-config.php template for Hostinger.
 *
 * Copy to the WordPress root as `wp-config.php` and fill in the database
 * credentials from hPanel → Databases → Management, plus fresh salts from
 * https://api.wordpress.org/secret-key/1.1/salt/
 *
 * @package Maapkathi
 */

// ---------------------------------------------------------------------
// Database — from hPanel → Databases → Management
// ---------------------------------------------------------------------
define( 'DB_NAME', 'REPLACE_DB_NAME' );
define( 'DB_USER', 'REPLACE_DB_USER' );
define( 'DB_PASSWORD', 'REPLACE_DB_PASSWORD' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Hostinger installs often use a randomised prefix. Keep whatever the
// installer generated — never hardcode wp_ anywhere in the codebase.
$table_prefix = 'wp_';

// ---------------------------------------------------------------------
// Salts — regenerate at https://api.wordpress.org/secret-key/1.1/salt/
// ---------------------------------------------------------------------
define( 'AUTH_KEY', 'REPLACE' );
define( 'SECURE_AUTH_KEY', 'REPLACE' );
define( 'LOGGED_IN_KEY', 'REPLACE' );
define( 'NONCE_KEY', 'REPLACE' );
define( 'AUTH_SALT', 'REPLACE' );
define( 'SECURE_AUTH_SALT', 'REPLACE' );
define( 'LOGGED_IN_SALT', 'REPLACE' );
define( 'NONCE_SALT', 'REPLACE' );

// ---------------------------------------------------------------------
// Maapkathi configuration (§5)
// ---------------------------------------------------------------------
define( 'MK_STORAGE_DRIVER', 3 );   // 1 S3 · 2 Google Drive · 3 Local (SHIPPED) · 4 R2 · 5 Supabase · 6 Bunny
define( 'MK_VIDEO_DRIVER', 0 );     // 0 same-as-storage (SHIPPED)
define( 'MK_CACHE_DRIVER', 1 );     // 1 transients · 2 Redis
define( 'MK_MAIL_DRIVER', 0 );      // 0 inbox only · 1 SMTP · 2 external API

// ABSPATH / WP_CONTENT_DIR are not set until wp-settings.php runs, so they
// are established here before anything builds a path from them. WordPress
// only defines them when they are not already set, so this is safe.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
define( 'MK_LOCAL_STORAGE_DIR', WP_CONTENT_DIR . '/uploads/maapkathi' );

define( 'MK_MAX_VIDEO_BYTES', 200 * 1024 * 1024 );
define( 'MK_MAX_IMAGE_BYTES', 10 * 1024 * 1024 );
define( 'MK_MAX_GIF_BYTES', 8 * 1024 * 1024 );
define( 'MK_CHUNK_BYTES', 2 * 1024 * 1024 );
define( 'MK_HERO_SLIDE_SECONDS', 6 );
define( 'MK_MAX_HERO_HOLD_SECONDS', 20 );

// ---------------------------------------------------------------------
// Outbound email — sender identity + SMTP (only used when MK_MAIL_DRIVER=1
// above). Needed for email-verification links and password-recovery
// emails to actually send. Hostinger's SMTP is usually
// smtp.hostinger.com : 587 (STARTTLS) — check hPanel → Emails to confirm.
// ---------------------------------------------------------------------
define( 'MK_MAIL_FROM_EMAIL', 'info@maapkathi.com' );
define( 'MK_MAIL_FROM_NAME', 'Maapkathi Studio' );
define( 'MK_SMTP_HOST', '' );
define( 'MK_SMTP_PORT', 587 );
define( 'MK_SMTP_USERNAME', '' );
define( 'MK_SMTP_PASSWORD', '' );
define( 'MK_SMTP_ENCRYPTION', 'tls' ); // tls | ssl

/*
 * ---------------------------------------------------------------------
 * First-run admin account (legacy / optional)
 * ---------------------------------------------------------------------
 * The first admin is now set up on-site instead: the moment the
 * WordPress installer's own admin first opens wp-admin, a one-time
 * "Maapkathi Setup" screen walks them through choosing their real
 * username, email, password, and full name — no wp-config.php editing
 * needed for the normal path.
 *
 * Leave these commented out unless you specifically need to pre-seed an
 * admin account non-interactively.
 */
// define( 'MK_ADMIN_USERNAME', 'studioadmin' );
// define( 'MK_ADMIN_PASSWORD', 'CHANGE-THIS-NOW-x9F2!' );
// define( 'MK_ADMIN_EMAIL', 'you@yourdomain.com' );
// define( 'MK_ADMIN_NAME', 'Studio Admin' );

// ---------------------------------------------------------------------
// Production hardening
// ---------------------------------------------------------------------
// No plugin/theme file editor in wp-admin — a stolen admin session should
// not be able to write PHP straight onto the server (§13).
define( 'DISALLOW_FILE_EDIT', true );

// Keep core auto-updating for security releases, but never let the site
// install plugins/themes from the dashboard without review.
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// Force SSL for logins and admin. Enable once the Hostinger SSL
// certificate is active for the domain (hPanel → Security → SSL).
define( 'FORCE_SSL_ADMIN', true );

// Trust Hostinger's proxy X-Forwarded-Proto so is_ssl() is correct behind
// their load balancer; without this WordPress can build http:// URLs on an
// https:// site and trigger redirect loops.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

// Debugging OFF in production. Turn WP_DEBUG on only while diagnosing,
// and never with WP_DEBUG_DISPLAY on a live site.
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', '0' );

// Limit stored revisions — shared hosting databases get large fast.
define( 'WP_POST_REVISIONS', 10 );
define( 'EMPTY_TRASH_DAYS', 30 );
define( 'AUTOSAVE_INTERVAL', 120 );

// Memory. Hostinger Premium allows this comfortably.
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_MAX_MEMORY_LIMIT', '384M' );

// ---------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
