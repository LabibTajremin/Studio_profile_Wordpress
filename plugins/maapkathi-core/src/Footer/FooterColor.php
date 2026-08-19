<?php
/**
 * Footer background/foreground resolution (FR-08.2, FR-09.2).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Footer;

use Maapkathi\Core\Theme\Accents;
use Maapkathi\Core\Theme\HexColor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns the footer background mode into a concrete colour pair.
 *
 * The copyright bar reads the very same tokens, which is what makes
 * FR-09.2 true by construction rather than by two values that merely
 * happen to match today.
 */
final class FooterColor {

	/**
	 * The dark neutral used by the reference footer.
	 *
	 * Deliberately not pure black: a flat #000 next to a warm cream page
	 * reads as a hole rather than as a surface.
	 */
	public const DARK_NEUTRAL = '#14181f';

	/**
	 * Resolves the footer surface and the ink that reads on it.
	 *
	 * @param array<string,mixed> $footer     Sanitized footer settings.
	 * @param string              $accent_hex Resolved accent for this mode.
	 * @param string              $surface    Page background for this mode.
	 * @param string              $on_surface Page foreground for this mode.
	 * @return array{bg:string,fg:string,muted:string}
	 */
	public static function resolve( array $footer, string $accent_hex, string $surface, string $on_surface ): array {
		$mode = (string) ( $footer['bg_mode'] ?? 'dark' );

		$bg = match ( $mode ) {
			'accent' => $accent_hex,
			'custom' => HexColor::normalize( $footer['bg_hex'] ?? null ) ?? self::DARK_NEUTRAL,
			// "surface" keeps the footer on the page background, which is
			// how the Classic footer has always looked.
			'surface' => $surface,
			default   => self::DARK_NEUTRAL,
		};

		// On the page surface the footer is not a separate colour field, so
		// it keeps the page's own ink rather than being pushed to pure
		// cream/black by the contrast picker.
		$fg = 'surface' === $mode ? $on_surface : Accents::on_accent( $bg );

		return array(
			'bg'    => $bg,
			'fg'    => $fg,
			// Secondary text (the copyright line, helper copy) still has to
			// clear 4.5:1, so it is a mix toward the background rather than
			// an opacity, which would sink below the threshold silently.
			'muted' => self::readable_muted( $bg, $fg ),
		);
	}

	/**
	 * A dimmed version of the footer ink that still clears 4.5:1.
	 *
	 * @param string $bg Footer background hex.
	 * @param string $fg Footer foreground hex.
	 * @return string Hex colour.
	 */
	private static function readable_muted( string $bg, string $fg ): string {
		$candidate = $fg;

		// Walk toward the background in small steps and stop at the last
		// shade that still passes, rather than picking a fixed opacity that
		// happens to fail on some backgrounds.
		for ( $percent = 10; $percent <= 40; $percent += 10 ) {
			$mixed = self::mix( $fg, $bg, $percent );
			if ( Accents::contrast_ratio( $mixed, $bg ) < 4.5 ) {
				break;
			}
			$candidate = $mixed;
		}

		return $candidate;
	}

	/**
	 * Blends two hex colours.
	 *
	 * @param string $hex_a   Colour to blend from.
	 * @param string $hex_b   Colour to blend toward.
	 * @param int    $percent How far toward $hex_b to move, 0-100.
	 * @return string Hex colour.
	 */
	private static function mix( string $hex_a, string $hex_b, int $percent ): string {
		$a = HexColor::normalize( $hex_a ) ?? '#000000';
		$b = HexColor::normalize( $hex_b ) ?? '#ffffff';

		$out = '#';
		for ( $i = 1; $i < 7; $i += 2 ) {
			$channel_a = hexdec( substr( $a, $i, 2 ) );
			$channel_b = hexdec( substr( $b, $i, 2 ) );
			$out      .= str_pad( dechex( (int) round( $channel_a + ( ( $channel_b - $channel_a ) * $percent / 100 ) ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}
}
