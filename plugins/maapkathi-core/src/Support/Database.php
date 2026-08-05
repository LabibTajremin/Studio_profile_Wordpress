<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and upgrades the three custom tables via dbDelta(), versioned with
 * the mk_db_version option. See BUILD_INSTRUCTIONS.md §3.6 for the MariaDB
 * type-mapping rules this file must follow exactly:
 * - VARCHAR(191) on every indexed string column (utf8mb4 767-byte index cap)
 * - LONGTEXT (never a native JSON type) for JSON payloads, encoded/decoded in PHP only
 * - DATETIME storing UTC, never TIMESTAMPTZ
 * - $wpdb->prefix, never a hardcoded wp_ prefix
 * - get_charset_collate(), never a hardcoded charset
 */
final class Database {

	public const OPTION_VERSION = 'mk_db_version';

	public static function install(): void {
		self::run_delta();
		update_option( self::OPTION_VERSION, MK_DB_VERSION );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::OPTION_VERSION ) !== MK_DB_VERSION ) {
			self::run_delta();
			update_option( self::OPTION_VERSION, MK_DB_VERSION );
		}
	}

	private static function run_delta(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix           = $wpdb->prefix;

		$inquiries = "CREATE TABLE {$prefix}mk_inquiries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL,
  phone VARCHAR(64) NULL,
  message LONGTEXT NOT NULL,
  source VARCHAR(191) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY is_read (is_read),
  KEY created_at (created_at)
) $charset_collate;";

		$audit_log = "CREATE TABLE {$prefix}mk_audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(191) NOT NULL,
  entity VARCHAR(191) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  diff LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY entity_lookup (entity,entity_id),
  KEY created_at (created_at)
) $charset_collate;";

		$revisions = "CREATE TABLE {$prefix}mk_revisions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity VARCHAR(191) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  payload LONGTEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  submitted_by BIGINT UNSIGNED NOT NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY entity_lookup (entity,entity_id)
) $charset_collate;";

		dbDelta( $inquiries );
		dbDelta( $audit_log );
		dbDelta( $revisions );
	}

	public static function inquiries_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mk_inquiries';
	}

	public static function audit_log_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mk_audit_log';
	}

	public static function revisions_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mk_revisions';
	}
}
