<?php
/**
 * Locally generated gradient SVG placeholder images.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Rest;

use Maapkathi\Core\Support\Branding;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * On-the-fly gradient SVG placeholders (§3.5), served locally.
 *
 * Replaces the source app's /api/placeholder/[...spec] route. Deliberately
 * local rather than pointing at a third-party placeholder host: a fresh
 * install must never depend on an external service being reachable, and a
 * client demo must never show a broken image because someone's network
 * blocks placehold.co.
 *
 * Route: /wp-json/maapkathi/v1/placeholder/{width}/{height}?label=Hero
 */
final class PlaceholderController {

	private const NAMESPACE = 'maapkathi/v1';

	private const MAX_DIMENSION = 4000;

	/**
	 * Wire the rest_api_init hook that registers the placeholder route.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the public GET /placeholder/{width}/{height} route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/placeholder/(?P<width>\d+)/(?P<height>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'render' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'width'  => array( 'sanitize_callback' => 'absint' ),
					'height' => array( 'sanitize_callback' => 'absint' ),
					'label'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);
	}

	/**
	 * Build the REST URL for a placeholder image with the given spec.
	 *
	 * @param string $label  Optional label text to draw on the placeholder.
	 * @param int    $width  Requested width in pixels, clamped to MAX_DIMENSION.
	 * @param int    $height Requested height in pixels, clamped to MAX_DIMENSION.
	 * @return string The placeholder image's REST URL.
	 */
	public static function url( string $label = '', int $width = 1600, int $height = 1000 ): string {
		$width  = max( 1, min( self::MAX_DIMENSION, $width ) );
		$height = max( 1, min( self::MAX_DIMENSION, $height ) );

		$url = rest_url( sprintf( '%s/placeholder/%d/%d', self::NAMESPACE, $width, $height ) );

		return $label ? add_query_arg( 'label', rawurlencode( $label ), $url ) : $url;
	}

	/**
	 * Output a gradient placeholder SVG for the requested spec and terminate the request.
	 *
	 * @param \WP_REST_Request $request REST request carrying width, height, and an optional label.
	 */
	public function render( \WP_REST_Request $request ) {
		$width  = max( 1, min( self::MAX_DIMENSION, absint( $request['width'] ) ) );
		$height = max( 1, min( self::MAX_DIMENSION, absint( $request['height'] ) ) );
		$label  = (string) $request->get_param( 'label' );

		$svg = self::svg( $label, $width, $height );

		// Immutable by nature (same spec => same bytes), so let the browser
		// and any page cache hold on to it.
		header( 'Content-Type: image/svg+xml' );
		header( 'Cache-Control: public, max-age=31536000, immutable' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self-built SVG, values escaped in svg().
		exit;
	}

	/**
	 * Build the gradient placeholder SVG markup for a given spec.
	 *
	 * @param string $label  Optional label text to draw centred on the image.
	 * @param int    $width  Image width in pixels.
	 * @param int    $height Image height in pixels.
	 * @return string SVG markup.
	 */
	public static function svg( string $label, int $width, int $height ): string {
		$accent = Branding::accent_hex();
		$dark   = self::shade( $accent, -0.35 );
		$font   = max( 14, (int) round( min( $width, $height ) / 12 ) );

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d" role="img" aria-label="%5$s">'
			. '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0%%" stop-color="%3$s"/><stop offset="100%%" stop-color="%4$s"/>'
			. '</linearGradient></defs>'
			. '<rect width="%1$d" height="%2$d" fill="url(#g)"/>'
			. '%6$s'
			. '</svg>',
			$width,
			$height,
			esc_attr( $accent ),
			esc_attr( $dark ),
			esc_attr( $label ? $label : __( 'Placeholder image', 'maapkathi' ) ),
			$label
				? sprintf(
					'<text x="50%%" y="50%%" dominant-baseline="middle" text-anchor="middle" font-family="Georgia,serif" font-size="%d" fill="%s" opacity="0.85">%s</text>',
					$font,
					esc_attr( \Maapkathi\Core\Theme\Accents::on_accent( $accent ) ),
					esc_html( $label )
				)
				: ''
		);
	}

	/**
	 * Lightens (positive) or darkens (negative) a hex colour.
	 *
	 * @param string $hex    Hex colour string, with or without a leading '#'.
	 * @param float  $amount Shade amount from -1 (fully dark) to 1 (fully light).
	 * @return string The shaded hex colour string, prefixed with '#'.
	 */
	private static function shade( string $hex, float $amount ): string {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return '#' . $hex;
		}

		$out = '#';
		for ( $i = 0; $i < 3; $i++ ) {
			$channel = hexdec( substr( $hex, $i * 2, 2 ) );
			$channel = (int) round( $amount < 0 ? $channel * ( 1 + $amount ) : $channel + ( 255 - $channel ) * $amount );
			$out    .= str_pad( dechex( max( 0, min( 255, $channel ) ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}
}
