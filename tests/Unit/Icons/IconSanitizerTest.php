<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Icons;

use Maapkathi\Core\Icons\IconLibrary;
use Maapkathi\Core\Icons\IconRenderer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * An uploaded SVG is inlined into the page rather than sandboxed in an
 * <img>, so everything executable has to be gone before it gets there. The
 * same pass also strips hardcoded colours, because an icon that keeps its
 * own fill silently ignores the accent (FR-06.4).
 */
final class IconSanitizerTest extends TestCase {

	/**
	 * Runs the private sanitiser.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string Sanitized markup.
	 */
	private function clean( string $svg ): string {
		$method = new ReflectionMethod( IconRenderer::class, 'sanitize_svg' );
		$method->setAccessible( true );

		return (string) $method->invoke( null, $svg );
	}

	/**
	 * One deliberately nasty file carrying every vector at once, so a
	 * regression in any single rule fails here rather than in production.
	 *
	 * @return string
	 */
	private function hostile_svg(): string {
		return '<?xml version="1.0"?><!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 24 24">'
			. '<script>alert(1)</script>'
			. '<style>path{fill:red}</style>'
			. '<a href="javascript:alert(2)"><path d="M1 1" fill="#333" onclick="alert(3)" style="fill:blue"/></a>'
			. '<use xlink:href="http://evil.example/x.svg#y"/>'
			. '<path d="M2 2" stroke="#abcdef" fill="none"/>'
			. '<foreignObject><body>hi</body></foreignObject>'
			. '</svg>';
	}

	public function test_executable_content_is_removed(): void {
		$out = $this->clean( $this->hostile_svg() );

		$this->assertStringNotContainsStringIgnoringCase( '<script', $out );
		$this->assertStringNotContainsString( 'alert(1)', $out );
		$this->assertStringNotContainsStringIgnoringCase( '<style', $out );
		$this->assertStringNotContainsStringIgnoringCase( '<use', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'foreignobject', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'onclick', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'href=', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'javascript:', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'style=', $out );
	}

	public function test_hardcoded_colours_become_currentcolor(): void {
		$out = $this->clean( $this->hostile_svg() );

		$this->assertStringNotContainsString( '#333', $out );
		$this->assertStringNotContainsString( '#abcdef', $out );
		$this->assertGreaterThanOrEqual( 2, substr_count( $out, 'currentColor' ) );
	}

	/**
	 * fill="none" is a shape instruction, not a colour choice — rewriting
	 * it to currentColor would flood-fill outline icons solid.
	 */
	public function test_fill_none_survives(): void {
		$this->assertStringContainsString( 'fill="none"', $this->clean( $this->hostile_svg() ) );
	}

	/**
	 * Baked-in width/height would beat the admin's size setting, but
	 * viewBox has to stay or the drawing loses its coordinate system.
	 */
	public function test_dimensions_are_stripped_but_viewbox_is_kept(): void {
		$out = $this->clean( $this->hostile_svg() );

		$this->assertStringNotContainsStringIgnoringCase( 'width=', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'height=', $out );
		$this->assertStringContainsStringIgnoringCase( 'viewbox', $out );
	}

	public function test_drawing_content_survives(): void {
		$out = $this->clean( $this->hostile_svg() );

		$this->assertGreaterThanOrEqual( 2, substr_count( $out, '<path' ) );
		$this->assertStringContainsString( 'mk-icon--upload', $out );
		$this->assertStringNotContainsString( '<?xml', $out );
		$this->assertStringNotContainsStringIgnoringCase( 'doctype', $out );
	}

	public function test_a_file_that_is_not_an_svg_yields_nothing(): void {
		$this->assertSame( '', $this->clean( 'not an svg at all' ) );
	}

	/**
	 * FR-07.5: one library, shared by both sections. If the two ever drew
	 * from different sets the site's icon style would quietly diverge.
	 */
	public function test_library_icons_all_draw_with_currentcolor(): void {
		foreach ( IconLibrary::ids() as $id ) {
			$svg = IconLibrary::svg( $id );

			$this->assertStringContainsString( 'stroke="currentColor"', $svg, $id );
			$this->assertStringContainsString( 'fill="none"', $svg, $id );
		}
	}

	/**
	 * A size of 0 means "let CSS decide", which is how the admin's size
	 * setting reaches the icon at all.
	 */
	public function test_zero_size_omits_the_dimension_attributes(): void {
		$svg = IconLibrary::svg( 'compass', 0 );

		$this->assertStringNotContainsString( 'width=', $svg );
		$this->assertStringNotContainsString( 'height=', $svg );
	}

	public function test_unknown_icon_ids_render_nothing(): void {
		$this->assertSame( '', IconLibrary::svg( 'no-such-icon' ) );
		$this->assertFalse( IconLibrary::has( 'no-such-icon' ) );
	}
}
