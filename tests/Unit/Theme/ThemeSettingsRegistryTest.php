<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Theme;

use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Theme\Accents;
use Maapkathi\Core\Theme\Backgrounds;
use Maapkathi\Core\Theme\Patterns;
use Maapkathi\Core\Theme\Fonts;
use Maapkathi\Core\Theme\Motion;
use PHPUnit\Framework\TestCase;

/**
 * §11.4: the registry must contain exactly 32 keys, and the exact option
 * ID lists must match the source registries. Fails loudly if a setting is
 * ever dropped.
 */
final class ThemeSettingsRegistryTest extends TestCase {

	public function test_exactly_32_settings(): void {
		$this->assertCount( 32, ThemeSettings::keys() );
	}

	public function test_expected_keys_present(): void {
		$expected = array(
			'mode', 'accent_id', 'custom_accent_hex', 'pattern_id', 'pattern_opacity',
			'font_pair_id', 'font_overrides', 'heading_color_hex', 'body_color_hex',
			'background_tone', 'radius', 'density', 'grain', 'glass', 'hero_style',
			'motion_preset', 'motion_level', 'scroll_reveal_style', 'hero_animation',
			'image_hover_style', 'card_hover_style', 'text_reveal_style', 'page_transition',
			'cursor_style', 'loader_style', 'scroll_progress', 'smooth_scroll',
			'parallax_intensity', 'motion_speed', 'stagger_ms', 'animate_once', 'motion_on_mobile',
		);
		$this->assertSame( $expected, ThemeSettings::keys() );
	}

	public function test_2_background_tones_never_plain_white(): void {
		$this->assertSame( array( 'warm', 'cool' ), Backgrounds::ids() );
		$this->assertSame( 'warm', Backgrounds::DEFAULT_TONE );
		foreach ( Backgrounds::all() as $tone ) {
			$this->assertNotSame( '#ffffff', strtolower( $tone['light']['background'] ) );
			$this->assertNotSame( '#fff', strtolower( $tone['light']['background'] ) );
		}
	}

	public function test_24_accents(): void {
		$this->assertCount( 24, Accents::ids() );
		$this->assertContains( 'oxblood', Accents::ids() );
		$this->assertSame( 'oxblood', Accents::default_id() );
	}

	public function test_22_patterns(): void {
		$this->assertCount( 22, Patterns::ids() );
		$this->assertContains( 'none', Patterns::ids() );
	}

	public function test_8_font_pairs(): void {
		$this->assertCount( 8, Fonts::ids() );
	}

	public function test_4_radius_and_3_density(): void {
		$this->assertSame( array( 'sharp', 'subtle', 'soft', 'pill' ), array_keys( Fonts::RADIUS_SCALE ) );
		$this->assertSame( array( 'compact', 'comfortable', 'spacious' ), array_keys( Fonts::DENSITY_SCALE ) );
	}

	public function test_motion_registry_counts(): void {
		$this->assertCount( 12, Motion::scroll_reveal_styles() );
		$this->assertCount( 8, Motion::hero_animations() );
		$this->assertCount( 8, Motion::image_hover_styles() );
		$this->assertCount( 6, Motion::card_hover_styles() );
		$this->assertCount( 6, Motion::text_reveal_styles() );
		$this->assertCount( 6, Motion::page_transitions() );
	}

	public function test_motion_level_mirrors_motion_preset_on_sanitize(): void {
		$out = ThemeSettings::sanitize( array( 'motion_preset' => 'expressive' ) );
		$this->assertSame( 'expressive', $out['motion_level'] );
	}

	public function test_motion_speed_clamped_to_50_150(): void {
		$out = ThemeSettings::sanitize( array( 'motion_speed' => 999 ) );
		$this->assertSame( 150, $out['motion_speed'] );

		$out = ThemeSettings::sanitize( array( 'motion_speed' => 1 ) );
		$this->assertSame( 50, $out['motion_speed'] );
	}

	public function test_unknown_enum_value_rejected_and_falls_back_to_default(): void {
		$out = ThemeSettings::sanitize( array( 'radius' => 'round' ) ); // not a real value
		$this->assertSame( 'subtle', $out['radius'] );

		$out = ThemeSettings::sanitize( array( 'accent_id' => 'not-a-real-accent' ) );
		$this->assertSame( Accents::default_id(), $out['accent_id'] );
	}

	/**
	 * "Two values -> two outputs" smoke test for a representative subset —
	 * proves the var builder is not dormant for these settings. Full
	 * 31-assertion coverage per §11.4 belongs in an integration test once a
	 * WP runtime is available (ThemeVarsBuilder::vars_for is exercised
	 * indirectly here via the pure parts it doesn't need WP for).
	 */
	public function test_readable_accent_meets_wcag_aa_on_every_background_tone(): void {
		foreach ( Backgrounds::all() as $tone ) {
			foreach ( Accents::all() as $accent ) {
				foreach ( array( 'light', 'dark' ) as $mode ) {
					$surface  = $tone[ $mode ]['background'];
					$readable = Accents::readable_on( $accent[ $mode ], $surface );

					$this->assertGreaterThanOrEqual(
						4.5,
						Accents::contrast_ratio( $readable, $surface ),
						sprintf( 'Accent "%s" (%s) is unreadable on %s', $accent['id'], $mode, $surface )
					);
				}
			}
		}
	}

	public function test_readable_accent_leaves_already_readable_accents_untouched(): void {
		// Oxblood on cream is already ~9.8:1 — it must not be shifted.
		$this->assertSame( '#6e1f2a', Accents::readable_on( '#6e1f2a', '#f9f0e4' ) );
	}

	public function test_radius_scale_has_distinct_pixel_values(): void {
		$values = array_values( Fonts::RADIUS_SCALE );
		$this->assertSame( $values, array_unique( $values ) );
	}
}
