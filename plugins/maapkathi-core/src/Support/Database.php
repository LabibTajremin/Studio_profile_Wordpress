<?php
/**
 * Custom table-name helpers and thin wrappers over the migration runner.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Table-name helpers plus thin wrappers over the versioned migration
 * runner. All actual schema work lives in Support\Migrations.
 *
 * Never hardcode a `wp_` prefix anywhere — Hostinger installs routinely
 * use a randomised table prefix.
 */
final class Database {

	public const OPTION_VERSION = Migrations::OPTION_VERSION;

	/** Runs on plugin activation. */
	public static function install(): void {
		Migrations::run();
	}

	/** Runs on every load; cheap no-op once the version matches. */
	public static function maybe_upgrade(): void {
		Migrations::run();
	}

	/**
	 * Fully-prefixed name of the inquiries table.
	 *
	 * @return string
	 */
	public static function inquiries_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mk_inquiries';
	}

	/**
	 * Fully-prefixed name of the audit log table.
	 *
	 * @return string
	 */
	public static function audit_log_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mk_audit_log';
	}

	/**
	 * Fully-prefixed name of the revisions table.
	 *
	 * @return string
	 */
	public static function revisions_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mk_revisions';
	}
}
