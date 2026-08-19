<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Theme;

use Maapkathi\Core\Theme\Accents;
use Maapkathi\Core\Theme\HeaderColor;
use Maapkathi\Core\Theme\HexColor;
use PHPUnit\Framework\TestCase;

/**
 * FR-01.5 lays down one exact priority order for the header colour and
 * explicitly rules out any other interpretation, so each of its four steps
 * gets its own test rather than one combined happy-path assertion.
 */
final class HeaderColorTest extends TestCase {

	private const ACCENT = '#6e1f2a';

	/**
	 * @param array<string,mixed> $overrides Settings to override on top of the defaults.
	 * @return array<string,mixed>
	 */
	private function settings( array $overrides = array() ): array {
		return array_merge(
			array(
				'header_follow_accent' => true,
				'header_palette_id'    => '',
				'header_hex'           => null,
			),
			$overrides
		);
	}

	public function test_rule_1_follow_accent_beats_hex_and_palette(): void {
		$resolved = HeaderColor::resolve(
			$this->settings(
				array(
					'header_follow_accent' => true,
					'header_hex'           => '#0f172a',
					'header_palette_id'    => 'sage',
				)
			),
			self::ACCENT
		);

		$this->assertSame( self::ACCENT, $resolved['bg'] );
		$this->assertSame( 'accent', $resolved['source'] );
	}

	public function test_rule_2_hex_beats_palette(): void {
		$resolved = HeaderColor::resolve(
			$this->settings(
				array(
					'header_follow_accent' => false,
					'header_hex'           => '#0f172a',
					'header_palette_id'    => 'sage',
				)
			),
			self::ACCENT
		);

		$this->assertSame( '#0f172a', $resolved['bg'] );
		$this->assertSame( 'hex', $resolved['source'] );
	}

	public function test_rule_3_palette_applies_when_hex_is_empty(): void {
		$sage     = Accents::by_id( 'sage' );
		$resolved = HeaderColor::resolve(
			$this->settings(
				array(
					'header_follow_accent' => false,
					'header_hex'           => '',
					'header_palette_id'    => 'sage',
				)
			),
			self::ACCENT
		);

		$this->assertSame( $sage['light'], $resolved['bg'] );
		$this->assertSame( 'palette', $resolved['source'] );
	}

	public function test_rule_4_falls_back_to_accent_never_to_empty(): void {
		$resolved = HeaderColor::resolve(
			$this->settings( array( 'header_follow_accent' => false ) ),
			self::ACCENT
		);

		$this->assertSame( self::ACCENT, $resolved['bg'] );
		$this->assertSame( 'fallback', $resolved['source'] );
	}

	/**
	 * AC-4: clearing the hex must return the header to the swatch, not to
	 * white and not to transparent.
	 */
	public function test_clearing_hex_returns_to_swatch(): void {
		$with_hex = HeaderColor::resolve(
			$this->settings(
				array(
					'header_follow_accent' => false,
					'header_hex'           => '#0f172a',
					'header_palette_id'    => 'deep-teal',
				)
			),
			self::ACCENT
		);
		$cleared  = HeaderColor::resolve(
			$this->settings(
				array(
					'header_follow_accent' => false,
					'header_hex'           => null,
					'header_palette_id'    => 'deep-teal',
				)
			),
			self::ACCENT
		);

		$this->assertSame( '#0f172a', $with_hex['bg'] );
		$this->assertSame( Accents::by_id( 'deep-teal' )['light'], $cleared['bg'] );
	}

	public function test_dark_mode_uses_the_swatch_dark_variant(): void {
		$resolved = HeaderColor::resolve(
			$this->settings(
				array(
					'header_follow_accent' => false,
					'header_palette_id'    => 'sage',
				)
			),
			'#8f2d3b',
			true
		);

		$this->assertSame( Accents::by_id( 'sage' )['dark'], $resolved['bg'] );
	}

	/**
	 * AC-6: the foreground has to clear 4.5:1 at both ends of the range,
	 * which is the whole point of deriving it rather than letting the admin
	 * pick a header colour that swallows its own menu.
	 */
	public function test_foreground_is_readable_on_extreme_headers(): void {
		foreach ( array( '#ffffff', '#000000', '#7f7f7f' ) as $bg ) {
			$resolved = HeaderColor::resolve(
				$this->settings(
					array(
						'header_follow_accent' => false,
						'header_hex'           => $bg,
					)
				),
				self::ACCENT
			);

			$this->assertGreaterThanOrEqual(
				4.5,
				Accents::contrast_ratio( $resolved['bg'], $resolved['fg'] ),
				$bg . ' header did not produce a readable foreground'
			);
		}
	}

	public function test_is_custom_is_false_while_following_the_accent(): void {
		$this->assertFalse( HeaderColor::is_custom( $this->settings() ) );
		$this->assertFalse(
			HeaderColor::is_custom( $this->settings( array( 'header_follow_accent' => false ) ) ),
			'nothing configured is not a custom colour'
		);
		$this->assertTrue(
			HeaderColor::is_custom(
				$this->settings(
					array(
						'header_follow_accent' => false,
						'header_hex'           => '#123456',
					)
				)
			)
		);
	}

	public function test_palette_offers_at_least_the_20_swatches_the_spec_requires(): void {
		$this->assertGreaterThanOrEqual( 20, count( HeaderColor::palette() ) );
	}

	/**
	 * FR-01.6: the spellings a human actually types all have to work, and
	 * anything else must normalise to null so the priority rule treats it as
	 * "not set" rather than writing a broken colour.
	 */
	public function test_hex_normalisation_accepts_every_documented_spelling(): void {
		$this->assertSame( '#aabbcc', HexColor::normalize( '#abc' ) );
		$this->assertSame( '#aabbcc', HexColor::normalize( 'abc' ) );
		$this->assertSame( '#0f172a', HexColor::normalize( '#0F172A' ) );
		$this->assertSame( '#0f172a', HexColor::normalize( '  0f172a  ' ) );
	}

	public function test_hex_normalisation_rejects_nonsense(): void {
		foreach ( array( '12xyz', '#12345', 'red', '', '#', null, 42 ) as $bad ) {
			$this->assertNull( HexColor::normalize( $bad ), var_export( $bad, true ) . ' should not parse' );
		}
	}

	/**
	 * A 3-digit shorthand that is expanded wrongly (#abc -> #ab0000) reads as
	 * a completely different colour and quietly breaks the contrast maths, so
	 * the expansion is pinned here.
	 */
	public function test_shorthand_expansion_preserves_every_channel(): void {
		$this->assertSame( '#00aaff', HexColor::normalize( '#0af' ) );
		$this->assertSame( '#ffffff', HexColor::normalize( 'fff' ) );
		$this->assertSame( '#000000', HexColor::normalize( '000' ) );
	}
}
