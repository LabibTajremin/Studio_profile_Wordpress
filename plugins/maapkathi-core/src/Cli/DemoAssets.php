<?php
/**
 * Locally generated demo image/logo assets for `wp maapkathi seed`.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Cli;

use Maapkathi\Core\Theme\Accents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the demo images and logos used by `wp maapkathi seed`.
 *
 * Images are drawn locally with GD rather than downloaded from a
 * placeholder service: a fresh install must look right with no outbound
 * network access, and a client demo must never show a broken image because
 * some third-party host is unreachable or rate-limiting. Everything lands
 * in the real Media Library through wp_insert_attachment(), so the demo
 * content exercises the same storage path as a genuine upload.
 */
final class DemoAssets {

	/** Deterministic palette, so reseeding produces a consistent look. */
	private const PALETTE = array(
		array( '#6e1f2a', '#2b0d12' ),
		array( '#0f5a5e', '#052326' ),
		array( '#46583c', '#1b2317' ),
		array( '#1e3a5f', '#0b1726' ),
		array( '#9c4a2f', '#3d1b11' ),
		array( '#5d2a4e', '#24101f' ),
		array( '#7d5a2e', '#2f2011' ),
		array( '#34327a', '#14132f' ),
	);

	/**
	 * Whether the GD extension is available to generate demo images.
	 *
	 * @return bool True when both imagecreatetruecolor() and imagepng() exist.
	 */
	public static function available(): bool {
		return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagepng' );
	}

	/**
	 * Creates (or reuses) a demo image attachment.
	 *
	 * Idempotent: the generated slug is stored as post meta, so reseeding
	 * finds the existing attachment instead of duplicating files — which
	 * matters on a 20 GB disk.
	 *
	 * @param string $slug          Unique demo-asset slug used to find/dedupe the attachment.
	 * @param string $label         Text drawn onto the generated image.
	 * @param int    $width         Image width in pixels.
	 * @param int    $height        Image height in pixels.
	 * @param int    $palette_index Index into PALETTE selecting the gradient colours.
	 * @return int Attachment ID, or 0 if generation/upload failed.
	 */
	public static function image( string $slug, string $label, int $width, int $height, int $palette_index = 0 ): int {
		$existing = self::find_existing( $slug );
		if ( $existing ) {
			return $existing;
		}

		if ( ! self::available() ) {
			return 0;
		}

		$png = self::render_png( $label, $width, $height, $palette_index );
		if ( '' === $png ) {
			return 0;
		}

		return self::sideload( $slug, $png, $label );
	}

	/**
	 * Wordmark-style logo on a transparent background, for the client wall
	 * and the site logo.
	 *
	 * @param string $slug          Unique demo-asset slug used to find/dedupe the attachment.
	 * @param string $text          Wordmark text to render.
	 * @param int    $palette_index Index into PALETTE selecting the ink colour.
	 * @return int Attachment ID, or 0 if generation/upload failed.
	 */
	public static function logo( string $slug, string $text, int $palette_index = 0 ): int {
		$existing = self::find_existing( $slug );
		if ( $existing ) {
			return $existing;
		}

		if ( ! self::available() ) {
			return 0;
		}

		$png = self::render_logo_png( $text, $palette_index );
		if ( '' === $png ) {
			return 0;
		}

		return self::sideload( $slug, $png, $text );
	}

	/**
	 * Look up an already-generated demo attachment by its stored slug.
	 *
	 * @param string $slug Demo-asset slug to search for.
	 * @return int Attachment ID, or 0 if none exists yet.
	 */
	private static function find_existing( string $slug ): int {
		$found = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_mk_demo_asset',
				'meta_value'     => $slug,
				'no_found_rows'  => true,
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Render a gradient placeholder image with a centred label as PNG bytes.
	 *
	 * @param string $label         Text drawn onto the image.
	 * @param int    $width         Image width in pixels.
	 * @param int    $height        Image height in pixels.
	 * @param int    $palette_index Index into PALETTE selecting the gradient colours.
	 * @return string Raw PNG bytes, or an empty string on failure.
	 */
	private static function render_png( string $label, int $width, int $height, int $palette_index ): string {
		$image = imagecreatetruecolor( $width, $height );
		if ( ! $image ) {
			return '';
		}

		list( $from, $to ) = self::PALETTE[ $palette_index % count( self::PALETTE ) ];
		$from_rgb          = self::hex_to_rgb( $from );
		$to_rgb            = self::hex_to_rgb( $to );

		// Diagonal gradient.
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x += max( 1, (int) round( $width / 220 ) ) ) {
				$t      = ( ( $x / max( 1, $width ) ) + ( $y / max( 1, $height ) ) ) / 2;
				$colour = imagecolorallocate(
					$image,
					(int) round( $from_rgb[0] + ( $to_rgb[0] - $from_rgb[0] ) * $t ),
					(int) round( $from_rgb[1] + ( $to_rgb[1] - $from_rgb[1] ) * $t ),
					(int) round( $from_rgb[2] + ( $to_rgb[2] - $from_rgb[2] ) * $t )
				);
				imagefilledrectangle( $image, $x, $y, $x + (int) round( $width / 220 ), $y, $colour );
			}
		}

		// Subtle architectural rule lines, so the demo reads as a design
		// studio placeholder rather than a flat colour block.
		$rule = imagecolorallocatealpha( $image, 255, 255, 255, 105 );
		$step = max( 40, (int) round( $height / 9 ) );
		for ( $y = $step; $y < $height; $y += $step ) {
			imageline( $image, 0, $y, $width, $y, $rule );
		}
		for ( $x = $step; $x < $width; $x += $step ) {
			imageline( $image, $x, 0, $x, $height, $rule );
		}

		self::draw_centred_text( $image, $label, $width, $height );

		ob_start();
		imagepng( $image, null, 6 );
		$data = (string) ob_get_clean();
		imagedestroy( $image );

		return $data;
	}

	/**
	 * Render a wordmark-style logo with a transparent background as PNG bytes.
	 *
	 * @param string $text          Wordmark text to render.
	 * @param int    $palette_index Index into PALETTE selecting the ink colour.
	 * @return string Raw PNG bytes, or an empty string on failure.
	 */
	private static function render_logo_png( string $text, int $palette_index ): string {
		$width  = 480;
		$height = 160;

		$image = imagecreatetruecolor( $width, $height );
		if ( ! $image ) {
			return '';
		}

		imagealphablending( $image, false );
		imagesavealpha( $image, true );
		imagefilledrectangle( $image, 0, 0, $width, $height, imagecolorallocatealpha( $image, 0, 0, 0, 127 ) );
		imagealphablending( $image, true );

		list( $ink ) = self::PALETTE[ $palette_index % count( self::PALETTE ) ];
		$rgb         = self::hex_to_rgb( $ink );
		$colour      = imagecolorallocate( $image, $rgb[0], $rgb[1], $rgb[2] );

		// Mark: a filled square with the initial knocked out.
		imagefilledrectangle( $image, 16, 40, 96, 120, $colour );
		$initial = strtoupper( substr( trim( $text ), 0, 1 ) );
		$white   = imagecolorallocate( $image, 255, 255, 255 );
		imagestring( $image, 5, 50, 72, $initial, $white );

		imagestring( $image, 5, 118, 72, strtoupper( $text ), $colour );

		ob_start();
		imagepng( $image, null, 6 );
		$data = (string) ob_get_clean();
		imagedestroy( $image );

		return $data;
	}

	/**
	 * Draw a drop-shadowed, centred, uppercased label onto a GD image.
	 *
	 * @param \GdImage $image  GD image resource to draw onto.
	 * @param string   $label  Text to draw; a no-op when empty.
	 * @param int      $width  Image width in pixels, used to centre the text.
	 * @param int      $height Image height in pixels, used to centre the text.
	 */
	private static function draw_centred_text( $image, string $label, int $width, int $height ): void {
		if ( '' === $label ) {
			return;
		}

		$label = strtoupper( $label );
		$font  = 5;
		$tw    = imagefontwidth( $font ) * strlen( $label );
		$th    = imagefontheight( $font );

		$x = max( 8, (int) round( ( $width - $tw ) / 2 ) );
		$y = max( 8, (int) round( ( $height - $th ) / 2 ) );

		$shadow = imagecolorallocatealpha( $image, 0, 0, 0, 70 );
		$white  = imagecolorallocate( $image, 255, 255, 255 );

		imagestring( $image, $font, $x + 1, $y + 1, $label, $shadow );
		imagestring( $image, $font, $x, $y, $label, $white );
	}

	/**
	 * @return array{0:int,1:int,2:int}
	 */
	private static function hex_to_rgb( string $hex ): array {
		$hex = ltrim( $hex, '#' );
		return array(
			(int) hexdec( substr( $hex, 0, 2 ) ),
			(int) hexdec( substr( $hex, 2, 2 ) ),
			(int) hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/** Writes the bytes into the uploads dir and registers an attachment. */
	private static function sideload( string $slug, string $png, string $title ): int {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_upload_bits( $slug . '.png', null, $png );

		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_title'     => $title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		update_post_meta( $attachment_id, '_mk_demo_asset', $slug );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );

		return (int) $attachment_id;
	}

	/**
	 * Removes every generated demo asset — used by `wp maapkathi seed
	 * --fresh` so a demo can be torn down without hunting through the
	 * Media Library by hand.
	 */
	public static function delete_all(): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_mk_demo_asset',
				'no_found_rows'  => true,
			)
		);

		foreach ( $ids as $id ) {
			wp_delete_attachment( (int) $id, true );
		}

		return count( $ids );
	}
}
