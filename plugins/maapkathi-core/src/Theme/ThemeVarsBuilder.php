<?php
/**
 * Theme CSS custom-property builder.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns theme_settings into the CSS custom-property block injected inline in
 * <head> (§10, §11.3). Cached in a transient, busted on save
 * (ThemeSettings::bust_cache()) so an admin never has to hard-refresh.
 */
final class ThemeVarsBuilder {

	/**
	 * Builds (or returns the cached) `:root{}` CSS block for the current
	 * theme settings.
	 *
	 * @param bool $reduced_motion Whether to build the reduced-motion variant.
	 * @return string Complete CSS, ready to inline in <head>.
	 */
	public static function build( bool $reduced_motion = false ): string {
		$cache_key = ThemeSettings::CACHE_KEY . ( $reduced_motion ? '_rm' : '' );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) ) {
			return $cached;
		}

		$settings = ThemeSettings::get();
		$vars     = self::vars_for( $settings, $reduced_motion );
		$pattern  = Patterns::by_id( $settings['pattern_id'] ) ?? Patterns::by_id( 'none' );

		unset( $vars['--pattern-css'] );

		$css = ":root{\n";
		foreach ( $vars as $name => $value ) {
			$css .= "  {$name}: {$value};\n";
		}
		$css .= "}\n";
		// --background/--foreground swap to their dark-mode pair here rather
		// than via a plain var() default, since the light/dark pair is a
		// per-tenant admin choice (Backgrounds), not a fixed fallback.
		$css .= ':root[data-theme="dark"]{ --background: var(--background-dark); --foreground: var(--foreground-dark); }' . "\n";
		// A CSS custom property cannot hold a full declaration for var()
		// substitution, so the pattern's `background-image`/`background-size`
		// declarations are emitted here as a real rule instead of a var.
		$css .= "body::before{ {$pattern['css']} }\n";

		set_transient( $cache_key, $css, DAY_IN_SECONDS );

		return $css;
	}

	/**
	 * Resolves a full settings payload into the CSS custom-property map.
	 *
	 * @param array<string,mixed> $settings       Sanitized theme settings.
	 * @param bool                $reduced_motion Whether to resolve the reduced-motion variant.
	 * @return array<string,string>
	 */
	public static function vars_for( array $settings, bool $reduced_motion = false ): array {
		$accent = Accents::by_id( $settings['accent_id'] ) ?? Accents::by_id( Accents::default_id() );

		$accent_light = $settings['custom_accent_hex'] ? $settings['custom_accent_hex'] : $accent['light'];
		$accent_dark  = $settings['custom_accent_hex'] ? $settings['custom_accent_hex'] : $accent['dark'];

		$pattern = Patterns::by_id( $settings['pattern_id'] ) ?? Patterns::by_id( 'none' );
		$fonts   = Fonts::by_id( $settings['font_pair_id'] ) ?? Fonts::by_id( 'fraunces-manrope' );
		$tone    = Backgrounds::by_id( $settings['background_tone'] ) ?? Backgrounds::by_id( Backgrounds::DEFAULT_TONE );

		$vars = array(
			'--accent'            => $accent_light,
			'--accent-dark'       => $accent_dark,
			'--accent-foreground' => Accents::on_accent( $accent_light ),
			'--background'        => $tone['light']['background'],
			'--foreground'        => $tone['light']['foreground'],
			'--background-dark'   => $tone['dark']['background'],
			'--foreground-dark'   => $tone['dark']['foreground'],
			'--pattern-css'       => rtrim( $pattern['css'], ';' ) . ';',
			'--pattern-opacity'   => (string) ( (int) $settings['pattern_opacity'] / 100 ),
			'--font-headings'     => self::area_font( $settings, 'headings', $fonts['display'] ),
			'--font-body'         => self::area_font( $settings, 'body', $fonts['body'] ),
			'--font-nav'          => self::area_font( $settings, 'nav', $fonts['body'] ),
			'--font-buttons'      => self::area_font( $settings, 'buttons', $fonts['body'] ),
			'--font-hero'         => self::area_font( $settings, 'hero', $fonts['display'] ),
			'--font-accents'      => self::area_font( $settings, 'accents', $fonts['body'] ),
			'--heading-color'     => $settings['heading_color_hex'] ? $settings['heading_color_hex'] : 'inherit',
			'--body-color'        => $settings['body_color_hex'] ? $settings['body_color_hex'] : 'inherit',
			'--radius'            => Fonts::RADIUS_SCALE[ $settings['radius'] ] ?? Fonts::RADIUS_SCALE['subtle'],
			'--density'           => Fonts::DENSITY_SCALE[ $settings['density'] ] ?? Fonts::DENSITY_SCALE['comfortable'],
			'--grain-opacity'     => $settings['grain'] ? '0.06' : '0',
			'--glass-blur'        => $settings['glass'] ? '12px' : '0px',
		);

		$motion_vars = Motion::resolve_vars(
			array(
				'motionPreset'      => $settings['motion_preset'],
				'motionSpeed'       => (int) $settings['motion_speed'],
				'staggerMs'         => (int) $settings['stagger_ms'],
				'parallaxIntensity' => (int) $settings['parallax_intensity'],
			),
			$reduced_motion
		);

		return array_merge( $vars, $motion_vars );
	}

	/**
	 * Resolves the font-family value for a single theming area, honouring
	 * any per-area font override.
	 *
	 * @param array<string,mixed> $settings Sanitized theme settings.
	 * @param string              $area     Theming area key, e.g. 'headings'.
	 * @param string              $fallback Font-family to use when no override applies.
	 * @return string
	 */
	private static function area_font( array $settings, string $area, string $fallback ): string {
		$override = $settings['font_overrides'][ $area ] ?? null;
		if ( $override && ! empty( $override['fontId'] ) ) {
			$pair = Fonts::by_id( $override['fontId'] );
			if ( $pair ) {
				return str_contains( $area, 'headings' ) || 'hero' === $area ? $pair['display'] : $pair['body'];
			}
		}
		return $fallback;
	}

	/**
	 * Resolves the data-* attributes for <html>, for settings that don't map
	 * to a single CSS var (§11.4): hero_style, hero_animation, loader_style,
	 * page_transition, cursor_style, scroll_progress, grain, glass, and the
	 * remaining motion selects driving JS behaviour.
	 *
	 * @param array<string,mixed> $settings Sanitized theme settings.
	 * @return array<string,string>
	 */
	public static function data_attrs_for( array $settings ): array {
		return array(
			'data-mode'                => $settings['mode'],
			'data-hero-style'          => $settings['hero_style'],
			'data-hero-animation'      => $settings['hero_animation'],
			'data-loader-style'        => $settings['loader_style'],
			'data-page-transition'     => $settings['page_transition'],
			'data-cursor-style'        => $settings['cursor_style'],
			'data-scroll-progress'     => $settings['scroll_progress'] ? '1' : '0',
			'data-grain'               => $settings['grain'] ? '1' : '0',
			'data-glass'               => $settings['glass'] ? '1' : '0',
			'data-scroll-reveal-style' => $settings['scroll_reveal_style'],
			'data-image-hover-style'   => $settings['image_hover_style'],
			'data-card-hover-style'    => $settings['card_hover_style'],
			'data-text-reveal-style'   => $settings['text_reveal_style'],
			'data-smooth-scroll'       => $settings['smooth_scroll'] ? '1' : '0',
			'data-animate-once'        => $settings['animate_once'] ? '1' : '0',
			'data-motion-on-mobile'    => $settings['motion_on_mobile'],
			'data-motion-preset'       => $settings['motion_preset'],
		);
	}
}
