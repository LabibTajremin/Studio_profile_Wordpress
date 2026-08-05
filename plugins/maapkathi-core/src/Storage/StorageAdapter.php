<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Storage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage abstraction (§6). Every adapter must pass the shared contract test
 * suite in tests/Integration/Storage/StorageContractTest.php.
 */
interface StorageAdapter {

	public function put( string $key, string $tmp_path, string $mime, string $visibility ): StoredObject;

	public function delete( string $key ): void;

	/**
	 * Must return a direct URL served statically by the web server for local
	 * storage — never a PHP endpoint that reads the file with readfile()
	 * (§6.1 rule 1).
	 */
	public function url( string $key ): string;

	public function exists( string $key ): bool;

	public function driver_code(): int;
}
