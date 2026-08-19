<?php
/**
 * Gallery (masonry) item rendering (FR-04).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Gallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the gallery's item markup.
 *
 * Lives in the plugin rather than the theme because the "Load more"
 * endpoint has to produce byte-identical markup to the first paint — if
 * the two ever drifted, appended rows would style differently from the
 * ones already on screen.
 */
final class GalleryRenderer {

	/**
	 * Renders one page of gallery items.
	 *
	 * @param \WP_Post[]          $projects Projects to render.
	 * @param array<string,mixed> $options  Rendering options: click ('lightbox'|'link'), offset (int).
	 * @return string Markup, already escaped.
	 */
	public static function items( array $projects, array $options = array() ): string {
		$click  = 'link' === ( $options['click'] ?? 'lightbox' ) ? 'link' : 'lightbox';
		$offset = absint( $options['offset'] ?? 0 );
		$out    = '';
		$index  = $offset;

		foreach ( $projects as $project ) {
			$item = self::item( $project, $click, $index );
			if ( '' === $item ) {
				continue;
			}
			$out .= $item;
			++$index;
		}

		return $out;
	}

	/**
	 * Renders a single gallery item, or an empty string when it cannot be
	 * rendered.
	 *
	 * FR-04.12: a project whose image file is missing is skipped entirely
	 * rather than rendered as an empty frame or a zero-height cell, either
	 * of which would leave a hole in the packing.
	 *
	 * @param \WP_Post $project Project post.
	 * @param string   $click   Either 'lightbox' or 'link'.
	 * @param int      $index   Position in the whole gallery, for the lightbox counter.
	 * @return string
	 */
	private static function item( \WP_Post $project, string $click, int $index ): string {
		$attachment_id = (int) get_post_thumbnail_id( $project );
		if ( ! $attachment_id ) {
			return '';
		}

		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! is_array( $meta ) || empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return '';
		}

		$full = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $full ) {
			return '';
		}

		$width  = (int) $meta['width'];
		$height = (int) $meta['height'];
		$title  = get_the_title( $project );

		// A panorama so extreme that it would render as a hairline across
		// the column gets its displayed ratio capped; the lightbox still
		// shows the whole thing. Everything else keeps its true ratio, so
		// nothing is cropped (FR-04.2).
		$ratio     = $width / max( 1, $height );
		$capped    = $ratio < 0.35 || $ratio > 4.0;
		$safe_w    = $capped ? 1000 : $width;
		$safe_h    = $capped ? (int) round( 1000 / min( 4.0, max( 0.35, $ratio ) ) ) : $height;
		$image_tag = wp_get_attachment_image(
			$attachment_id,
			'large',
			false,
			array(
				'class'    => 'mk-masonry__image',
				// Explicit dimensions plus srcset/sizes, so the browser
				// reserves the right box before the file arrives and the
				// layout never shifts (FR-04.5).
				'loading'  => $index < 4 ? 'eager' : 'lazy',
				'decoding' => 'async',
				'sizes'    => '(min-width: 1200px) 25vw, (min-width: 992px) 33vw, (min-width: 768px) 50vw, 100vw',
				'alt'      => $title,
			)
		);

		if ( '' === $image_tag ) {
			return '';
		}

		$classes = 'mk-masonry__item';
		if ( $capped ) {
			$classes .= ' mk-masonry__item--capped';
		}

		// --mk-natural-w stops a small image being blown up past its own
		// resolution into a blur (FR-04.11); --mk-ratio reserves the box.
		$style = sprintf(
			'--mk-ratio: %1$d / %2$d; --mk-natural-w: %3$dpx;',
			$safe_w,
			$safe_h,
			$width
		);

		$inner = $image_tag . sprintf(
			'<span class="mk-masonry__overlay" aria-hidden="true"><span class="mk-masonry__overlay-title">%s</span></span>',
			esc_html( $title )
		);

		if ( 'link' === $click ) {
			$anchor = sprintf(
				'<a class="mk-masonry__link" href="%s">%s</a>',
				esc_url( (string) get_permalink( $project ) ),
				$inner
			);
		} else {
			$anchor = sprintf(
				'<a class="mk-masonry__link" href="%1$s" data-lightbox-item data-alt="%2$s" data-caption="%2$s">%3$s</a>',
				esc_url( $full ),
				esc_attr( $title ),
				$inner
			);
		}

		return sprintf(
			'<li class="%1$s" style="%2$s">%3$s</li>',
			esc_attr( $classes ),
			esc_attr( $style ),
			$anchor
		);
	}
}
