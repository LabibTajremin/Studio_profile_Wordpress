<?php
/**
 * Versioned, idempotent database schema migrations.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

use Maapkathi\Core\Footer\FooterSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Versioned, idempotent database migrations.
 *
 * Runs on activation AND on every page load where the stored schema version
 * is behind MK_DB_VERSION, so an in-place plugin update (FTP overwrite,
 * Git pull, WP plugin upload) upgrades the database without anyone having
 * to deactivate/reactivate. Safe to run repeatedly on a database that is
 * already fully up to date — every step is written to be re-runnable.
 *
 * ## Why dbDelta() rather than "CREATE TABLE IF NOT EXISTS" / "ADD COLUMN IF NOT EXISTS"
 *
 * dbDelta() already provides exactly the create-if-not-exists semantics we
 * want, and it does it portably:
 *   - a table that does not exist is created;
 *   - a table that exists gains any missing columns and indexes;
 *   - a table that is already correct is left completely untouched.
 *
 * `ADD COLUMN IF NOT EXISTS` is a MariaDB-only extension that MySQL 8
 * rejects outright, so hand-rolling the SQL would make the plugin
 * non-portable for exactly zero benefit (BUILD_INSTRUCTIONS.md §3.6
 * calls this out explicitly). Everything below therefore goes through
 * dbDelta() for schema, and guarded writes for data.
 *
 * ## dbDelta() formatting rules (it is famously picky — do not "tidy" these)
 *   - two spaces after PRIMARY KEY
 *   - the keyword KEY, never INDEX
 *   - lowercase type names
 *   - one field per line
 *   - field types spelled exactly as MariaDB reports them back
 * Getting any of these wrong makes dbDelta re-issue the same ALTER on
 * every single request, forever.
 */
final class Migrations {

	public const OPTION_VERSION = 'mk_db_version';

	/**
	 * Every schema version in order. Add a new entry when the schema
	 * changes and bump MK_DB_VERSION in maapkathi-core.php to match.
	 *
	 * Each step must be idempotent on its own.
	 *
	 * @return array<string, callable():void>
	 */
	private static function steps(): array {
		return array(
			'1.0.0' => array( self::class, 'migrate_1_0_0' ),
			'1.1.0' => array( self::class, 'migrate_1_1_0' ),
		);
	}

	/**
	 * Runs any migration steps newer than the stored version, in order.
	 * Called on activation and (cheaply, version-guarded) on every load.
	 */
	public static function run(): void {
		$installed = (string) get_option( self::OPTION_VERSION, '0.0.0' );
		$target    = (string) MK_DB_VERSION;

		if ( version_compare( $installed, $target, '>=' ) ) {
			return;
		}

		// Guard against two concurrent requests both running migrations on
		// a busy site — shared hosting will happily serve them in parallel.
		$lock = 'mk_migrations_running';
		if ( get_transient( $lock ) ) {
			return;
		}
		set_transient( $lock, 1, 5 * MINUTE_IN_SECONDS );

		try {
			foreach ( self::steps() as $version => $callback ) {
				if ( version_compare( $installed, $version, '<' ) ) {
					call_user_func( $callback );
				}
			}

			update_option( self::OPTION_VERSION, $target );
		} finally {
			delete_transient( $lock );
		}
	}

	/**
	 * Initial schema: the three custom tables from §3.1.
	 * Everything else in the build uses native WordPress storage.
	 */
	private static function migrate_1_0_0(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		// VARCHAR(191) on every indexed string column: utf8mb4 reserves 4
		// bytes per character, and older InnoDB caps an index key at 767
		// bytes. 191 * 4 = 764. This is why WordPress core uses 191.
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

	/**
	 * True when every custom table physically exists — used by the
	 * Site Health / Dashboard check so a half-migrated install is visible
	 * rather than failing silently at the first insert.
	 */
	public static function tables_present(): bool {
		global $wpdb;

		foreach ( array( Database::inquiries_table(), Database::audit_log_table(), Database::revisions_table() ) as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( $found !== $table ) {
				return false;
			}
		}

		return true;
	}
	/**
	 * 1.1.0 — footer settings and the newsletter subscribers table (FR-08).
	 *
	 * The footer style is the only setting here that cannot simply take its
	 * shipped default: a fresh install wants the new Modern footer, but a
	 * site that is already live must keep the Classic one until its owner
	 * opts in, or its footer silently changes shape under it (FR-08.1,
	 * GR-03). "Already live" is read as "the site settings option exists",
	 * which is written the first time anyone saves settings or runs setup.
	 */
	private static function migrate_1_1_0(): void {
		global $wpdb;

		if ( false === get_option( FooterSettings::OPTION, false ) ) {
			$footer = FooterSettings::defaults();

			if ( false !== get_option( 'mk_site_settings', false ) ) {
				$footer['style'] = 'classic';
			}

			add_option( FooterSettings::OPTION, $footer );
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		// Unique index on email so a double submit cannot create a second
		// row for the same subscriber (spec section 13).
		$subscribers = "CREATE TABLE {$prefix}mk_subscribers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(191) NOT NULL,
  source VARCHAR(191) NULL,
  ip_hash VARCHAR(64) NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY email (email),
  KEY created_at (created_at)
) {$charset_collate};";

		dbDelta( $subscribers );
	}
}
