<?php
/**
 * Chunked file upload REST endpoint.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Rest;

use Maapkathi\Core\Config\Config;
use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Storage\StorageFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chunked upload endpoint (§6.1 rule 4, §13). Chunk IDs are server-generated
 * (never taken from client input) so reassembly is path-traversal safe, and
 * every chunk is nonce-verified and capability-checked, not just the first.
 */
final class UploadController {

	private const NAMESPACE = 'maapkathi/v1';

	/**
	 * Wire the rest_api_init hook and schedule the orphaned-chunk garbage collector.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'mk_gc_orphaned_chunks', array( $this, 'gc_orphaned_chunks' ) );

		if ( ! wp_next_scheduled( 'mk_gc_orphaned_chunks' ) ) {
			wp_schedule_event( time(), 'hourly', 'mk_gc_orphaned_chunks' );
		}
	}

	/**
	 * Register the upload/chunk and upload/complete routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/upload/chunk',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chunk' ),
				'permission_callback' => array( $this, 'can_upload' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/upload/complete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_complete' ),
				'permission_callback' => array( $this, 'can_upload' ),
			)
		);
	}

	/**
	 * Permission callback for both upload routes.
	 *
	 * @return bool True when the current user may edit content or upload files.
	 */
	public function can_upload(): bool {
		return current_user_can( Roles::CAP_EDIT_CONTENT ) || current_user_can( 'upload_files' );
	}

	/**
	 * Store one uploaded chunk on disk under its server-issued upload_id.
	 *
	 * @param \WP_REST_Request $request REST request carrying upload_id, chunk_index, and the chunk file.
	 * @return array<string,int|string>|\WP_Error Chunk receipt on success, or an error.
	 */
	public function handle_chunk( \WP_REST_Request $request ) {
		$upload_id   = sanitize_key( (string) $request->get_param( 'upload_id' ) );
		$chunk_index = absint( $request->get_param( 'chunk_index' ) );
		$file        = $request->get_file_params()['chunk'] ?? null;

		if ( ! $upload_id || null === $file ) {
			return new \WP_Error( 'mk_bad_chunk', __( 'Missing upload_id or chunk.', 'maapkathi' ), array( 'status' => 400 ) );
		}

		// upload_id is server-issued (returned from an earlier /upload/chunk
		// call with chunk_index=0) — sanitize_key() strips anything that
		// could traverse outside the chunk directory.
		$dir = trailingslashit( $this->chunks_dir() ) . $upload_id;
		wp_mkdir_p( $dir );

		$dest = trailingslashit( $dir ) . 'chunk_' . $chunk_index;
		if ( ! @move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return new \WP_Error( 'mk_chunk_write_failed', __( 'Could not store chunk.', 'maapkathi' ), array( 'status' => 500 ) );
		}

		return array(
			'upload_id' => $upload_id,
			'received'  => $chunk_index,
		);
	}

	public function handle_complete( \WP_REST_Request $request ) {
		$upload_id = sanitize_key( (string) $request->get_param( 'upload_id' ) );
		$total     = absint( $request->get_param( 'total_chunks' ) );
		$filename  = sanitize_file_name( (string) $request->get_param( 'filename' ) );

		$dir = trailingslashit( $this->chunks_dir() ) . $upload_id;
		if ( ! is_dir( $dir ) ) {
			return new \WP_Error( 'mk_upload_not_found', __( 'Upload session not found.', 'maapkathi' ), array( 'status' => 404 ) );
		}

		$assembled = trailingslashit( $dir ) . 'assembled_' . wp_generate_uuid4();
		$out       = fopen( $assembled, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		for ( $i = 0; $i < $total; $i++ ) {
			$chunk_path = trailingslashit( $dir ) . 'chunk_' . $i;
			if ( ! file_exists( $chunk_path ) ) {
				fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				return new \WP_Error( 'mk_missing_chunk', sprintf( 'Missing chunk %d.', $i ), array( 'status' => 400 ) );
			}
			fwrite( $out, file_get_contents( $chunk_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_get_contents
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$validation = $this->validate_assembled_file( $assembled, $filename );
		if ( is_wp_error( $validation ) ) {
			wp_delete_file( $assembled );
			$this->cleanup_dir( $dir );
			return $validation;
		}

		$ext = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
		$key = gmdate( 'Y/m/' ) . wp_generate_uuid4() . '.' . $ext;

		$stored = StorageFactory::create()->put( $key, $assembled, $validation, 'public' );

		$this->cleanup_dir( $dir );

		return array(
			'url'   => $stored->url,
			'key'   => $stored->key,
			'bytes' => $stored->bytes,
		);
	}

	/**
	 * Magic-byte validation (§6.3, §13) — extension and client Content-Type
	 * are never trusted. Returns the detected mime, or a WP_Error.
	 */
	private function validate_assembled_file( string $path, string $original_filename ) {
		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$header = $handle ? fread( $handle, 16 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		if ( $handle ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		$mime = match ( true ) {
			str_starts_with( $header, "\xFF\xD8\xFF" )        => 'image/jpeg',
			str_starts_with( $header, "\x89PNG" )              => 'image/png',
			str_starts_with( substr( $header, 8, 4 ), 'WEBP' )  => 'image/webp',
			str_starts_with( $header, 'GIF87a' ) || str_starts_with( $header, 'GIF89a' ) => 'image/gif',
			str_contains( substr( $header, 4, 8 ), 'ftyp' )    => 'video/mp4',
			default => null,
		};

		if ( null === $mime ) {
			return new \WP_Error( 'mk_invalid_file_type', __( 'Unrecognised file type (checked by content, not extension).', 'maapkathi' ), array( 'status' => 415 ) );
		}

		$config = Config::instance();
		$size   = filesize( $path );

		if ( 'video/mp4' === $mime && $size > $config->max_video_bytes() ) {
			return new \WP_Error( 'mk_video_too_large', __( 'Video exceeds the maximum allowed size.', 'maapkathi' ), array( 'status' => 413 ) );
		}
		if ( 'image/gif' === $mime && $size > $config->max_gif_bytes() ) {
			return new \WP_Error( 'mk_gif_too_large', __( 'GIF exceeds the maximum allowed size.', 'maapkathi' ), array( 'status' => 413 ) );
		}
		if ( in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) && $size > $config->max_image_bytes() ) {
			return new \WP_Error( 'mk_image_too_large', __( 'Image exceeds the maximum allowed size.', 'maapkathi' ), array( 'status' => 413 ) );
		}

		return $mime;
	}

	/**
	 * Removes chunk sessions older than 24h that were never completed, so
	 * abandoned uploads cannot fill the disk (§6.1 rule 4, §13).
	 */
	public function gc_orphaned_chunks(): void {
		$dir = $this->chunks_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( glob( trailingslashit( $dir ) . '*', GLOB_ONLYDIR ) ?: array() as $session_dir ) {
			if ( filemtime( $session_dir ) < time() - DAY_IN_SECONDS ) {
				$this->cleanup_dir( $session_dir );
			}
		}
	}

	private function chunks_dir(): string {
		return trailingslashit( Config::instance()->local_storage_dir() ) . '.chunks';
	}

	private function cleanup_dir( string $dir ): void {
		foreach ( glob( trailingslashit( $dir ) . '*' ) ?: array() as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}
