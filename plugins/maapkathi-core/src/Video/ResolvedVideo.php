<?php
/**
 * Resolved video value object.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Video;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single shape templates render video from — never raw admin input.
 */
final class ResolvedVideo {

	/**
	 * @param string  $kind          'file' | 'youtube' | 'vimeo'.
	 * @param string  $src           Playable/embeddable URL.
	 * @param ?string $poster        Poster image URL, if any.
	 * @param bool    $is_background Whether this video plays as a background loop.
	 */
	public function __construct(
		public readonly string $kind,
		public readonly string $src,
		public readonly ?string $poster,
		public readonly bool $is_background
	) {}
}
