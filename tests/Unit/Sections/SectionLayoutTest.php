<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Sections;

use Maapkathi\Core\Sections\SectionLayout;
use Maapkathi\Core\Sections\SectionRegistry;
use PHPUnit\Framework\TestCase;

/**
 * FR-03's riskiest promise is that a duplicated section is genuinely
 * independent — its own id, its own anchor, its own record — because a
 * shared one would mean editing the copy silently edited the original.
 */
final class SectionLayoutTest extends TestCase {

	public function test_defaults_are_one_enabled_instance_of_each_homepage_type(): void {
		$defaults = SectionLayout::defaults();
		$types    = SectionRegistry::all();

		$this->assertNotEmpty( $defaults );

		foreach ( $defaults as $row ) {
			$this->assertTrue( $row['enabled'] );
			$this->assertSame( $row['type'], $row['id'], 'the first instance keeps the type as its id' );
			$this->assertSame( 'home', $types[ $row['type'] ]['page'] );
		}
	}

	public function test_order_is_preserved(): void {
		$reversed = array_reverse( SectionLayout::defaults() );

		$this->assertSame(
			array_column( $reversed, 'type' ),
			array_column( SectionLayout::sanitize( $reversed ), 'type' )
		);
	}

	/**
	 * The browser generates ids for new rows, so the server has to assume
	 * they may collide and fix them rather than trust them.
	 */
	public function test_colliding_ids_are_made_unique(): void {
		$rows = array(
			array(
				'id'      => 'services',
				'type'    => 'services',
				'enabled' => true,
			),
			array(
				'id'      => 'services',
				'type'    => 'services',
				'enabled' => true,
			),
			array(
				'id'      => 'services',
				'type'    => 'services',
				'enabled' => true,
			),
		);

		$layout = SectionLayout::sanitize( $rows );

		$this->assertCount( 3, $layout );
		$this->assertCount( 3, array_unique( array_column( $layout, 'id' ) ) );
	}

	public function test_unknown_and_inner_page_types_are_refused(): void {
		$unknown = SectionLayout::sanitize(
			array(
				array(
					'id'      => 'x',
					'type'    => 'not_a_type',
					'enabled' => true,
				),
			)
		);
		$this->assertSame( count( SectionLayout::defaults() ), count( $unknown ), 'an empty result falls back to defaults' );

		$inner = SectionLayout::sanitize(
			array(
				array(
					'id'      => 'about_page',
					'type'    => 'about_page',
					'enabled' => true,
				),
			)
		);
		$this->assertNotContains( 'about_page', array_column( $inner, 'type' ) );
	}

	/**
	 * FR-03.6: a core section can always be switched off, but its first
	 * instance cannot be removed — otherwise a site could lose its hero
	 * with no route back except editing the database.
	 */
	public function test_core_sections_are_protected_only_in_their_first_instance(): void {
		$layout = array(
			array(
				'id'      => 'services',
				'type'    => 'services',
				'enabled' => true,
			),
			array(
				'id'      => 'services-abc123',
				'type'    => 'services',
				'enabled' => false,
			),
			array(
				'id'      => 'awards',
				'type'    => 'awards',
				'enabled' => true,
			),
		);

		$this->assertFalse( SectionLayout::is_deletable( 'services', $layout ) );
		$this->assertTrue( SectionLayout::is_deletable( 'services-abc123', $layout ) );
		$this->assertTrue( SectionLayout::is_deletable( 'awards', $layout ) );
	}

	public function test_new_ids_avoid_the_ones_already_in_use(): void {
		$taken = array( 'services', 'services-aaaaaa' );

		for ( $i = 0; $i < 25; $i++ ) {
			$id = SectionLayout::new_id( 'services', $taken );

			$this->assertNotContains( $id, $taken );
			$this->assertStringStartsWith( 'services-', $id );

			$taken[] = $id;
		}
	}
}
