<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Storage;

use Maapkathi\Core\Config\Config;
use Maapkathi\Core\Storage\Adapters\LocalStorageAdapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StorageFactory {

	private static ?StorageAdapter $instance = null;

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
