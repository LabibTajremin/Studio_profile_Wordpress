<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Video;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ResolvedVideo {

	public function __construct(
		/** 'file' | 'youtube' | 'vimeo' */
		public readonly string $kind,
		public readonly string $src,
		public readonly ?string $poster,
		public readonly bool $is_background
	) {}
}
