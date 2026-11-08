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

			// Already installed on the visitor's device: nothing to
			// download, so these render instantly and cost nothing.
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
				'id'     => 'arial',
				'name'   => 'Arial',
				'stack'  => 'Arial, "Helvetica Neue", Helvetica, ' . self::SANS_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'helvetica',
				'name'   => 'Helvetica',
				'stack'  => '"Helvetica Neue", Helvetica, Arial, ' . self::SANS_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'times-new-roman',
				'name'   => 'Times New Roman',
				'stack'  => '"Times New Roman", Times, ' . self::SERIF_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'georgia',
				'name'   => 'Georgia',
				'stack'  => 'Georgia, "Times New Roman", ' . self::SERIF_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'garamond',
				'name'   => 'Garamond',
				'stack'  => 'Garamond, "EB Garamond", Baskerville, ' . self::SERIF_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'palatino',
				'name'   => 'Palatino',
				'stack'  => '"Palatino Linotype", Palatino, "Book Antiqua", ' . self::SERIF_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'verdana',
				'name'   => 'Verdana',
				'stack'  => 'Verdana, Geneva, ' . self::SANS_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'tahoma',
				'name'   => 'Tahoma',
				'stack'  => 'Tahoma, Verdana, Geneva, ' . self::SANS_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'trebuchet-ms',
				'name'   => 'Trebuchet MS',
				'stack'  => '"Trebuchet MS", "Lucida Grande", ' . self::SANS_FALLBACK,
				'google' => '',
			),
			array(
				'id'     => 'courier-new',
				'name'   => 'Courier New',
				'stack'  => '"Courier New", Courier, "Lucida Console", monospace',
				'google' => '',
			),

			// Google Fonts, sans. Only the chosen face is ever requested.
			array(
				'id'     => 'montserrat',
				'name'   => 'Montserrat',
				'stack'  => '"Montserrat", ' . self::SANS_FALLBACK,
				'google' => 'Montserrat',
			),
			array(
				'id'     => 'roboto',
				'name'   => 'Roboto',
				'stack'  => '"Roboto", ' . self::SANS_FALLBACK,
				'google' => 'Roboto',
			),
			array(
				'id'     => 'open-sans',
				'name'   => 'Open Sans',
				'stack'  => '"Open Sans", ' . self::SANS_FALLBACK,
				'google' => 'Open Sans',
			),
			array(
				'id'     => 'lato',
				'name'   => 'Lato',
				'stack'  => '"Lato", ' . self::SANS_FALLBACK,
				'google' => 'Lato',
			),
			array(
				'id'     => 'poppins',
				'name'   => 'Poppins',
				'stack'  => '"Poppins", ' . self::SANS_FALLBACK,
				'google' => 'Poppins',
			),
			array(
				'id'     => 'raleway',
				'name'   => 'Raleway',
				'stack'  => '"Raleway", ' . self::SANS_FALLBACK,
				'google' => 'Raleway',
			),
			array(
				'id'     => 'oswald',
				'name'   => 'Oswald',
				'stack'  => '"Oswald", ' . self::SANS_FALLBACK,
				'google' => 'Oswald',
			),
			array(
				'id'     => 'nunito',
				'name'   => 'Nunito',
				'stack'  => '"Nunito", ' . self::SANS_FALLBACK,
				'google' => 'Nunito',
			),
			array(
				'id'     => 'rubik',
				'name'   => 'Rubik',
				'stack'  => '"Rubik", ' . self::SANS_FALLBACK,
				'google' => 'Rubik',
			),
			array(
				'id'     => 'josefin-sans',
				'name'   => 'Josefin Sans',
				'stack'  => '"Josefin Sans", ' . self::SANS_FALLBACK,
				'google' => 'Josefin Sans',
			),
			array(
				'id'     => 'quicksand',
				'name'   => 'Quicksand',
				'stack'  => '"Quicksand", ' . self::SANS_FALLBACK,
				'google' => 'Quicksand',
			),
			array(
				'id'     => 'bebas-neue',
				'name'   => 'Bebas Neue',
				'stack'  => '"Bebas Neue", ' . self::SANS_FALLBACK,
				'google' => 'Bebas Neue',
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

			// Google Fonts, serif and display.
			array(
				'id'     => 'merriweather',
				'name'   => 'Merriweather',
				'stack'  => '"Merriweather", ' . self::SERIF_FALLBACK,
				'google' => 'Merriweather',
			),
			array(
				'id'     => 'lora',
				'name'   => 'Lora',
				'stack'  => '"Lora", ' . self::SERIF_FALLBACK,
				'google' => 'Lora',
			),
			array(
				'id'     => 'pt-serif',
				'name'   => 'PT Serif',
				'stack'  => '"PT Serif", ' . self::SERIF_FALLBACK,
				'google' => 'PT Serif',
			),
			array(
				'id'     => 'noto-serif',
				'name'   => 'Noto Serif',
				'stack'  => '"Noto Serif", ' . self::SERIF_FALLBACK,
				'google' => 'Noto Serif',
			),
			array(
				'id'     => 'eb-garamond',
				'name'   => 'EB Garamond',
				'stack'  => '"EB Garamond", ' . self::SERIF_FALLBACK,
				'google' => 'EB Garamond',
			),
			array(
				'id'     => 'bodoni-moda',
				'name'   => 'Bodoni Moda',
				'stack'  => '"Bodoni Moda", ' . self::SERIF_FALLBACK,
				'google' => 'Bodoni Moda',
			),
			array(
				'id'     => 'cinzel',
				'name'   => 'Cinzel',
				'stack'  => '"Cinzel", ' . self::SERIF_FALLBACK,
				'google' => 'Cinzel',
			),
			array(
				'id'     => 'abril-fatface',
				'name'   => 'Abril Fatface',
				'stack'  => '"Abril Fatface", ' . self::SERIF_FALLBACK,
				'google' => 'Abril Fatface',
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
