<?php
/**
 * The logo wordmark's own typeface, size and weight.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Tests\Unit\Theme;

use Maapkathi\Core\Theme\LogoFonts;
use Maapkathi\Core\Theme\ThemeSettings;
use PHPUnit\Framework\TestCase;

final class LogoFontTest extends TestCase {

	/**
	 * Settings with one logo field overridden.
	 *
	 * @param array<string,mixed> $over Fields to override.
	 * @return array<string,mixed>
	 */
	private function settings( array $over = array() ): array {
		return ThemeSettings::sanitize( array_merge( ThemeSettings::defaults(), $over ) );
	}

	/**
	 * A site that has never touched these must render exactly as before.
	 */
	public function test_defaults_keep_the_previous_appearance(): void {
		$defaults = ThemeSettings::defaults();

		$this->assertSame( LogoFonts::INHERIT, $defaults['logo_font_id'] );
		$this->assertSame( 18, $defaults['logo_font_size'] );
		$this->assertSame( 600, $defaults['logo_font_weight'] );
		$this->assertSame( 'var(--font-headings)', LogoFonts::resolve_stack( $defaults ) );
		$this->assertSame( '', LogoFonts::google_family( $defaults ), 'Inherit must not request a second webfont.' );
	}

	public function test_preset_resolves_to_its_stack_and_google_family(): void {
		$settings = $this->settings( array( 'logo_font_id' => 'playfair-display' ) );

		$this->assertStringContainsString( '"Playfair Display"', LogoFonts::resolve_stack( $settings ) );
		$this->assertSame( 'Playfair Display', LogoFonts::google_family( $settings ) );
	}

	public function test_system_presets_request_no_webfont(): void {
		foreach ( array( 'system-sans', 'system-serif' ) as $id ) {
			$settings = $this->settings( array( 'logo_font_id' => $id ) );
			$this->assertSame( '', LogoFonts::google_family( $settings ), $id );
		}
	}

	public function test_unknown_font_id_falls_back_to_inherit(): void {
		$this->assertSame( LogoFonts::INHERIT, $this->settings( array( 'logo_font_id' => 'nope' ) )['logo_font_id'] );
	}

	/**
	 * The family name is interpolated into a CSS declaration, so it must not
	 * be able to close that declaration and inject rules of its own.
	 */
	public function test_custom_family_cannot_inject_css(): void {
		$settings = $this->settings(
			array(
				'logo_font_id'     => LogoFonts::CUSTOM,
				'logo_font_custom' => 'Evil"; } body{display:none} .x{"',
			)
		);

		$this->assertDoesNotMatchRegularExpression( '/["\';:{}()\\\\\\/<>]/', $settings['logo_font_custom'] );

		$stack = LogoFonts::resolve_stack( $settings );
		$this->assertStringNotContainsString( ';', $stack );
		$this->assertStringNotContainsString( '}', $stack );
		$this->assertSame( 0, substr_count( $stack, '"' ) % 2, 'Quotes must stay balanced.' );
	}

	public function test_custom_family_of_only_punctuation_falls_back(): void {
		$settings = $this->settings(
			array(
				'logo_font_id'     => LogoFonts::CUSTOM,
				'logo_font_custom' => '";}',
			)
		);

		$this->assertSame( 'var(--font-headings)', LogoFonts::resolve_stack( $settings ) );
	}

	/**
	 * This URL is enqueued into <head>, where a stylesheet from an arbitrary
	 * host could restyle the whole site.
	 *
	 * @dataProvider rejected_urls
	 */
	public function test_unsafe_font_urls_are_discarded( string $url ): void {
		$this->assertSame( '', $this->settings( array( 'logo_font_url' => $url ) )['logo_font_url'], $url );
	}

	/**
	 * @return array<int,array{0:string}>
	 */
	public static function rejected_urls(): array {
		return array(
			array( 'http://fonts.googleapis.com/css2?family=X' ),
			array( 'https://evil.example.com/x.css' ),
			array( 'javascript:alert(1)' ),
			array( 'https://evil.com/a.css?x=fonts.googleapis.com' ),
			array( '//fonts.googleapis.com/css2' ),
			array( 'data:text/css,body{display:none}' ),
		);
	}

	/**
	 * @dataProvider accepted_urls
	 */
	public function test_font_hosts_we_trust_are_kept( string $url ): void {
		$this->assertSame( $url, $this->settings( array( 'logo_font_url' => $url ) )['logo_font_url'] );
	}

	/**
	 * @return array<int,array{0:string}>
	 */
	public static function accepted_urls(): array {
		return array(
			array( 'https://fonts.googleapis.com/css2?family=Inter' ),
			array( 'https://fonts.bunny.net/css?family=inter' ),
			array( 'https://use.typekit.net/abc1234.css' ),
		);
	}

	/**
	 * @dataProvider sizes
	 */
	public function test_size_is_clamped_to_a_usable_range( int $posted, int $expected ): void {
		$this->assertSame( $expected, $this->settings( array( 'logo_font_size' => $posted ) )['logo_font_size'] );
	}

	/**
	 * @return array<int,array{0:int,1:int}>
	 */
	public static function sizes(): array {
		return array(
			array( 5, 10 ),
			array( 10, 10 ),
			array( 18, 18 ),
			array( 48, 48 ),
			array( 999, 48 ),
			array( -3, 10 ),
		);
	}

	/**
	 * @dataProvider weights
	 *
	 * @param mixed $posted Raw posted weight.
	 */
	public function test_weight_snaps_to_a_real_css_step( $posted, int $expected ): void {
		$this->assertSame( $expected, $this->settings( array( 'logo_font_weight' => $posted ) )['logo_font_weight'] );
	}

	/**
	 * @return array<int,array{0:mixed,1:int}>
	 */
	public static function weights(): array {
		return array(
			array( 100, 100 ),
			array( 900, 900 ),
			array( 650, 600 ),
			array( 0, 600 ),
			array( 'bold', 600 ),
		);
	}

	public function test_every_preset_id_is_unique_and_resolvable(): void {
		$ids = LogoFonts::ids();

		$this->assertSame( $ids, array_unique( $ids ) );
		$this->assertContains( LogoFonts::INHERIT, $ids );
		$this->assertContains( LogoFonts::CUSTOM, $ids );

		foreach ( $ids as $id ) {
			$this->assertNotNull( LogoFonts::by_id( $id ), $id );
		}
	}
}
