<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Theme;

use Maapkathi\Core\Theme\Motion;
use PHPUnit\Framework\TestCase;

final class MotionResolveVarsTest extends TestCase {

	private function input( string $preset, int $speed = 100, int $stagger = 70, int $parallax = 20 ): array {
		return array(
			'motionPreset'      => $preset,
			'motionSpeed'       => $speed,
			'staggerMs'         => $stagger,
			'parallaxIntensity' => $parallax,
		);
	}

	public function test_reduced_motion_collapses_everything(): void {
		$vars = Motion::resolve_vars( $this->input( 'expressive' ), true );

		$this->assertSame( '0ms', $vars['--motion-duration'] );
		$this->assertSame( 'linear', $vars['--motion-ease'] );
		$this->assertSame( '0px', $vars['--motion-distance'] );
		$this->assertSame( '0ms', $vars['--motion-stagger'] );
		$this->assertSame( '0', $vars['--parallax-intensity'] );
	}

	public function test_off_preset(): void {
		$vars = Motion::resolve_vars( $this->input( 'off' ), false );
		$this->assertSame( '0ms', $vars['--motion-duration'] );
		$this->assertSame( 'linear', $vars['--motion-ease'] );
	}

	public function test_refined_preset_at_100_percent_speed(): void {
		$vars = Motion::resolve_vars( $this->input( 'refined' ), false );
		$this->assertSame( '600ms', $vars['--motion-duration'] );
		$this->assertSame( '70ms', $vars['--motion-stagger'] );
	}

	public function test_motion_speed_scales_duration(): void {
		$vars = Motion::resolve_vars( $this->input( 'refined', 50 ), false );
		$this->assertSame( '300ms', $vars['--motion-duration'] );
	}

	public function test_custom_preset_uses_admin_stagger(): void {
		$vars = Motion::resolve_vars( $this->input( 'custom', 100, 200 ), false );
		$this->assertSame( '200ms', $vars['--motion-stagger'] );
		// custom borrows refined's duration/distance base.
		$this->assertSame( '600ms', $vars['--motion-duration'] );
	}

	public function test_stagger_ms_ignored_unless_preset_is_custom(): void {
		$vars = Motion::resolve_vars( $this->input( 'refined', 100, 250 ), false );
		$this->assertSame( '70ms', $vars['--motion-stagger'], 'stagger_ms must be ignored for non-custom presets' );
	}

	public function test_parallax_intensity_normalised_to_0_1(): void {
		$vars = Motion::resolve_vars( $this->input( 'refined', 100, 70, 40 ), false );
		$this->assertSame( '0.4', $vars['--parallax-intensity'] );
	}
}
