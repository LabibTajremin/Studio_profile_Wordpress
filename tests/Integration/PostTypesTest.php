<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Integration;

use WP_UnitTestCase;

/**
 * Verifies custom post types are actually registered against a real
 * WordPress instance, including the mk_service has_archive=false
 * deviation documented in CLAUDE.md (§3.2 — /services is a hand-built
 * Page, not a CPT archive; don't re-enable it).
 */
final class PostTypesTest extends WP_UnitTestCase {

	public function test_mk_project_is_registered_with_archive(): void {
		$this->assertTrue( post_type_exists( 'mk_project' ) );

		$post_type = get_post_type_object( 'mk_project' );
		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->has_archive );
		$this->assertArrayHasKey( 'editor', get_all_post_type_supports( 'mk_project' ) );
	}

	public function test_mk_service_is_registered_without_archive(): void {
		$this->assertTrue( post_type_exists( 'mk_service' ) );

		$post_type = get_post_type_object( 'mk_service' );
		$this->assertNotNull( $post_type );
		$this->assertFalse( $post_type->has_archive );
		$this->assertTrue( $post_type->hierarchical );
	}
}
