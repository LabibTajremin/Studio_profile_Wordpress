<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Footer;

use Maapkathi\Core\Footer\FooterColor;
use Maapkathi\Core\Theme\Accents;
use PHPUnit\Framework\TestCase;

/**
 * FR-08.2 lets the admin point the footer at four very different
 * backgrounds, and FR-09.5 requires the copyright line to stay readable on
 * every one of them. Contrast is therefore asserted for each mode rather
 * than for the default alone.
 */
final class FooterColorTest extends TestCase {

	private const ACCENT     = '#6e1f2a';
	private const SURFACE    = '#f9f0e4';
	private const ON_SURFACE = '#171310';

	/**
	 * Resolves a footer settings fragment against fixed page colours.
	 *
	 * @param array<string,mixed> $overrides Footer settings to override.
	 * @return array{bg:string,fg:string,muted:string}
	 */
	private function resolve( array $overrides ): array {
		return FooterColor::resolve( $overrides, self::ACCENT, self::SURFACE, self::ON_SURFACE );
	}

	public function test_dark_is_the_default_surface(): void {
		$this->assertSame( FooterColor::DARK_NEUTRAL, $this->resolve( array() )['bg'] );
	}

	public function test_accent_mode_uses_the_accent(): void {
		$this->assertSame( self::ACCENT, $this->resolve( array( 'bg_mode' => 'accent' ) )['bg'] );
	}

	public function test_custom_mode_uses_the_hex(): void {
		$this->assertSame(
			'#0f172a',
			$this->resolve(
				array(
					'bg_mode' => 'custom',
					'bg_hex'  => '#0f172a',
				)
			)['bg']
		);
	}

	/**
	 * A custom mode with an unusable hex must not produce an empty or
	 * transparent footer — it falls back to the dark neutral.
	 */
	public function test_custom_mode_with_a_broken_hex_falls_back(): void {
		$this->assertSame(
			FooterColor::DARK_NEUTRAL,
			$this->resolve(
				array(
					'bg_mode' => 'custom',
					'bg_hex'  => 'not-a-colour',
				)
			)['bg']
		);
	}

	public function test_surface_mode_keeps_the_page_colours(): void {
		$resolved = $this->resolve( array( 'bg_mode' => 'surface' ) );
		$this->assertSame( self::SURFACE, $resolved['bg'] );
		$this->assertSame( self::ON_SURFACE, $resolved['fg'] );
	}

	/**
	 * AC-5 and FR-09.5: body text and the copyright line both have to clear
	 * 4.5:1 on every background the admin can pick.
	 */
	public function test_every_background_mode_stays_readable(): void {
		$modes = array(
			array( 'bg_mode' => 'dark' ),
			array( 'bg_mode' => 'accent' ),
			array( 'bg_mode' => 'surface' ),
			array(
				'bg_mode' => 'custom',
				'bg_hex'  => '#ffffff',
			),
			array(
				'bg_mode' => 'custom',
				'bg_hex'  => '#000000',
			),
			array(
				'bg_mode' => 'custom',
				'bg_hex'  => '#7f7f7f',
			),
		);

		foreach ( $modes as $mode ) {
			$resolved = $this->resolve( $mode );
			$label    = $mode['bg_mode'] . ( $mode['bg_hex'] ?? '' );

			$this->assertGreaterThanOrEqual(
				4.5,
				Accents::contrast_ratio( $resolved['bg'], $resolved['fg'] ),
				$label . ': footer text failed contrast'
			);
			$this->assertGreaterThanOrEqual(
				4.5,
				Accents::contrast_ratio( $resolved['bg'], $resolved['muted'] ),
				$label . ': copyright line failed contrast'
			);
		}
	}

	/**
	 * The muted shade exists to be visibly quieter than the body ink. If it
	 * ever resolved to the same value the distinction would be lost, which
	 * would be a silent regression rather than a visible one.
	 */
	public function test_muted_is_dimmer_than_the_body_ink(): void {
		$resolved = $this->resolve( array( 'bg_mode' => 'dark' ) );

		$this->assertNotSame( $resolved['fg'], $resolved['muted'] );
		$this->assertLessThan(
			Accents::contrast_ratio( $resolved['bg'], $resolved['fg'] ),
			Accents::contrast_ratio( $resolved['bg'], $resolved['muted'] )
		);
	}
}
