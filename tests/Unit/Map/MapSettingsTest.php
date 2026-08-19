<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Map;

use Maapkathi\Core\Map\MapSettings;
use PHPUnit\Framework\TestCase;

/**
 * The map has two rules that are easy to get subtly wrong and hard to
 * notice afterwards: coordinates must beat an address (FR-05.4), and an
 * unconfigured map must render nothing rather than an empty grey frame
 * (FR-05.5). Both are pinned here, along with the keyless fallback that
 * keeps a missing API key away from visitors (FR-05.8).
 */
final class MapSettingsTest extends TestCase {

	/**
	 * Builds a full sanitized settings payload from a fragment.
	 *
	 * @param array<string,mixed> $overrides Settings to override.
	 * @return array<string,mixed>
	 */
	private function settings( array $overrides = array() ): array {
		return MapSettings::sanitize( array_merge( MapSettings::defaults(), $overrides ) );
	}

	public function test_an_unconfigured_map_renders_nothing(): void {
		$settings = $this->settings();

		$this->assertFalse( MapSettings::is_configured( $settings ) );
		$this->assertSame( '', MapSettings::embed_url( $settings ) );
		$this->assertSame( '', MapSettings::directions_url( $settings ) );
	}

	public function test_coordinates_beat_an_address(): void {
		$settings = $this->settings(
			array(
				'address' => 'House 42, Dhaka',
				'lat'     => '23.7806',
				'lng'     => '90.4074',
			)
		);

		$this->assertSame( '23.7806,90.4074', MapSettings::query( $settings ) );
	}

	public function test_an_address_alone_is_enough(): void {
		$settings = $this->settings( array( 'address' => 'House 42, Dhaka' ) );

		$this->assertTrue( MapSettings::is_configured( $settings ) );
		$this->assertSame( 'House 42, Dhaka', MapSettings::query( $settings ) );
	}

	/**
	 * Half a coordinate pair is not a location — centring on it would put
	 * the marker somewhere arbitrary rather than nowhere.
	 */
	public function test_one_coordinate_alone_is_not_a_location(): void {
		$this->assertFalse( MapSettings::is_configured( $this->settings( array( 'lat' => '23.78' ) ) ) );
		$this->assertFalse( MapSettings::is_configured( $this->settings( array( 'lng' => '90.40' ) ) ) );
	}

	public function test_google_js_without_a_key_falls_back_to_the_keyless_embed(): void {
		$url = MapSettings::embed_url(
			$this->settings(
				array(
					'provider' => 'google-js',
					'address'  => 'Dhaka',
				)
			)
		);

		$this->assertStringContainsString( 'maps.google.com/maps', $url );
		$this->assertStringContainsString( 'output=embed', $url );
	}

	public function test_openstreetmap_uses_a_bounding_box(): void {
		$url = MapSettings::embed_url(
			$this->settings(
				array(
					'provider' => 'osm',
					'lat'      => '23.7806',
					'lng'      => '90.4074',
				)
			)
		);

		$this->assertStringContainsString( 'openstreetmap.org/export/embed', $url );
		$this->assertStringContainsString( 'bbox=', $url );
	}

	/**
	 * OSM's embed cannot geocode, so an address-only configuration goes to
	 * the provider that can rather than silently centring on the wrong
	 * continent.
	 */
	public function test_openstreetmap_with_only_an_address_falls_back(): void {
		$url = MapSettings::embed_url(
			$this->settings(
				array(
					'provider' => 'osm',
					'address'  => 'Dhaka',
				)
			)
		);

		$this->assertStringContainsString( 'maps.google.com', $url );
	}

	public function test_numeric_settings_are_clamped(): void {
		$this->assertSame( 20, $this->settings( array( 'zoom' => 99 ) )['zoom'] );
		$this->assertSame( 1, $this->settings( array( 'zoom' => 0 ) )['zoom'] );
		$this->assertSame( 1200, $this->settings( array( 'h_desktop' => 99999 ) )['h_desktop'] );
		$this->assertSame( 120, $this->settings( array( 'h_mobile' => 1 ) )['h_mobile'] );
	}

	public function test_impossible_coordinates_are_rejected(): void {
		$this->assertSame( '', $this->settings( array( 'lat' => '199' ) )['lat'] );
		$this->assertSame( '', $this->settings( array( 'lng' => '-500' ) )['lng'] );
		$this->assertSame( '', $this->settings( array( 'lat' => 'north' ) )['lat'] );
		$this->assertSame( '-33.86', $this->settings( array( 'lat' => '-33.86' ) )['lat'] );
	}

	public function test_unknown_enum_values_fall_back_to_defaults(): void {
		$this->assertSame( 'google-embed', $this->settings( array( 'provider' => 'bing' ) )['provider'] );
		$this->assertSame( 'auto', $this->settings( array( 'style' => 'neon' ) )['style'] );
	}
}
