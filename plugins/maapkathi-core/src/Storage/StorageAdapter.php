<?php
/**
 * Contract every storage driver (local, remote) must implement.
 *
 * @package maapkathi-core
 */

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

	/**
	 * Writes a local temp file into storage under the given key.
	 *
	 * @param string $key        Storage key to write the object under.
	 * @param string $tmp_path   Absolute path of the local temp file to store.
	 * @param string $mime       MIME type of the file.
	 * @param string $visibility Visibility of the stored object (e.g. 'public').
	 * @return StoredObject
	 */
	public function put( string $key, string $tmp_path, string $mime, string $visibility ): StoredObject;

	/**
	 * Deletes the object stored under the given key, if it exists.
	 *
	 * @param string $key Storage key of the object to delete.
	 * @return void
	 */
	public function delete( string $key ): void;

	/**
	 * Must return a direct URL served statically by the web server for local
	 * storage — never a PHP endpoint that reads the file with readfile()
	 * (§6.1 rule 1).
	 *
	 * @param string $key Storage key of the object.
	 * @return string
	 */
	public function url( string $key ): string;

	/**
	 * Checks whether an object exists under the given key.
	 *
	 * @param string $key Storage key to check.
	 * @return bool
	 */
	public function exists( string $key ): bool;

	/**
	 * Numeric driver identifier, matching the MK_STORAGE_DRIVER constant
	 * that selects this adapter.
	 *
	 * @return int
	 */
	public function driver_code(): int;
}
