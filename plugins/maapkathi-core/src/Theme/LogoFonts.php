<?php
/**
 * Logo typeface registry.
 *
 * The wordmark beside the logo mark is the one piece of type a studio most
 * often wants off the theme's pairing — it is a brand asset, not body copy.
 * This registry backs that choice: a short list of faces that read well at
 * wordmark size, plus an escape hatch for a family the list does not carry.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Faces offered for the logo wordmark.
 */
final class LogoFonts {

	private const SERIF_FALLBACK = 'Georgia, "Times New Roman", serif';
	private const SANS_FALLBACK  = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

	/**
	 * Id used when the wordmark should keep following the heading font.
	 */
	public const INHERIT = 'inherit';

	/**
	 * Id that switches on the custom family / stylesheet fields.
	 */
	public const CUSTOM = 'custom';

	/**
	 * Presets.
	 *
	 * `google` is the exact Google Fonts family name, or an empty string for
	 * a face that needs no web font (the system stack). Only the chosen face
	 * is ever requested — the registry is not a download list.
	 *
	 * @return array<int,array{id:string,name:string,stack:string,google:string}>
	 */
	public static function all(): array {
		return array(
			array(
				'id'     => self::INHERIT,
				'name'   => __( 'Same as headings (default)', 'maapkathi' ),
				'stack'  => 'var(--font-headings)',
				'google' => '',
			),
			array(
				'id'     => 'system-sans',
				'name'   => 'System sans',
				'stack'  => self::SANS_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'system-serif',
				'name'   => 'System serif',
				'stack'  => self::SERIF_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'fraunces',
				'name'   => 'Fraunces',
				'stack'  => '"Fraunces", ' . self::SERIF_FALLBACK,
				'google' => 'Fraunces',
			),
			array(
				'id'     => 'playfair-display',
				'name'   => 'Playfair Display',
				'stack'  => '"Playfair Display", ' . self::SERIF_FALLBACK,
				'google' => 'Playfair Display',
			),
			array(
				'id'     => 'cormorant-garamond',
				'name'   => 'Cormorant Garamond',
				'stack'  => '"Cormorant Garamond", ' . self::SERIF_FALLBACK,
				'google' => 'Cormorant Garamond',
			),
			array(
				'id'     => 'dm-serif-display',
				'name'   => 'DM Serif Display',
				'stack'  => '"DM Serif Display", ' . self::SERIF_FALLBACK,
				'google' => 'DM Serif Display',
			),
			array(
				'id'     => 'instrument-serif',
				'name'   => 'Instrument Serif',
				'stack'  => '"Instrument Serif", ' . self::SERIF_FALLBACK,
				'google' => 'Instrument Serif',
			),
			array(
				'id'     => 'libre-caslon-display',
				'name'   => 'Libre Caslon Display',
				'stack'  => '"Libre Caslon Display", ' . self::SERIF_FALLBACK,
				'google' => 'Libre Caslon Display',
			),
			array(
				'id'     => 'spectral',
				'name'   => 'Spectral',
				'stack'  => '"Spectral", ' . self::SERIF_FALLBACK,
				'google' => 'Spectral',
			),
			array(
				'id'     => 'bricolage-grotesque',
				'name'   => 'Bricolage Grotesque',
				'stack'  => '"Bricolage Grotesque", ' . self::SANS_FALLBACK,
				'google' => 'Bricolage Grotesque',
			),
			array(
				'id'     => 'manrope',
				'name'   => 'Manrope',
				'stack'  => '"Manrope", ' . self::SANS_FALLBACK,
				'google' => 'Manrope',
			),
			array(
				'id'     => 'inter',
				'name'   => 'Inter',
				'stack'  => '"Inter", ' . self::SANS_FALLBACK,
				'google' => 'Inter',
			),
			array(
				'id'     => 'geist',
				'name'   => 'Geist',
				'stack'  => '"Geist", ' . self::SANS_FALLBACK,
				'google' => 'Geist',
			),
			array(
				'id'     => 'dm-sans',
				'name'   => 'DM Sans',
				'stack'  => '"DM Sans", ' . self::SANS_FALLBACK,
				'google' => 'DM Sans',
			),
			array(
				'id'     => 'work-sans',
				'name'   => 'Work Sans',
				'stack'  => '"Work Sans", ' . self::SANS_FALLBACK,
				'google' => 'Work Sans',
			),
			array(
				'id'     => 'space-grotesk',
				'name'   => 'Space Grotesk',
				'stack'  => '"Space Grotesk", ' . self::SANS_FALLBACK,
				'google' => 'Space Grotesk',
			),
			array(
				'id'     => 'outfit',
				'name'   => 'Outfit',
				'stack'  => '"Outfit", ' . self::SANS_FALLBACK,
				'google' => 'Outfit',
			),
			array(
				'id'     => self::CUSTOM,
				'name'   => __( 'Custom font…', 'maapkathi' ),
				'stack'  => '',
				'google' => '',
			),
		);
	}

	/**
	 * Every registered id, for validation.
	 *
	 * @return string[]
	 */
	public static function ids(): array {
		return array_column( self::all(), 'id' );
	}

	/**
	 * One preset by id.
	 *
	 * @param string $id Preset id.
	 * @return array{id:string,name:string,stack:string,google:string}|null
	 */
	public static function by_id( string $id ): ?array {
		foreach ( self::all() as $font ) {
			if ( $font['id'] === $id ) {
				return $font;
			}
		}

		return null;
	}

	/**
	 * The CSS font-family value for the saved settings.
	 *
	 * A custom family name is quoted and given the sans fallback, so a
	 * misspelled name degrades to a readable face rather than to nothing.
	 *
	 * @param array<string,mixed> $settings Theme settings.
	 * @return string CSS font-family value.
	 */
	public static function resolve_stack( array $settings ): string {
		$id = (string) ( $settings['logo_font_id'] ?? self::INHERIT );

		if ( self::CUSTOM === $id ) {
			$family = trim( (string) ( $settings['logo_font_custom'] ?? '' ) );

			return '' !== $family
				? '"' . $family . '", ' . self::SANS_FALLBACK
				: 'var(--font-headings)';
		}

		$preset = self::by_id( $id );

		return $preset && '' !== $preset['stack'] ? $preset['stack'] : 'var(--font-headings)';
	}

	/**
	 * The Google Fonts family name to request for the saved settings, if any.
	 *
	 * Returns an empty string for the inherit case (the pairing already
	 * loads it), for the system stacks, and when a custom face is supplied
	 * through its own stylesheet URL rather than Google.
	 *
	 * @param array<string,mixed> $settings Theme settings.
	 * @return string
	 */
	public static function google_family( array $settings ): string {
		$id = (string) ( $settings['logo_font_id'] ?? self::INHERIT );

		if ( self::CUSTOM === $id ) {
			// A self-hosted or third-party stylesheet supersedes Google.
			if ( '' !== trim( (string) ( $settings['logo_font_url'] ?? '' ) ) ) {
				return '';
			}

			return trim( (string) ( $settings['logo_font_custom'] ?? '' ) );
		}

		$preset = self::by_id( $id );

		return $preset ? $preset['google'] : '';
	}
}
