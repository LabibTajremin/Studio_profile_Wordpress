<?php
/**
 * Header background/foreground resolution (FR-01).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the header colour from the four admin inputs, in the exact
 * priority order FR-01.5 lays down, and derives a readable foreground for it.
 */
final class HeaderColor {

	/**
	 * Resolves the header background and foreground for one theme mode.
	 *
	 * The priority order is FR-01.5 verbatim and deliberately has no other
	 * interpretation:
	 *   1. follow-accent on            -> accent wins, hex and palette ignored
	 *   2. off + valid hex             -> hex wins over the palette swatch
	 *   3. off + palette swatch chosen -> swatch applies
	 *   4. off, nothing usable set     -> fall back to the accent
	 *
	 * Step 4 is what stops the header ever rendering transparent, white by
	 * accident, or unstyled: there is no branch that returns an empty value.
	 *
	 * @param array<string,mixed> $settings   Sanitized theme settings.
	 * @param string              $accent_hex Resolved accent for this mode.
	 * @param bool                $dark       Whether to resolve the dark-mode variant.
	 * @return array{bg:string,fg:string,source:string} Resolved colours and which rule produced them.
	 */
	public static function resolve( array $settings, string $accent_hex, bool $dark = false ): array {
		$follows = ! empty( $settings['header_follow_accent'] );

		if ( $follows ) {
			return self::pack( $accent_hex, 'accent' );
		}

		$hex = HexColor::normalize( $settings['header_hex'] ?? null );
		if ( null !== $hex ) {
			return self::pack( $hex, 'hex' );
		}

		$swatch = self::palette_hex( (string) ( $settings['header_palette_id'] ?? '' ), $dark );
		if ( null !== $swatch ) {
			return self::pack( $swatch, 'palette' );
		}

		return self::pack( $accent_hex, 'fallback' );
	}

	/**
	 * Whether the header is showing a colour of its own rather than the accent.
	 *
	 * The theme uses this to decide whether to paint the header as a solid
	 * configured surface (custom colour) or to keep the translucent accent
	 * wash it has always had (GR-03: an untouched site must not change).
	 *
	 * @param array<string,mixed> $settings Sanitized theme settings.
	 * @return bool
	 */
	public static function is_custom( array $settings ): bool {
		if ( ! empty( $settings['header_follow_accent'] ) ) {
			return false;
		}

		return null !== HexColor::normalize( $settings['header_hex'] ?? null )
			|| null !== self::palette_hex( (string) ( $settings['header_palette_id'] ?? '' ), false );
	}

	/**
	 * The swatch palette offered for the header.
	 *
	 * Deliberately the same registry the accent picker uses, so the header
	 * cannot drift into a second, differently-curated set of colours
	 * (FR-01.4 requires the same premium palette, minimum 20 swatches).
	 *
	 * @return array<int,array{id:string,name:string,light:string,dark:string}>
	 */
	public static function palette(): array {
		return Accents::all();
	}

	/**
	 * Looks up one palette swatch's hex for the requested mode.
	 *
	 * @param string $id   Swatch id, or an empty string when none is chosen.
	 * @param bool   $dark Whether to return the dark-mode variant.
	 * @return string|null Hex colour, or null when no valid swatch is selected.
	 */
	private static function palette_hex( string $id, bool $dark ): ?string {
		if ( '' === $id ) {
			return null;
		}

		$swatch = Accents::by_id( $id );
		if ( null === $swatch ) {
			return null;
		}

		return $dark ? $swatch['dark'] : $swatch['light'];
	}

	/**
	 * Pairs a resolved background with the ink that reads on it (FR-01.7).
	 *
	 * @param string $bg     Resolved background hex.
	 * @param string $source Which priority rule produced it.
	 * @return array{bg:string,fg:string,source:string}
	 */
	private static function pack( string $bg, string $source ): array {
		return array(
			'bg'     => $bg,
			'fg'     => Accents::on_accent( $bg ),
			'source' => $source,
		);
	}
}
