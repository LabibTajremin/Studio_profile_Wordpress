<?php
/**
 * Inline SVG social marks for the footer (FR-08.6, FR-08.8).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Footer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bundled, single-path social marks.
 *
 * Inline rather than an icon font or a sprite sheet: the footer needs them
 * to take their colour from currentColor so the accent hover works on any
 * footer background, and an icon font would also be a second network
 * request for eleven glyphs.
 */
final class SocialIcons {

	/**
	 * The path data for one platform's mark.
	 *
	 * @param string $platform Platform slug from FooterSettings::platforms().
	 * @return string SVG path data, or an empty string for an unknown platform.
	 */
	public static function path( string $platform ): string {
		$paths = array(
			'facebook'  => 'M14 9h2.5V6H14c-2.2 0-3.5 1.4-3.5 3.6V11H8v3h2.5v7h3v-7H16l.5-3h-3V9.8c0-.6.3-.8 1-.8Z',
			'instagram' => 'M12 7.6a4.4 4.4 0 1 0 0 8.8 4.4 4.4 0 0 0 0-8.8Zm0 7.2a2.8 2.8 0 1 1 0-5.6 2.8 2.8 0 0 1 0 5.6Zm5.6-7.4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM8.5 3.5h7A5 5 0 0 1 20.5 8.5v7a5 5 0 0 1-5 5h-7a5 5 0 0 1-5-5v-7a5 5 0 0 1 5-5Zm0 1.8a3.2 3.2 0 0 0-3.2 3.2v7a3.2 3.2 0 0 0 3.2 3.2h7a3.2 3.2 0 0 0 3.2-3.2v-7a3.2 3.2 0 0 0-3.2-3.2h-7Z',
			'youtube'   => 'M21.6 8.2a2.5 2.5 0 0 0-1.8-1.8C18.2 6 12 6 12 6s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 8.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 3.8 2.5 2.5 0 0 0 1.8 1.8C5.8 18 12 18 12 18s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-3.8ZM10 15V9l5.2 3-5.2 3Z',
			'linkedin'  => 'M6.94 8.5a1.94 1.94 0 1 1 0-3.88 1.94 1.94 0 0 1 0 3.88ZM5.3 20h3.3V9.9H5.3V20Zm5.4 0H14v-5.5c0-1.45.27-2.85 2.06-2.85 1.77 0 1.79 1.65 1.79 2.94V20h3.3v-6.06c0-2.86-.62-5.06-3.96-5.06-1.6 0-2.68.88-3.12 1.72h-.05V9.9h-3.17V20Z',
			'x'         => 'M17.5 3h3.1l-6.8 7.8L21.8 21h-6.2l-4.9-6.4L5.1 21H2l7.3-8.3L2.5 3h6.4l4.4 5.8L17.5 3Zm-1.1 16.1h1.7L7.7 4.8H5.9l10.5 14.3Z',
			'tiktok'    => 'M16.5 3c.3 2 1.5 3.4 3.5 3.6v2.6c-1.3.1-2.5-.3-3.6-1v5.9c0 4.6-4.4 6.9-8 4.7-2.4-1.5-2.9-5-.9-7.2 1.2-1.3 3-1.8 4.7-1.4v2.7c-.4-.1-.8-.2-1.2-.1-1.2.1-2 1.2-1.8 2.4.2 1.2 1.5 1.9 2.6 1.5.9-.3 1.4-1.1 1.4-2V3h3.3Z',
			'pinterest' => 'M12 3a9 9 0 0 0-3.3 17.4c-.1-.7-.1-1.9 0-2.7l1.1-4.7s-.3-.6-.3-1.4c0-1.3.8-2.3 1.7-2.3.8 0 1.2.6 1.2 1.4 0 .8-.5 2.1-.8 3.3-.2.9.5 1.7 1.4 1.7 1.7 0 3-1.8 3-4.4 0-2.3-1.6-3.9-4-3.9-2.7 0-4.3 2-4.3 4.1 0 .8.3 1.7.7 2.2.1.1.1.2.1.3l-.3 1c0 .2-.1.2-.3.1-1.2-.6-1.9-2.3-1.9-3.7 0-3 2.2-5.8 6.3-5.8 3.3 0 5.9 2.4 5.9 5.5 0 3.3-2.1 6-5 6-1 0-1.9-.5-2.2-1.1l-.6 2.3c-.2.9-.8 2-1.2 2.6A9 9 0 1 0 12 3Z',
			'whatsapp'  => 'M12 3a8.9 8.9 0 0 0-7.7 13.4L3 21l4.7-1.2A8.9 8.9 0 1 0 12 3Zm0 16.2c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.8.7.8-2.7-.2-.3a7.4 7.4 0 1 1 6.5 3.6Zm4.1-5.5c-.2-.1-1.3-.7-1.5-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6 6 0 0 1-3-2.6c-.1-.2 0-.4.1-.5l.4-.5c.1-.2.1-.3 0-.5l-.6-1.5c-.2-.4-.4-.3-.5-.3h-.5c-.2 0-.5.1-.7.3-.7.7-.9 1.7-.5 2.7a9.4 9.4 0 0 0 4.4 4.4c1.3.5 2.3.5 3-.1.3-.3.6-.8.7-1.2v-.5c-.1-.1-.2-.2-.4-.3Z',
			'behance'   => 'M9.3 6.5c1.9 0 3.2.9 3.2 2.7 0 1-.5 1.8-1.4 2.2 1.2.3 1.9 1.3 1.9 2.6 0 2-1.5 3-3.5 3H3V6.5h6.3Zm-.4 4.1c.8 0 1.3-.4 1.3-1.1s-.5-1.1-1.3-1.1H5.5v2.2h3.4Zm.2 4.6c.9 0 1.5-.4 1.5-1.3s-.6-1.3-1.5-1.3H5.5v2.6h3.6Zm9.1-5.9c2.3 0 3.8 1.6 3.8 4v.7h-5.6c.1 1.1.8 1.8 1.9 1.8.8 0 1.4-.3 1.7-.9h1.9c-.4 1.5-1.8 2.5-3.6 2.5-2.4 0-4-1.7-4-4.1s1.6-4 3.9-4Zm1.8 3.3c-.1-1-.8-1.7-1.8-1.7s-1.7.7-1.8 1.7h3.6ZM15.3 7h4.9v1.4h-4.9V7Z',
			'dribbble'  => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm5.9 4.2a7.3 7.3 0 0 1 1.7 4.5c-.3-.1-2.9-.6-5.5-.3l-.4-.9a22 22 0 0 0 4.2-3.3ZM12 4.8c1.7 0 3.3.6 4.5 1.7A18 18 0 0 1 13 9.6 33 33 0 0 0 10.4 5c.5-.1 1-.2 1.6-.2ZM8.6 5.6a38 38 0 0 1 2.6 4.5 25 25 0 0 1-6.6.9 7.4 7.4 0 0 1 4-5.4ZM4.4 12v-.2c1 0 4.4 0 7.5-1l.6 1.2c-3 .9-5.6 3.3-6.6 5A7.2 7.2 0 0 1 4.4 12Zm7.6 7.2c-1.6 0-3.1-.5-4.3-1.4.7-1.5 2.7-3.7 6.1-4.5a30 30 0 0 1 1.6 5.3c-1 .4-2.1.6-3.4.6Zm5-1.5a32 32 0 0 0-1.5-5c2.4-.3 4.5.3 4.8.4a7.4 7.4 0 0 1-3.3 4.6Z',
			'threads'   => 'M16.9 11.4c-.1 0-.2-.1-.3-.1-.2-3.3-2-5.2-5-5.2-2 0-3.6.8-4.6 2.3l1.7 1.2c.7-1.1 1.8-1.3 2.9-1.3 1.2 0 2.1.4 2.6 1.1.4.5.6 1.2.7 2.1-.8-.1-1.6-.2-2.5-.2-2.9 0-4.8 1.4-4.7 3.5 0 1 .5 1.9 1.4 2.5.7.5 1.7.7 2.7.7 1.3-.1 2.4-.6 3.1-1.5.6-.7.9-1.5 1.1-2.6.7.4 1.2 1 1.5 1.7.5 1.1.5 3-1 4.5-1.3 1.3-2.9 1.9-5.4 1.9-2.7 0-4.7-.9-6-2.6C2.7 17.7 2.1 15.4 2 12c0-3.4.5-5.7 1.8-7.4C5.1 3 7.1 2 9.8 2c2.7 0 4.8 1 6.1 2.7.7.9 1.2 2 1.4 3.3l2-.5c-.3-1.7-1-3.1-1.9-4.3C15.7 1 13.1 0 9.8 0 6.5 0 3.9 1.2 2.2 3.5.6 5.7 0 8.4 0 12c0 3.6.6 6.3 2.2 8.5C3.9 22.8 6.5 24 9.8 24c3 0 5.1-.8 6.9-2.5 2.3-2.3 2.2-5.2 1.5-6.9-.5-1.3-1.5-2.4-2.9-3.1l-.4-.1Zm-4.6 4.7c-1.1.1-2.3-.4-2.4-1.4-.1-.8.5-1.6 2.6-1.6.7 0 1.4.1 2.1.2-.2 2.1-1.2 2.7-2.3 2.8Z',
		);

		return $paths[ $platform ] ?? '';
	}

	/**
	 * Renders one platform's mark as an inline SVG.
	 *
	 * Marked aria-hidden because the link around it already carries the
	 * platform name as its accessible label (GR-05) — announcing both would
	 * read the platform out twice.
	 *
	 * @param string $platform Platform slug.
	 * @param int    $size     Rendered size in pixels.
	 * @return string SVG markup, or an empty string for an unknown platform.
	 */
	public static function svg( string $platform, int $size = 18 ): string {
		$path = self::path( $platform );
		if ( '' === $path ) {
			return '';
		}

		return sprintf(
			'<svg viewBox="0 0 24 24" width="%1$d" height="%1$d" fill="currentColor" aria-hidden="true" focusable="false"><path d="%2$s" /></svg>',
			$size,
			esc_attr( $path )
		);
	}
}
