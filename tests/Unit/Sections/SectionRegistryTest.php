<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Sections;

use Maapkathi\Core\Sections\SectionRegistry;
use PHPUnit\Framework\TestCase;

/**
 * FR-02's load-bearing promise is that renaming a section changes only what
 * a visitor reads — anchors stay valid, and two sections on a page can
 * never end up sharing one, because that would silently make one of them
 * unreachable from a menu link.
 */
final class SectionRegistryTest extends TestCase {

	/**
	 * Every anchor assigned by a sanitise pass, keyed "page#anchor".
	 *
	 * @param array<string,array{subtitle:string,show_title:bool,anchor:string}> $values Sanitized values.
	 * @return string[]
	 */
	private function scoped_anchors( array $values ): array {
		$out = array();

		foreach ( SectionRegistry::all() as $id => $section ) {
			$out[] = $section['page'] . '#' . $values[ $id ]['anchor'];
		}

		return $out;
	}

	public function test_registry_ships_with_unique_anchors_per_page(): void {
		$scoped = array();

		foreach ( SectionRegistry::all() as $section ) {
			$scoped[] = $section['page'] . '#' . $section['anchor'];
		}

		$this->assertSame( count( $scoped ), count( array_unique( $scoped ) ) );
	}

	public function test_headings_are_shown_by_default(): void {
		foreach ( SectionRegistry::get() as $id => $row ) {
			$this->assertTrue( $row['show_title'], $id . ' should default to visible' );
		}
	}

	public function test_anchors_are_slugified(): void {
		$result = SectionRegistry::sanitize(
			array( 'clients' => array( 'anchor' => 'Our CLIENTS!! 2024' ) )
		);

		$this->assertSame( 'our-clients-2024', $result['values']['clients']['anchor'] );
	}

	public function test_an_emptied_anchor_falls_back_to_the_default(): void {
		$result = SectionRegistry::sanitize(
			array( 'clients' => array( 'anchor' => '' ) )
		);

		$this->assertSame( 'clients', $result['values']['clients']['anchor'] );
	}

	public function test_unchecking_show_title_stores_a_real_false(): void {
		$result = SectionRegistry::sanitize(
			array( 'clients' => array( 'show_title' => '0' ) )
		);

		$this->assertFalse( $result['values']['clients']['show_title'] );
	}

	/**
	 * Taking another section's anchor must be reported AND resolved. The
	 * naive fallbacks — the previously saved anchor, or the registry
	 * default — are both unsafe here, because the section that took this
	 * anchor may have taken it from precisely those.
	 */
	public function test_a_stolen_anchor_never_leaves_two_sections_sharing_one(): void {
		$result = SectionRegistry::sanitize(
			array( 'clients' => array( 'anchor' => 'work' ) )
		);

		$this->assertNotEmpty( $result['errors'], 'the collision should be reported' );

		$scoped = $this->scoped_anchors( $result['values'] );
		$this->assertSame(
			count( $scoped ),
			count( array_unique( $scoped ) ),
			'two sections ended up sharing an anchor'
		);
	}

	/**
	 * Uniqueness is scoped to the page: two sections on different templates
	 * sharing an anchor breaks nothing, and forcing them apart would be
	 * gratuitous.
	 */
	public function test_sections_on_different_pages_may_share_an_anchor(): void {
		$result = SectionRegistry::sanitize(
			array(
				'clients'    => array( 'anchor' => 'intro' ),
				'about_page' => array( 'anchor' => 'intro' ),
			)
		);

		$this->assertSame( 'intro', $result['values']['clients']['anchor'] );
		$this->assertSame( 'intro', $result['values']['about_page']['anchor'] );
		$this->assertArrayNotHasKey( 'about_page', $result['errors'] );
	}

	/**
	 * Even several sections all demanding the same anchor must come out
	 * distinct rather than the suffix logic stopping after one attempt.
	 */
	public function test_many_collisions_all_resolve(): void {
		$result = SectionRegistry::sanitize(
			array(
				'clients'      => array( 'anchor' => 'same' ),
				'categories'   => array( 'anchor' => 'same' ),
				'values'       => array( 'anchor' => 'same' ),
				'testimonials' => array( 'anchor' => 'same' ),
			)
		);

		$scoped = $this->scoped_anchors( $result['values'] );
		$this->assertSame( count( $scoped ), count( array_unique( $scoped ) ) );
	}

	/**
	 * FR-02.4: the section's identity must not move when its title does.
	 */
	public function test_section_ids_are_independent_of_titles(): void {
		$before = SectionRegistry::ids();

		SectionRegistry::sanitize(
			array( 'clients' => array( 'anchor' => 'clients' ) )
		);

		$this->assertSame( $before, SectionRegistry::ids() );
	}
}
