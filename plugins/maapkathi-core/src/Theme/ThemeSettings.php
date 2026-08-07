<?php
/**
 * Theme settings registry.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The complete 31-setting registry (§11.1). This is the single source of
 * truth for defaults and allowed values — the Appearance/Motion admin
 * screens, the theme-vars cache, and the settings-registry unit test all
 * read from here so nothing can drift out of sync.
 */
final class ThemeSettings {

	public const OPTION    = 'mk_theme_settings';
	public const CACHE_KEY = 'mk_theme_vars_cache';

	/**
	 * @return array<string,mixed> setting key => default value
	 */
	public static function defaults(): array {
		return array(
			// Appearance group (#1–14).
			'mode'                => 'system',
			'accent_id'           => Accents::default_id(),
			'custom_accent_hex'   => null,
			'pattern_id'          => 'none',
			'pattern_opacity'     => 6,
			'font_pair_id'        => 'fraunces-manrope',
			'font_overrides'      => array(), // headings, body, nav, buttons, hero, accents => {fontId, colorHex}.
			'heading_color_hex'   => null,
			'body_color_hex'      => null,
			'radius'              => 'subtle',
			'density'             => 'comfortable',
			'grain'               => false,
			'glass'               => true,
			'hero_style'          => 'full-bleed',
			// Motion group (#15–31).
			'motion_preset'       => 'refined',
			'motion_level'        => 'refined',
			'scroll_reveal_style' => 'fade-up-soft',
			'hero_animation'      => 'ken-burns-drift',
			'image_hover_style'   => 'zoom',
			'card_hover_style'    => 'lift-shadow',
			'text_reveal_style'   => 'mask-slide-up',
			'page_transition'     => 'fade',
			'cursor_style'        => 'none',
			'loader_style'        => 'none',
			'scroll_progress'     => false,
			'smooth_scroll'       => false,
			'parallax_intensity'  => 20,
			'motion_speed'        => 100,
			'stagger_ms'          => 70,
			'animate_once'        => true,
			'motion_on_mobile'    => 'reduced',
		);
	}

	/**
	 * All registered setting keys.
	 *
	 * @return string[]
	 */
	public static function keys(): array {
		return array_keys( self::defaults() );
	}

	/**
	 * Allowed value lists per setting, for server-side validation (§11.2)
	 * and the registry-integrity test (§11.4). Free-form settings (hex
	 * colours, sliders, arrays) are validated separately.
	 *
	 * @return array<string,string[]>
	 */
	public static function allowed_values(): array {
		return array(
			'mode'                => array( 'light', 'dark', 'system' ),
			'accent_id'           => Accents::ids(),
			'pattern_id'          => Patterns::ids(),
			'font_pair_id'        => Fonts::ids(),
			'radius'              => array_keys( Fonts::RADIUS_SCALE ),
			'density'             => array_keys( Fonts::DENSITY_SCALE ),
			'hero_style'          => array( 'full-bleed', 'contained', 'split', 'minimal' ),
			'motion_preset'       => array( 'off', 'subtle', 'refined', 'expressive', 'custom' ),
			'motion_level'        => array( 'off', 'subtle', 'refined', 'expressive', 'custom' ),
			'scroll_reveal_style' => array_column( Motion::scroll_reveal_styles(), 'id' ),
			'hero_animation'      => array_column( Motion::hero_animations(), 'id' ),
			'image_hover_style'   => array_column( Motion::image_hover_styles(), 'id' ),
			'card_hover_style'    => array_column( Motion::card_hover_styles(), 'id' ),
			'text_reveal_style'   => array_column( Motion::text_reveal_styles(), 'id' ),
			'page_transition'     => array_column( Motion::page_transitions(), 'id' ),
			'cursor_style'        => array( 'none', 'dot', 'ring', 'accent-blend' ),
			'loader_style'        => array( 'none', 'bar', 'logo-fade' ),
			'motion_on_mobile'    => array( 'full', 'reduced', 'off' ),
		);
	}

	/**
	 * Registers the hooks that keep the theme-vars cache in sync with the
	 * stored settings.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'update_option_' . self::OPTION, array( $this, 'bust_cache' ) );
		add_action( 'add_option_' . self::OPTION, array( $this, 'bust_cache' ) );
	}

	/**
	 * Clears the cached theme-vars CSS so it is rebuilt on next request.
	 *
	 * @return void
	 */
	public function bust_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * The current, sanitized theme settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	/**
	 * Validates and clamps a full or partial settings payload against the
	 * registry. Unknown keys are dropped; unknown enum values and
	 * out-of-range numbers fall back to the default (§11.2, §11.3).
	 *
	 * @param array<string,mixed> $input
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults = self::defaults();
		$allowed  = self::allowed_values();
		$out      = array();

		foreach ( $defaults as $key => $default ) {
			$value = $input[ $key ] ?? $default;

			if ( isset( $allowed[ $key ] ) ) {
				$out[ $key ] = in_array( $value, $allowed[ $key ], true ) ? $value : $default;
				continue;
			}

			$out[ $key ] = match ( $key ) {
				'custom_accent_hex', 'heading_color_hex', 'body_color_hex' =>
					( is_string( $value ) && '' !== $value ) ? sanitize_hex_color( $value ) : null,
				'pattern_opacity'   => max( 0, min( 100, absint( $value ) ) ),
				'parallax_intensity' => max( 0, min( 100, absint( $value ) ) ),
				'motion_speed'      => max( 50, min( 150, absint( $value ) ) ),
				'stagger_ms'        => max( 0, min( 300, absint( $value ) ) ),
				'grain', 'glass', 'scroll_progress', 'smooth_scroll', 'animate_once' => (bool) $value,
				'font_overrides'    => is_array( $value ) ? self::sanitize_font_overrides( $value ) : array(),
				default             => $value,
			};
		}

		// motion_level mirrors motion_preset — legacy parity column, no
		// independent control (§11.1 #16).
		$out['motion_level'] = $out['motion_preset'];

		return $out;
	}

	/**
	 * @param array<string,mixed> $overrides
	 * @return array<string,array{fontId:?string,colorHex:?string}>
	 */
	private static function sanitize_font_overrides( array $overrides ): array {
		$areas = array( 'headings', 'body', 'nav', 'buttons', 'hero', 'accents' );
		$out   = array();

		foreach ( $areas as $area ) {
			if ( empty( $overrides[ $area ] ) || ! is_array( $overrides[ $area ] ) ) {
				continue;
			}
			$row     = $overrides[ $area ];
			$font_id = $row['fontId'] ?? null;
			$color   = $row['colorHex'] ?? null;

			$entry = array();
			if ( $font_id && in_array( $font_id, Fonts::ids(), true ) ) {
				$entry['fontId'] = $font_id;
			}
			if ( $color ) {
				$entry['colorHex'] = sanitize_hex_color( $color );
			}
			if ( ! empty( $entry ) ) {
				$out[ $area ] = $entry;
			}
		}

		return $out;
	}
}
