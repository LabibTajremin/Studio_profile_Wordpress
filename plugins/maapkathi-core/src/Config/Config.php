<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and validates the §5 numeric-code configuration constants.
 * Fails loudly (admin notice) at boot rather than silently at runtime.
 */
final class Config {

	private const STORAGE_DRIVERS = array( 1, 2, 3, 4, 5, 6 );
	private const VIDEO_DRIVERS   = array( 0, 1, 2, 3, 4 );
	private const CACHE_DRIVERS   = array( 1, 2 );
	private const MAIL_DRIVERS    = array( 0, 1, 2 );

	private static ?Config $instance = null;

	/** @var string[] */
	private array $errors = array();

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function storage_driver(): int {
		return $this->constant_int( 'MK_STORAGE_DRIVER', 3 );
	}

	public function video_driver(): int {
		return $this->constant_int( 'MK_VIDEO_DRIVER', 0 );
	}

	public function cache_driver(): int {
		return $this->constant_int( 'MK_CACHE_DRIVER', 1 );
	}

	public function mail_driver(): int {
		return $this->constant_int( 'MK_MAIL_DRIVER', 0 );
	}

	public function local_storage_dir(): string {
		return defined( 'MK_LOCAL_STORAGE_DIR' ) ? (string) MK_LOCAL_STORAGE_DIR : WP_CONTENT_DIR . '/uploads/maapkathi';
	}

	public function max_video_bytes(): int {
		return $this->constant_int( 'MK_MAX_VIDEO_BYTES', 200 * 1024 * 1024 );
	}

	public function max_image_bytes(): int {
		return $this->constant_int( 'MK_MAX_IMAGE_BYTES', 10 * 1024 * 1024 );
	}

	public function max_gif_bytes(): int {
		return $this->constant_int( 'MK_MAX_GIF_BYTES', 8 * 1024 * 1024 );
	}

	public function chunk_bytes(): int {
		return $this->constant_int( 'MK_CHUNK_BYTES', 2 * 1024 * 1024 );
	}

	public function hero_slide_seconds(): int {
		return $this->constant_int( 'MK_HERO_SLIDE_SECONDS', 6 );
	}

	public function max_hero_hold_seconds(): int {
		return $this->constant_int( 'MK_MAX_HERO_HOLD_SECONDS', 20 );
	}

	/**
	 * Validates all driver codes and required credentials. Returns a list of
	 * human-readable error strings; empty means configuration is valid.
	 *
	 * @return string[]
	 */
	public function validate(): array {
		$this->errors = array();

		$this->check_in_set( 'MK_STORAGE_DRIVER', $this->storage_driver(), self::STORAGE_DRIVERS );
		$this->check_in_set( 'MK_VIDEO_DRIVER', $this->video_driver(), self::VIDEO_DRIVERS );
		$this->check_in_set( 'MK_CACHE_DRIVER', $this->cache_driver(), self::CACHE_DRIVERS );
		$this->check_in_set( 'MK_MAIL_DRIVER', $this->mail_driver(), self::MAIL_DRIVERS );

		$this->check_driver_credentials();

		if ( $this->hero_slide_seconds() < 3 || $this->hero_slide_seconds() > 20 ) {
			$this->errors[] = 'MK_HERO_SLIDE_SECONDS must be between 3 and 20.';
		}

		return $this->errors;
	}

	public function validate_or_notice(): void {
		$errors = $this->validate();

		if ( empty( $errors ) ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () use ( $errors ): void {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>Maapkathi configuration error(s):</strong></p><ul>';
				foreach ( $errors as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul></div>';
			}
		);
	}

	private function check_in_set( string $constant_name, int $value, array $allowed ): void {
		if ( ! in_array( $value, $allowed, true ) ) {
			$this->errors[] = sprintf(
				'%s is set to %d, which is not a recognised driver code (%s).',
				$constant_name,
				$value,
				implode( ', ', $allowed )
			);
		}
	}

	private function check_driver_credentials(): void {
		$driver = $this->storage_driver();

		// Driver 3 (Local) requires no external credentials — the shipped default.
		if ( 3 === $driver ) {
			return;
		}

		$required = array(
			1 => array( 'MK_S3_KEY', 'MK_S3_SECRET', 'MK_S3_BUCKET', 'MK_S3_REGION' ),
			2 => array( 'MK_GDRIVE_SERVICE_ACCOUNT_JSON' ),
			4 => array( 'MK_R2_KEY', 'MK_R2_SECRET', 'MK_R2_BUCKET', 'MK_R2_ENDPOINT' ),
			5 => array( 'MK_SUPABASE_URL', 'MK_SUPABASE_BUCKET', 'MK_SUPABASE_SERVICE_KEY' ),
			6 => array( 'MK_BUNNY_STORAGE_ZONE', 'MK_BUNNY_ACCESS_KEY' ),
		);

		if ( ! isset( $required[ $driver ] ) ) {
			return;
		}

		foreach ( $required[ $driver ] as $constant_name ) {
			if ( ! defined( $constant_name ) || '' === (string) constant( $constant_name ) ) {
				$this->errors[] = sprintf(
					'MK_STORAGE_DRIVER=%d requires %s to be defined in wp-config.php.',
					$driver,
					$constant_name
				);
			}
		}
	}

	private function constant_int( string $name, int $default ): int {
		return defined( $name ) ? (int) constant( $name ) : $default;
	}
}
