<?php
/**
 * Reordering the homepage by ordinal from the Appearance screen.
 *
 * The screen posts a number per section rather than a sorted list, so the
 * behaviour worth pinning is the sort: that a smaller number moves a
 * section up, that ties do not shuffle, and that nothing is lost on the way
 * through.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Tests\Unit\Sections;

use Maapkathi\Core\Sections\SectionLayout;
use PHPUnit\Framework\TestCase;

final class SectionOrdinalTest extends TestCase {

	/**
	 * Mirrors Menu::save_section_order(), which is private and reads $_POST.
	 *
	 * @param array<string,string> $types    id => type.
	 * @param array<string,int>    $orders   id => ordinal.
	 * @param array<string,int>    $switches id => 1|0.
	 * @return array<int,array{id:string,type:string,enabled:bool}>
	 */
	private function reorder( array $types, array $orders, array $switches ): array {
		$rows     = array();
		$position = 0;

		foreach ( $types as $id => $type ) {
			$id      = (string) $id;
			$ordinal = ( 'hero' === $type )
				? 0
				: ( isset( $orders[ $id ] ) ? max( 1, min( 999, absint( $orders[ $id ] ) ) ) : 999 );

			$rows[] = array(
				'id'       => $id,
				'type'     => (string) $type,
				'enabled'  => ! empty( $switches[ $id ] ),
				'_ordinal' => $ordinal,
				'_seq'     => $position++,
			);
		}

		usort(
			$rows,
			static function ( array $a, array $b ): int {
				return array( $a['_ordinal'], $a['_seq'] ) <=> array( $b['_ordinal'], $b['_seq'] );
			}
		);

		$layout = array();
		foreach ( $rows as $row ) {
			$layout[] = array(
				'id'      => $row['id'],
				'type'    => $row['type'],
				'enabled' => $row['enabled'],
			);
		}

		return SectionLayout::sanitize( $layout );
	}

	/**
	 * The screen's starting state: every section on, numbered 1..N.
	 *
	 * @return array{0:array<string,string>,1:array<string,int>,2:array<string,int>}
	 */
	private function initial(): array {
		$types    = array();
		$orders   = array();
		$switches = array();
		$n        = 0;

		foreach ( SectionLayout::defaults() as $row ) {
			$types[ $row['id'] ]    = $row['type'];
			$orders[ $row['id'] ]   = ++$n;
			$switches[ $row['id'] ] = 1;
		}

		return array( $types, $orders, $switches );
	}

	/**
	 * Ids of the sections the admin can actually move.
	 *
	 * @param array<string,string> $types id => type.
	 * @return string[]
	 */
	private function movable( array $types ): array {
		return array_values(
			array_filter(
				array_keys( $types ),
				static fn( string $id ): bool => 'hero' !== $types[ $id ]
			)
		);
	}

	public function test_saving_without_changing_a_number_changes_nothing(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$this->assertSame(
			array_column( SectionLayout::defaults(), 'id' ),
			array_column( $this->reorder( $types, $orders, $switches ), 'id' )
		);
	}

	public function test_a_smaller_number_moves_a_section_up(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$last             = (string) array_key_last( $orders );
		$orders[ $last ]  = 1;
		$layout           = $this->reorder( $types, $orders, $switches );
		$movable_in_order = array_values(
			array_filter( array_column( $layout, 'id' ), static fn( string $id ): bool => 'hero' !== $types[ $id ] )
		);

		$this->assertSame( $last, $movable_in_order[0] );
	}

	public function test_hero_stays_first_even_when_something_else_is_number_one(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$orders[ (string) array_key_last( $orders ) ] = 1;

		$this->assertSame( 'hero', $this->reorder( $types, $orders, $switches )[0]['type'] );
	}

	public function test_swapping_two_numbers_swaps_the_two_sections(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$movable = $this->movable( $types );
		[ $a, $b ] = array( $movable[0], $movable[1] );

		$swapped        = $orders;
		$swapped[ $a ]  = $orders[ $b ];
		$swapped[ $b ]  = $orders[ $a ];
		$layout         = $this->reorder( $types, $swapped, $switches );
		$result         = array_values(
			array_filter( array_column( $layout, 'id' ), static fn( string $id ): bool => 'hero' !== $types[ $id ] )
		);

		$this->assertSame( $b, $result[0] );
		$this->assertSame( $a, $result[1] );
	}

	/**
	 * Equal numbers are legal, and must not make the page reshuffle itself
	 * every time the admin presses Save.
	 */
	public function test_equal_numbers_keep_the_current_order_and_are_stable(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$tied  = array_map( static fn(): int => 5, $orders );
		$once  = $this->reorder( $types, $tied, $switches );
		$twice = $this->reorder( $types, $tied, $switches );

		$this->assertSame( array_column( $once, 'id' ), array_column( $twice, 'id' ) );
		$this->assertSame( array_column( SectionLayout::defaults(), 'id' ), array_column( $once, 'id' ) );
	}

	public function test_numbers_need_not_be_consecutive(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$spaced = array();
		$n      = 0;
		foreach ( array_keys( $orders ) as $id ) {
			$spaced[ $id ] = 1000 - ( ++$n * 7 );
		}

		$layout   = $this->reorder( $types, $spaced, $switches );
		$result   = array_values(
			array_filter( array_column( $layout, 'id' ), static fn( string $id ): bool => 'hero' !== $types[ $id ] )
		);
		$expected = array_reverse( $this->movable( $types ) );

		$this->assertSame( $expected, $result );
	}

	public function test_switching_a_section_off_leaves_the_others_on(): void {
		[ $types, $orders, $switches ] = $this->initial();

		$movable            = $this->movable( $types );
		$off                = $movable[2];
		$switches[ $off ]   = 0;
		$layout             = $this->reorder( $types, $orders, $switches );

		$enabled = array();
		foreach ( $layout as $row ) {
			$enabled[ $row['id'] ] = $row['enabled'];
		}

		$this->assertFalse( $enabled[ $off ] );
		$this->assertTrue( $enabled[ $movable[0] ] );
		$this->assertTrue( $enabled[ $movable[1] ] );
	}

	public function test_no_section_is_lost_or_duplicated_whatever_the_numbers(): void {
		[ $types, $orders, $switches ] = $this->initial();

		foreach ( array( 0, 1, 999, 100000 ) as $value ) {
			$flat   = array_map( static fn(): int => $value, $orders );
			$layout = $this->reorder( $types, $flat, $switches );
			$ids    = array_column( $layout, 'id' );

			$this->assertCount( count( SectionLayout::defaults() ), $layout, "ordinal $value" );
			$this->assertSame( $ids, array_unique( $ids ), "ordinal $value" );
		}
	}
}
