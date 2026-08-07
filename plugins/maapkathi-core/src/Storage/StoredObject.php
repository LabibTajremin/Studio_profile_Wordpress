<?php
/**
 * Value object returned by every storage adapter's put() call.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Storage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable record describing a file that was just written to storage.
 */
final class StoredObject {

	/**
	 * Creates the value object.
	 *
	 * @param string $key   Storage key the object was written under.
	 * @param string $url   Direct, publicly reachable URL for the object.
	 * @param int    $bytes Size of the object in bytes.
	 * @param string $mime  MIME type of the object.
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $url,
		public readonly int $bytes,
		public readonly string $mime
	) {}
}
