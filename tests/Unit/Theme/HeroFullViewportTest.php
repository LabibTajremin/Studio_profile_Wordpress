<?php
/**
 * The hero is full-page by contract (§11.5).
 *
 * These assertions read the shipped stylesheet rather than a browser, so they
 * run in the unit job with no WordPress server. The Playwright spec in
 * tests/E2E covers the rendered result once a server exists; this is the guard
 * that works today.
 *
 * Background: the hero shipped with a four-way `hero_style` select, production
 * was left on `minimal`, and the hero rendered at 43-50% of the viewport.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;

final class HeroFullViewportTest extends TestCase {

	private function css(): string {
		$path = dirname( __DIR__, 3 ) . '/themes/maapkathi-theme/assets/css/sections.css';
		$this->assertFileExists( $path, 'sections.css must ship with the theme.' );
		return (string) file_get_contents( $path );
	}

	/**
	 * The base rule must pin the hero to the viewport. `clamp()` is what broke
	 * it before: `clamp(560px, 100svh, 1100px)` falls short on tall screens.
	 */
	public function test_hero_min_height_is_the_full_small_viewport(): void {
		$css = $this->css();

		$matched = preg_match( '/^\.mk-hero \{(.*?)^\}/ms', $css, $m );
		$this->assertSame( 1, $matched, 'A top-level .mk-hero rule must exist.' );

		$this->assertMatchesRegularExpression(
			'/min-height:\s*100svh\s*;/',
			$m[1],
			'.mk-hero must use min-height: 100svh.'
		);
		$this->assertDoesNotMatchRegularExpression(
			'/min-height:\s*clamp\(/',
			$m[1],
			'.mk-hero must not clamp its height — that is what capped it before.'
		);
		$this->assertDoesNotMatchRegularExpression(
			'/(^|[^-])height:\s*100svh\s*;/',
			$m[1],
			'.mk-hero must use min-height, not a fixed height, or copy is clipped on short screens.'
		);
	}

	public function test_a_viewport_fallback_exists_for_browsers_without_svh(): void {
		$this->assertMatchesRegularExpression(
			'/@supports\s+not\s*\(min-height:\s*100svh\)\s*\{\s*\.mk-hero\s*\{[^}]*min-height:\s*100vh/m',
			$this->css(),
			'Browsers without svh support need a 100vh fallback.'
		);
	}

	/**
	 * No selector may key off the setting again. This is the rule that actually
	 * caused the regression.
	 */
	public function test_no_hero_style_variant_selectors_remain(): void {
		$this->assertStringNotContainsString(
			'data-hero-style',
			$this->css(),
			'Hero size must not be switchable from an attribute.'
		);
	}

	public function test_hero_media_covers_the_frame(): void {
		$this->assertMatchesRegularExpression(
			'/\.mk-hero__media[^{]*\{[^}]*object-fit:\s*cover/s',
			$this->css(),
			'Any uploaded aspect ratio must cover the hero rather than letterbox.'
		);
	}

	/**
	 * The attribute must not be emitted either, so a stale stylesheet cached by
	 * a visitor cannot be reactivated by markup.
	 */
	public function test_theme_vars_builder_does_not_emit_the_attribute(): void {
		$path = dirname( __DIR__, 3 ) . '/plugins/maapkathi-core/src/Theme/ThemeVarsBuilder.php';
		$this->assertFileExists( $path );
		$this->assertStringNotContainsString(
			'data-hero-style',
			(string) file_get_contents( $path ),
			'ThemeVarsBuilder must not emit data-hero-style.'
		);
	}
}
