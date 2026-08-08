<?php
/**
 * Accent colour registry.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accent registry (24), ported verbatim from
 * src/presentation/theme/accents.ts in the source app. IDs, names, and hex
 * values must match exactly — do not retype or invent.
 */
final class Accents {

	public const INK   = '#141110';
	public const CREAM = '#faf6f1';

	/**
	 * All 24 registered accents.
	 *
	 * @return array<int, array{id:string,name:string,light:string,dark:string}>
	 */
	public static function all(): array {
		return array(
			array(
				'id'    => 'obsidian-gold',
				'name'  => 'Obsidian Gold',
				'light' => '#6b5410',
				'dark'  => '#8a6d1f',
			),
			array(
				'id'    => 'terracotta',
				'name'  => 'Terracotta',
				'light' => '#9c4a2f',
				'dark'  => '#a8502f',
			),
			array(
				'id'    => 'sage',
				'name'  => 'Sage',
				'light' => '#46583c',
				'dark'  => '#8fa27f',
			),
			array(
				'id'    => 'deep-teal',
				'name'  => 'Deep Teal',
				'light' => '#0f5a5e',
				'dark'  => '#17767b',
			),
			array(
				'id'    => 'midnight-blue',
				'name'  => 'Midnight Blue',
				'light' => '#1e3a5f',
				'dark'  => '#2f5580',
			),
			array(
				'id'    => 'burnt-sienna',
				'name'  => 'Burnt Sienna',
				'light' => '#8f4a2a',
				'dark'  => '#a5522f',
			),
			array(
				'id'    => 'olive',
				'name'  => 'Olive',
				'light' => '#55591f',
				'dark'  => '#6a6f26',
			),
			array(
				'id'    => 'plum',
				'name'  => 'Plum',
				'light' => '#5d2a4e',
				'dark'  => '#7e3a6a',
			),
			array(
				'id'    => 'copper',
				'name'  => 'Copper',
				'light' => '#9a5a2f',
				'dark'  => '#bd7440',
			),
			array(
				'id'    => 'slate',
				'name'  => 'Slate',
				'light' => '#3f4a54',
				'dark'  => '#6f8290',
			),
			array(
				'id'    => 'sand',
				'name'  => 'Sand',
				'light' => '#cbb389',
				'dark'  => '#d8c39c',
			),
			array(
				'id'    => 'forest',
				'name'  => 'Forest',
				'light' => '#24503a',
				'dark'  => '#356b4f',
			),
			array(
				'id'    => 'oxblood',
				'name'  => 'Oxblood',
				'light' => '#6e1f2a',
				'dark'  => '#8f2d3b',
			),
			array(
				'id'    => 'cobalt',
				'name'  => 'Cobalt',
				'light' => '#1f3fa0',
				'dark'  => '#3357c4',
			),
			array(
				'id'    => 'champagne',
				'name'  => 'Champagne',
				'light' => '#d9c48f',
				'dark'  => '#e4d3a6',
			),
			array(
				'id'    => 'charcoal-rose',
				'name'  => 'Charcoal Rose',
				'light' => '#6b4a52',
				'dark'  => '#a67d86',
			),
			array(
				'id'    => 'emerald',
				'name'  => 'Emerald',
				'light' => '#0f6b47',
				'dark'  => '#128052',
			),
			array(
				'id'    => 'amber',
				'name'  => 'Amber',
				'light' => '#b5730a',
				'dark'  => '#e0940f',
			),
			array(
				'id'    => 'indigo',
				'name'  => 'Indigo',
				'light' => '#34327a',
				'dark'  => '#4a47a3',
			),
			array(
				'id'    => 'clay',
				'name'  => 'Clay',
				'light' => '#9a5744',
				'dark'  => '#b56d58',
			),
			array(
				'id'    => 'moss',
				'name'  => 'Moss',
				'light' => '#4f5b2c',
				'dark'  => '#626f37',
			),
			array(
				'id'    => 'bronze',
				'name'  => 'Bronze',
				'light' => '#7d5a2e',
				'dark'  => '#916a39',
			),
			array(
				'id'    => 'ink-violet',
				'name'  => 'Ink Violet',
				'light' => '#3a2a5e',
				'dark'  => '#52407e',
			),
			array(
				'id'    => 'warm-graphite',
				'name'  => 'Warm Graphite',
				'light' => '#44403c',
				'dark'  => '#605a54',
			),
		);
	}

	/**
	 * Looks up a single accent by its id.
	 *
	 * @param string $id Accent id to look up.
	 * @return array{id:string,name:string,light:string,dark:string}|null
	 */
	public static function by_id( string $id ): ?array {
		foreach ( self::all() as $accent ) {
			if ( $accent['id'] === $id ) {
				return $accent;
			}
		}
		return null;
	}

	/**
	 * All registered accent ids, in registry order.
	 *
	 * @return string[]
	 */
	public static function ids(): array {
		return array_column( self::all(), 'id' );
	}

	/**
	 * The accent id used when no accent has been chosen yet.
	 *
	 * @return string
	 */
	public static function default_id(): string {
		return 'oxblood';
	}

	/**
	 * The readable ink (dark or cream) to place on a given accent surface.
	 *
	 * @param string $surface_hex Accent surface colour, as a hex string.
	 * @return string Either self::INK or self::CREAM, whichever reads better.
	 */
	public static function on_accent( string $surface_hex ): string {
		$ink_contrast   = self::contrast_ratio( $surface_hex, self::INK );
		$cream_contrast = self::contrast_ratio( $surface_hex, self::CREAM );
		return $ink_contrast >= $cream_contrast ? self::INK : self::CREAM;
	}

	/**
	 * An accent shade guaranteed readable as *text* on a given surface.
	 *
	 * Note on_accent() solves the opposite problem (ink to place on top of
	 * an accent fill). This one is for the accent used as a foreground colour
	 * on the page background — role labels, icons, link hovers. A light
	 * accent such as Sand or Champagne on the cream background falls far
	 * below 4.5:1, so it is stepped toward ink (or toward cream on a dark
	 * surface) until it passes.
	 *
	 * @param string $accent_hex  Accent colour, as a hex string.
	 * @param string $surface_hex Surface the text sits on, as a hex string.
	 * @return string Hex colour meeting at least 4.5:1 against the surface.
	 */
	public static function readable_on( string $accent_hex, string $surface_hex ): string {
		if ( self::contrast_ratio( $accent_hex, $surface_hex ) >= 4.5 ) {
			return $accent_hex;
		}

		// Darken against a light surface, lighten against a dark one.
		$toward = self::relative_luminance( $surface_hex ) > 0.5 ? self::INK : self::CREAM;

		for ( $step = 1; $step <= 20; $step++ ) {
			$candidate = self::mix( $accent_hex, $toward, $step * 5 );
			if ( self::contrast_ratio( $candidate, $surface_hex ) >= 4.5 ) {
				return $candidate;
			}
		}

		return $toward;
	}

	/**
	 * Blends two hex colours by a percentage, in sRGB.
	 *
	 * @param string $hex_a   Base colour, as a hex string.
	 * @param string $hex_b   Colour to mix in, as a hex string.
	 * @param int    $percent How much of $hex_b to mix in, 0-100.
	 * @return string Resulting hex colour.
	 */
	private static function mix( string $hex_a, string $hex_b, int $percent ): string {
		$a     = ltrim( $hex_a, '#' );
		$b     = ltrim( $hex_b, '#' );
		$ratio = max( 0, min( 100, $percent ) ) / 100;
		$out   = '#';

		for ( $i = 0; $i < 3; $i++ ) {
			$channel_a = (int) hexdec( substr( $a, $i * 2, 2 ) );
			$channel_b = (int) hexdec( substr( $b, $i * 2, 2 ) );
			$mixed     = (int) round( $channel_a + ( ( $channel_b - $channel_a ) * $ratio ) );
			$out      .= str_pad( dechex( max( 0, min( 255, $mixed ) ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}

	/**
	 * WCAG contrast ratio between two hex colours.
	 *
	 * @param string $hex_a First colour, as a hex string.
	 * @param string $hex_b Second colour, as a hex string.
	 * @return float Contrast ratio, from 1 (no contrast) to 21 (max contrast).
	 */
	public static function contrast_ratio( string $hex_a, string $hex_b ): float {
		$lum_a   = self::relative_luminance( $hex_a );
		$lum_b   = self::relative_luminance( $hex_b );
		$lighter = max( $lum_a, $lum_b );
		$darker  = min( $lum_a, $lum_b );
		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * WCAG relative luminance of a hex colour.
	 *
	 * @param string $hex Colour, as a hex string.
	 * @return float Relative luminance, from 0 (black) to 1 (white).
	 */
	public static function relative_luminance( string $hex ): float {
		$hex = ltrim( $hex, '#' );
		$r   = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g   = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b   = hexdec( substr( $hex, 4, 2 ) ) / 255;

		$channel = static function ( float $c ): float {
			return $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		};

		return 0.2126 * $channel( $r ) + 0.7152 * $channel( $g ) + 0.0722 * $channel( $b );
	}
}
