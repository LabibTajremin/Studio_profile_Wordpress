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
 * The complete 33-setting registry (§11.1). This is the single source of
 * truth for defaults and allowed values — the Appearance/Motion admin
 * screens, the theme-vars cache, and the settings-registry unit test all
 * read from here so nothing can drift out of sync.
 */
final class ThemeSettings {

	public const OPTION    = 'mk_theme_settings';
	public const CACHE_KEY = 'mk_theme_vars_cache';

	/**
	 * The default value for every registered setting.
	 *
	 * @return array<string,mixed> setting key => default value
	 */
	public static function defaults(): array {
		return array(
			// Appearance group (#1–14).
			'mode'                   => 'system',
			'accent_id'              => Accents::default_id(),
			'custom_accent_hex'      => null,
			'pattern_id'             => 'none',
			'pattern_opacity'        => 6,
			'font_pair_id'           => 'fraunces-manrope',
			'font_overrides'         => array(), // headings, body, nav, buttons, hero, accents => {fontId, colorHex}.
			'heading_color_hex'      => null,
			'body_color_hex'         => null,
			'background_tone'        => Backgrounds::DEFAULT_TONE,
			'header_opacity'         => 14,
			// FR-01: header colour. Following the accent is the default so
			// an existing site looks identical after the update (GR-03).
			'header_follow_accent'   => true,
			'header_palette_id'      => '',
			'header_hex'             => null,
			'header_logo_mode'       => 'auto',
			// Logo wordmark typography. The wordmark is a brand asset, so it
			// gets its own face, size and weight rather than inheriting the
			// heading pairing. 'inherit' keeps the previous behaviour, which
			// is what an existing site must look like after this update.
			'logo_font_id'           => LogoFonts::INHERIT,
			'logo_font_custom'       => '',
			'logo_font_url'          => '',
			'logo_font_size'         => 18,
			'logo_font_weight'       => 600,
			// FR-06/07 icon sizing. The old rendered size was 28px, so
			// "200% bigger" (3x) is 84 and "100% bigger" (2x) is 56. Both
			// are exposed as numbers because that phrasing is genuinely
			// ambiguous and the final look wants tuning, not a code change.
			'services_icon_size'     => 84,
			'services_icon_position' => 'beside',
			'values_icon_size'       => 56,
			'radius'                 => 'subtle',
			'density'                => 'comfortable',
			'grain'                  => false,
			'glass'                  => true,
			'hero_style'             => 'full-bleed',
			// Motion group (#15–31).
			'motion_preset'          => 'refined',
			'motion_level'           => 'refined',
			'scroll_reveal_style'    => 'fade-up-soft',
			'hero_animation'         => 'ken-burns-drift',
			'image_hover_style'      => 'zoom',
			'card_hover_style'       => 'lift-shadow',
			'text_reveal_style'      => 'mask-slide-up',
			'page_transition'        => 'fade',
			'cursor_style'           => 'none',
			'loader_style'           => 'none',
			'scroll_progress'        => false,
			'smooth_scroll'          => false,
			'parallax_intensity'     => 20,
			'motion_speed'           => 100,
			'stagger_ms'             => 70,
			'animate_once'           => true,
			'motion_on_mobile'       => 'reduced',
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
			'mode'                   => array( 'light', 'dark', 'system' ),
			'accent_id'              => Accents::ids(),
			'pattern_id'             => Patterns::ids(),
			'font_pair_id'           => Fonts::ids(),
			'background_tone'        => Backgrounds::ids(),
			'radius'                 => array_keys( Fonts::RADIUS_SCALE ),
			'density'                => array_keys( Fonts::DENSITY_SCALE ),
			'motion_preset'          => array( 'off', 'subtle', 'refined', 'expressive', 'custom' ),
			'motion_level'           => array( 'off', 'subtle', 'refined', 'expressive', 'custom' ),
			'scroll_reveal_style'    => array_column( Motion::scroll_reveal_styles(), 'id' ),
			'hero_animation'         => array_column( Motion::hero_animations(), 'id' ),
			'image_hover_style'      => array_column( Motion::image_hover_styles(), 'id' ),
			'card_hover_style'       => array_column( Motion::card_hover_styles(), 'id' ),
			'text_reveal_style'      => array_column( Motion::text_reveal_styles(), 'id' ),
			'page_transition'        => array_column( Motion::page_transitions(), 'id' ),
			'cursor_style'           => array( 'none', 'dot', 'ring', 'accent-blend' ),
			'loader_style'           => array( 'none', 'bar', 'logo-fade' ),
			'motion_on_mobile'       => array( 'full', 'reduced', 'off' ),
			'header_logo_mode'       => array( 'auto', 'light', 'dark' ),
			'logo_font_id'           => LogoFonts::ids(),
			'services_icon_position' => array( 'beside', 'above' ),
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
		self::flush_cache();
	}

	/**
	 * Drops the cached CSS variable block.
	 *
	 * Static so the other screens that feed those variables — the footer
	 * settings, the section builder — can invalidate the cache without
	 * instantiating a settings object just to call a hook callback.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		delete_transient( self::CACHE_KEY );
		delete_transient( self::CACHE_KEY . '_rm' );
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
	 * @param array<string,mixed> $input Full or partial settings payload.
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
				'pattern_opacity' => max( 0, min( 100, absint( $value ) ) ),
				'header_opacity'  => max( 0, min( 100, absint( $value ) ) ),
				'services_icon_size', 'values_icon_size' => max( 24, min( 160, absint( $value ) ) ),
				// The wordmark sits in a fixed-height bar, so the range is
				// bounded at both ends: below 10px it is unreadable, above
				// 48px it breaks the header's own layout.
				'logo_font_size'    => max( 10, min( 48, absint( $value ) ) ),
				'logo_font_weight'  => self::sanitize_font_weight( $value ),
				'logo_font_custom'  => self::sanitize_font_family( $value ),
				'logo_font_url'     => self::sanitize_font_url( $value ),
				'parallax_intensity' => max( 0, min( 100, absint( $value ) ) ),
				'motion_speed'       => max( 50, min( 150, absint( $value ) ) ),
				'stagger_ms'         => max( 0, min( 300, absint( $value ) ) ),
				// FR-01.6: accepts #rgb/#rrggbb with or without the leading
				// hash, in any case. An unparseable value normalises to null,
				// which the priority rule treats as "not set" rather than
				// writing a broken colour to the database.
				'header_hex'        => HexColor::normalize( $value ),
				'header_palette_id' => ( is_string( $value ) && null !== Accents::by_id( $value ) ) ? $value : '',
				'grain', 'glass', 'scroll_progress', 'smooth_scroll', 'animate_once', 'header_follow_accent' => (bool) $value,
				'font_overrides'    => is_array( $value ) ? self::sanitize_font_overrides( $value ) : array(),
				default             => $value,
			};
		}

		// motion_level mirrors motion_preset — legacy parity column, no
		// independent control (§11.1 #16).
		$out['motion_level'] = $out['motion_preset'];

		// The hero is always full-bleed and always full viewport. The column is
		// kept for schema parity with the source app but is never admin-settable,
		// so a stale or hand-crafted POST cannot shrink the hero.
		$out['hero_style'] = 'full-bleed';

		return $out;
	}

	/**
	 * Clamps a CSS font-weight to the nine standard steps.
	 *
	 * @param mixed $value Raw posted weight.
	 * @return int
	 */
	private static function sanitize_font_weight( $value ): int {
		$weight = absint( $value );
		$steps  = array( 100, 200, 300, 400, 500, 600, 700, 800, 900 );

		return in_array( $weight, $steps, true ) ? $weight : 600;
	}

	/**
	 * Normalises a custom font family name.
	 *
	 * The value is interpolated into a CSS font-family declaration and into
	 * a Google Fonts query, so it is reduced to the characters a real family
	 * name uses. That keeps a quote or a semicolon from closing the
	 * declaration and injecting arbitrary CSS.
	 *
	 * @param mixed $value Raw posted family name.
	 * @return string
	 */
	private static function sanitize_font_family( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$family = (string) preg_replace( '/[^A-Za-z0-9 _-]/', '', trim( $value ) );
		$family = (string) preg_replace( '/\s+/', ' ', $family );

		return substr( trim( $family ), 0, 80 );
	}

	/**
	 * Validates a custom webfont stylesheet URL.
	 *
	 * This URL is enqueued into <head>, so a stylesheet from anywhere can
	 * restyle the whole page. Only https is accepted, and only a host that
	 * actually serves fonts: an admin pasting the wrong link gets an empty
	 * value rather than a third-party stylesheet over their site. Hosting
	 * the file in the media library covers the self-hosted case.
	 *
	 * @param mixed $value Raw posted URL.
	 * @return string Empty string when the URL is unusable.
	 */
	private static function sanitize_font_url( $value ): string {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return '';
		}

		$raw = trim( $value );

		// Checked before esc_url_raw() rather than relying on it: that
		// function leaves a protocol-relative "//host/path" intact, because
		// no scheme is present for it to reject. Requiring the scheme here
		// makes "https only" mean exactly that.
		if ( 0 !== stripos( $raw, 'https://' ) ) {
			return '';
		}

		$url = esc_url_raw( $raw, array( 'https' ) );
		if ( '' === $url ) {
			return '';
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( '' === $host ) {
			return '';
		}

		$allowed = array(
			'fonts.googleapis.com',
			'fonts.gstatic.com',
			'use.typekit.net',
			'p.typekit.net',
			'use.fontawesome.com',
			'fonts.bunny.net',
			'cdn.jsdelivr.net',
			'rsms.me',
		);

		// The site's own uploads are always allowed, which is how a licensed
		// font file gets used without trusting an outside host.
		$own = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		if ( '' !== $own ) {
			$allowed[] = $own;
		}

		return in_array( $host, $allowed, true ) ? $url : '';
	}

	/**
	 * Validates a font_overrides payload, dropping unknown areas/font ids.
	 *
	 * @param array<string,mixed> $overrides Raw per-area font override payload.
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
