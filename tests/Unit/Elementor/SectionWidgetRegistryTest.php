<?php
/**
 * The theme sections published as Elementor widgets.
 *
 * Elementor itself is not a test dependency, so these assert the wiring
 * that must hold before Elementor is ever loaded: which sections are
 * offered, that each has a partial to render, and that each has a concrete
 * class Elementor can resolve a placed widget back to.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Tests\Unit\Elementor;

use Maapkathi\Core\Elementor\Support;
use Maapkathi\Core\Sections\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class SectionWidgetRegistryTest extends TestCase {

	private function theme_dir(): string {
		return dirname( __DIR__, 3 ) . '/themes/maapkathi-theme';
	}

	private function widget_dir(): string {
		return dirname( __DIR__, 3 ) . '/plugins/maapkathi-core/src/Elementor/Widgets';
	}

	public function test_every_offered_section_has_a_partial_to_render(): void {
		$types = Support::widget_types();

		$this->assertNotEmpty( $types, 'At least one section must be available in the builder.' );

		foreach ( array_keys( $types ) as $type ) {
			$this->assertFileExists(
				$this->theme_dir() . '/parts/sections/' . $type . '.php',
				sprintf( 'Widget "%s" is offered but has no partial.', $type )
			);
		}
	}

	public function test_every_offered_section_has_a_concrete_widget_class(): void {
		foreach ( array_keys( Support::widget_types() ) as $type ) {
			$this->assertFileExists(
				$this->widget_dir() . '/' . Support::class_suffix( $type ) . 'Widget.php',
				sprintf( 'Widget "%s" has no class; Elementor could not resolve it.', $type )
			);
		}
	}

	/**
	 * The reverse direction: a class with no partial would register a
	 * widget that silently renders nothing.
	 */
	public function test_no_widget_class_points_at_a_missing_partial(): void {
		foreach ( (array) glob( $this->widget_dir() . '/*Widget.php' ) as $file ) {
			$name = basename( (string) $file );

			if ( 'SectionWidget.php' === $name ) {
				continue;
			}

			$source = (string) file_get_contents( (string) $file );

			$this->assertSame(
				1,
				preg_match( "/return '([a-z_]+)';/", $source, $m ),
				$name . ' must declare its section type.'
			);
			$this->assertFileExists( $this->theme_dir() . '/parts/sections/' . $m[1] . '.php', $name );

			// Elementor maps a placed widget back to a class by name, so the
			// class name and the file name cannot drift apart.
			$this->assertSame( 1, preg_match( '/final class (\w+) extends/', $source, $c ), $name );
			$this->assertSame( $name, $c[1] . '.php' );
		}
	}

	/**
	 * The hero is full-viewport by contract and renders above the content
	 * region (§11.5), so it must not be droppable mid-page.
	 */
	public function test_hero_is_not_offered_as_a_widget(): void {
		$this->assertArrayNotHasKey( 'hero', Support::widget_types() );
	}

	/**
	 * The registry also holds page headings (About, Contact, the work
	 * archive). They are titles for existing templates, not blocks.
	 */
	public function test_page_heading_entries_are_not_offered(): void {
		$types = Support::widget_types();

		foreach ( SectionRegistry::all() as $type => $section ) {
			if ( 'home' !== $section['page'] ) {
				$this->assertArrayNotHasKey( $type, $types, $type );
			}
		}
	}

	public function test_support_is_inert_without_elementor(): void {
		$this->assertFalse( Support::is_available() );
	}

	/**
	 * @dataProvider type_names
	 */
	public function test_class_suffix_is_stable( string $type, string $expected ): void {
		$this->assertSame( $expected, Support::class_suffix( $type ) );
	}

	/**
	 * @return array<int,array{0:string,1:string}>
	 */
	public static function type_names(): array {
		return array(
			array( 'cta', 'Cta' ),
			array( 'faq', 'Faq' ),
			array( 'testimonials', 'Testimonials' ),
			array( 'work_archive', 'WorkArchive' ),
		);
	}
}
