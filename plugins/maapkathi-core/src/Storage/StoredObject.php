<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Storage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StoredObject {

	public function __construct(
		public readonly string $key,
		public readonly string $url,
		public readonly int $bytes,
		public readonly string $mime
	) {}
}
