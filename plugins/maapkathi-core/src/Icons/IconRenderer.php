<?php
/**
 * Resolves and renders a content item's icon (FR-06.4, FR-06.9, FR-07.2).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One place that decides which of a post's three icon fields wins, so the
 * services section, the services page and the values section cannot drift
 * into three slightly different answers.
 */
final class IconRenderer {

	/**
	 * Renders the icon for one post, or an empty string when it has none.
	 *
	 * Resolution order, most specific first:
	 *   1. an uploaded SVG (the admin went out of their way to supply it);
	 *   2. an icon chosen from the bundled library;
	 *   3. a legacy dashicon class, kept so content created before the
	 *      library existed still shows its icon.
	 *
	 * @param int $post_id Post to render the icon for.
	 * @return string Markup, already safe to echo.
	 */
	public static function render( int $post_id ): string {
		$upload = absint( get_post_meta( $post_id, 'mk_icon_svg', true ) );
		if ( $upload ) {
			return self::render_upload( $upload );
		}

		$icon_id = (string) get_post_meta( $post_id, 'mk_icon_id', true );
		if ( '' !== $icon_id && IconLibrary::has( $icon_id ) ) {
			return IconLibrary::svg( $icon_id, 0, 'mk-icon mk-icon--library' );
		}

		$legacy = (string) get_post_meta( $post_id, 'mk_icon', true );
		if ( '' !== $legacy ) {
			return sprintf(
				'<span class="mk-icon mk-icon--dashicon dashicons %s" aria-hidden="true"></span>',
				esc_attr( $legacy )
			);
		}

		return '';
	}

	/**
	 * Whether a post has any icon at all.
	 *
	 * Lets a template skip the icon slot entirely rather than rendering an
	 * empty box that still takes up its configured width.
	 *
	 * @param int $post_id Post to check.
	 * @return bool
	 */
	public static function has( int $post_id ): bool {
		return '' !== self::render( $post_id );
	}

	/**
	 * Renders an uploaded icon.
	 *
	 * An SVG is inlined so `currentColor` inside it picks up the accent.
	 * A raster file cannot be recoloured at all, so it is rendered as a
	 * plain image in its own colours rather than being tinted into
	 * something the designer never intended (FR-06.9).
	 *
	 * @param int $attachment_id Uploaded icon attachment.
	 * @return string Markup, already safe to echo.
	 */
	private static function render_upload( int $attachment_id ): string {
		$mime = (string) get_post_mime_type( $attachment_id );

		if ( 'image/svg+xml' !== $mime ) {
			// Returns an empty string for a missing attachment, which is
			// exactly the "no icon" answer the caller wants anyway.
			return wp_get_attachment_image(
				$attachment_id,
				'thumbnail',
				false,
				array(
					'class'    => 'mk-icon mk-icon--raster',
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
		}

		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a local uploaded file, not a remote request.
		$markup = (string) file_get_contents( $path );
		if ( '' === $markup ) {
			return '';
		}

		return self::sanitize_svg( $markup );
	}

	/**
	 * Strips an uploaded SVG down to something safe to inline, and to
	 * something the accent colour can actually reach.
	 *
	 * Two separate jobs:
	 *   - security: scripts, event handlers, external references and
	 *     stylesheets come out, because this markup is about to be inlined
	 *     into the page rather than sandboxed in an <img>;
	 *   - colour: hardcoded fill/stroke values come out too, so the icon
	 *     inherits currentColor and follows the accent (FR-06.4). An icon
	 *     that keeps its own #333 fill would silently ignore the theme.
	 *
	 * @param string $markup Raw SVG file contents.
	 * @return string Sanitized SVG markup, or an empty string if unusable.
	 */
	private static function sanitize_svg( string $markup ): string {
		// Everything before the opening <svg> (XML prolog, doctype,
		// comments) is dropped rather than parsed.
		$start = stripos( $markup, '<svg' );
		if ( false === $start ) {
			return '';
		}
		$markup = substr( $markup, $start );

		// Whole elements that must never survive inlining.
		$markup = (string) preg_replace( '#<(script|foreignObject|style|iframe|use)\b[^>]*>.*?</\1>#is', '', $markup );
		$markup = (string) preg_replace( '#<(script|foreignObject|style|iframe|use)\b[^>]*/?>#is', '', $markup );

		// Inline event handlers and javascript: / data: references.
		$markup = (string) preg_replace( '#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $markup );
		$markup = (string) preg_replace( '#\s(?:href|xlink:href)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $markup );

		// Hardcoded colours, so currentColor governs. "none" is preserved:
		// it is a shape instruction, not a colour choice.
		$markup = (string) preg_replace_callback(
			'#\s(fill|stroke)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i',
			static function ( array $found ): string {
				$value = strtolower( trim( $found[3] ?? ( $found[4] ?? ( $found[5] ?? '' ) ) ) );
				if ( 'none' === $value || 'currentcolor' === $value ) {
					return $found[0];
				}
				return ' ' . strtolower( $found[1] ) . '="currentColor"';
			},
			$markup
		);

		// A style attribute could reintroduce a colour or a url() reference.
		$markup = (string) preg_replace( '#\sstyle\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $markup );

		// The width/height baked into the file would defeat the admin's size
		// setting, so they go and CSS sizes the icon instead. viewBox stays,
		// because without it the drawing has no coordinate system.
		$markup = (string) preg_replace( '#\s(width|height)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $markup );

		return str_replace( '<svg', '<svg class="mk-icon mk-icon--upload" aria-hidden="true" focusable="false"', $markup );
	}
}
