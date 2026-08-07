<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Integration;

use Maapkathi\Core\Support\Database;
use WP_UnitTestCase;

/**
 * Verifies the plugin's custom tables against a real WordPress + MariaDB
 * instance, rather than only the standalone smoke-test script (see
 * docs/BUILD_STATUS.md). The tables are created by Support\Migrations,
 * triggered here via Plugin::boot()'s Database::maybe_upgrade() call on
 * `plugins_loaded`, which the WP test bootstrap fires for every test.
 */
final class DatabaseTest extends WP_UnitTestCase {

	public function test_inquiries_table_exists_with_expected_columns(): void {
		global $wpdb;

		$table = Database::inquiries_table();
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );

		$columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );
		foreach ( array( 'id', 'name', 'email', 'phone', 'message', 'source', 'is_read', 'created_at' ) as $expected ) {
			$this->assertContains( $expected, $columns );
		}
	}

	public function test_audit_log_table_exists_with_expected_columns(): void {
		global $wpdb;

		$table = Database::audit_log_table();
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );

		$columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );
		foreach ( array( 'id', 'actor_id', 'action', 'entity', 'entity_id', 'diff', 'created_at' ) as $expected ) {
			$this->assertContains( $expected, $columns );
		}
	}

	public function test_revisions_table_exists_with_expected_columns(): void {
		global $wpdb;

		$table = Database::revisions_table();
		$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) );

		$columns = $wpdb->get_col( "DESCRIBE {$table}", 0 );
		foreach ( array( 'id', 'entity', 'entity_id', 'payload', 'status', 'submitted_by', 'reviewed_by', 'reviewed_at', 'note', 'created_at' ) as $expected ) {
			$this->assertContains( $expected, $columns );
		}
	}

	public function test_db_version_option_is_recorded(): void {
		$this->assertNotEmpty( get_option( Database::OPTION_VERSION ) );
	}
}
