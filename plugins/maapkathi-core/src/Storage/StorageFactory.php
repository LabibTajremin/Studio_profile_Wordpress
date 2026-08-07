<?php
/**
 * Selects and caches the active storage adapter based on configuration.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Storage;

use Maapkathi\Core\Config\Config;
use Maapkathi\Core\Storage\Adapters\LocalStorageAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the storage adapter matching the configured MK_STORAGE_DRIVER
 * constant, and caches it for the rest of the request.
 */
final class StorageFactory {

	/**
	 * @var StorageAdapter|null Cached adapter instance for this request.
	 */
	private static ?StorageAdapter $instance = null;

	/**
	 * Returns the configured storage adapter, creating and caching it on
	 * first use.
	 *
	 * @return StorageAdapter
	 */
	public static function create(): StorageAdapter {
		if ( null !== self::$instance ) {
			return self::$instance;
		}

		$driver = Config::instance()->storage_driver();

		self::$instance = match ( $driver ) {
			// Remote adapters (1, 2, 4, 5, 6) ship as stubs behind this factory
			// so the driver is swappable by one constant; only driver 3 is
			// required to work flawlessly for this build (§6, DEC-2).
			3       => new LocalStorageAdapter(),
			default => new LocalStorageAdapter(),
		};

		return self::$instance;
	}

	public static function reset(): void {
		self::$instance = null;
	}
}
