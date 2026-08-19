<?php
/**
 * Hex colour parsing and normalisation (FR-01.6).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accepts the hex spellings a human actually types and normalises them to
 * one canonical form.
 *
 * WordPress's own sanitize_hex_color() rejects a missing "#" and rejects
 * 3-digit shorthand, both of which FR-01.6 requires us to accept, so this
 * normalises first and hands the canonical value over afterwards.
 */
final class HexColor {

	/**
	 * Normalises a user-entered hex colour to lowercase #rrggbb.
	 *
	 * @param mixed $raw Raw field value, from a settings form or import.
	 * @return string|null Canonical #rrggbb, or null when the value is not a hex colour.
	 */
	public static function normalize( $raw ): ?string {
		if ( ! is_string( $raw ) ) {
			return null;
		}

		$value = strtolower( trim( $raw ) );
		if ( '' === $value ) {
			return null;
		}

		$value = ltrim( $value, '#' );

		if ( ! preg_match( '/^([0-9a-f]{3}|[0-9a-f]{6})$/', $value ) ) {
			return null;
		}

		// Expand #abc to #aabbcc so every stored value is the same length
		// and downstream luminance maths never has to branch on it.
		if ( 3 === strlen( $value ) ) {
			$value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
		}

		return '#' . $value;
	}

	/**
	 * Whether a raw field value is a usable hex colour.
	 *
	 * @param mixed $raw Raw field value.
	 * @return bool
	 */
	public static function is_valid( $raw ): bool {
		return null !== self::normalize( $raw );
	}
}
