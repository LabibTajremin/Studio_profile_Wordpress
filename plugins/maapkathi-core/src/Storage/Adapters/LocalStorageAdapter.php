<?php
/**
 * Local filesystem storage adapter (driver 3, the default).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Storage\Adapters;

use Maapkathi\Core\Config\Config;
use Maapkathi\Core\Storage\StorageAdapter;
use Maapkathi\Core\Storage\StoredObject;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default shipped storage driver (§6, §6.1). Writes under
 * MK_LOCAL_STORAGE_DIR/{yyyy}/{mm}/{uuid}.{ext} and returns a direct static
 * URL — never a PHP streaming endpoint, so video delivery costs zero PHP
 * workers and LiteSpeed serves Range requests (HTTP 206) natively.
 */
final class LocalStorageAdapter implements StorageAdapter {

	/**
	 * Numeric identifier for this driver.
	 *
	 * @return int
	 */
	public function driver_code(): int {
		return 3;
	}

	/**
	 * Copies a local temp file into the storage directory under the given
	 * key, creating the destination directory and the protective
	 * .htaccess as needed.
	 *
	 * @param string $key        Storage key to write the object under.
	 * @param string $tmp_path   Absolute path of the local temp file to store.
	 * @param string $mime       MIME type of the file.
	 * @param string $visibility Unused — local storage has no per-object visibility.
	 * @return StoredObject
	 */
	public function put( string $key, string $tmp_path, string $mime, string $visibility ): StoredObject {
		$this->ensure_htaccess();

		$destination = $this->path_for_key( $key );
		wp_mkdir_p( dirname( $destination ) );

		// Suppressed deliberately: copy() emits its own PHP warning on
		// failure in addition to a false return value, and the false
		// return is already turned into a proper exception below, so the
		// warning would just be redundant noise in the log.
		if ( ! @copy( $tmp_path, $destination ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- failure is already converted into a RuntimeException below; the suppressed warning would be redundant.
			throw new \RuntimeException( 'Unable to write file to local storage: ' . esc_html( $key ) );
		}

		return new StoredObject(
			key: $key,
			url: $this->url( $key ),
			bytes: (int) filesize( $destination ),
			mime: $mime
		);
	}

	/**
	 * Deletes the object stored under the given key, if it exists.
	 *
	 * @param string $key Storage key of the object to delete.
	 * @return void
	 */
	public function delete( string $key ): void {
		$path = $this->path_for_key( $key );
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Builds the direct, statically-served URL for an object.
	 *
	 * @param string $key Storage key of the object.
	 * @return string
	 */
	public function url( string $key ): string {
		$base_url = $this->base_url();

		return trailingslashit( $base_url ) . ltrim( $key, '/' );
	}

	/**
	 * Checks whether an object exists under the given key.
	 *
	 * @param string $key Storage key to check.
	 * @return bool
	 */
	public function exists( string $key ): bool {
		return file_exists( $this->path_for_key( $key ) );
	}

	/**
	 * Total bytes and inode (file) count used under the local storage dir, for
	 * the admin Dashboard quota band (§6.1 rule 5).
	 *
	 * @return array{bytes:int,files:int}
	 */
	public function usage(): array {
		$dir = $this->base_dir();

		if ( ! is_dir( $dir ) ) {
			return array(
				'bytes' => 0,
				'files' => 0,
			);
		}

		$bytes = 0;
		$files = 0;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				++$files;
				$bytes += $file->getSize();
			}
		}

		return array(
			'bytes' => $bytes,
			'files' => $files,
		);
	}

	/**
	 * Resolves a storage key to its absolute filesystem path.
	 *
	 * @param string $key Storage key.
	 * @return string
	 */
	private function path_for_key( string $key ): string {
		return trailingslashit( $this->base_dir() ) . ltrim( $key, '/' );
	}

	/**
	 * Absolute path of the configured local storage directory.
	 *
	 * @return string
	 */
	private function base_dir(): string {
		return Config::instance()->local_storage_dir();
	}

	/**
	 * Public base URL corresponding to the local storage directory,
	 * derived from the WordPress uploads base URL.
	 *
	 * @return string
	 */
	private function base_url(): string {
		$upload_dir = wp_get_upload_dir();
		$relative   = str_replace( wp_normalize_path( $upload_dir['basedir'] ), '', wp_normalize_path( $this->base_dir() ) );
		return $upload_dir['baseurl'] . $relative;
	}

	/**
	 * Denies PHP execution inside the media directory — a hard security
	 * requirement (§6, §13). Written once and re-checked on every put().
	 */
	private function ensure_htaccess(): void {
		$dir = $this->base_dir();
		wp_mkdir_p( $dir );

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( file_exists( $htaccess ) ) {
			return;
		}

		$contents = "php_flag engine off\n" .
			"<FilesMatch \"\\.ph(p[0-9]?|tml)\$\">\n" .
			"  Require all denied\n" .
			"</FilesMatch>\n";

		file_put_contents( $htaccess, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
}
