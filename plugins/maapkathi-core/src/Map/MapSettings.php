<?php
/**
 * Map configuration and embed-URL construction (FR-05).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Map;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything the map block needs, and the one place that decides whether
 * it can render at all.
 */
final class MapSettings {

	public const OPTION = 'mk_map_settings';

	/**
	 * Shipped defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled_contact' => true,
			'enabled_home'    => false,
			'provider'        => 'google-embed',
			'address'         => '',
			'lat'             => '',
			'lng'             => '',
			'zoom'            => 15,
			'h_desktop'       => 420,
			'h_mobile'        => 280,
			'style'           => 'auto',
			'marker'          => '',
			'api_key'         => '',
			'show_directions' => true,
		);
	}

	/**
	 * Providers the admin can choose between.
	 *
	 * @return array<string,string> slug => human label
	 */
	public static function providers(): array {
		return array(
			'google-embed' => __( 'Google Maps (no API key needed)', 'maapkathi' ),
			'osm'          => __( 'OpenStreetMap (no API key needed)', 'maapkathi' ),
			'google-js'    => __( 'Google Maps JavaScript API (needs a key)', 'maapkathi' ),
		);
	}

	/**
	 * Current map settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $stored ) ? array_merge( self::defaults(), $stored ) : self::defaults() );
	}

	/**
	 * Validates and clamps a settings payload.
	 *
	 * @param array<string,mixed> $input Raw payload.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults = self::defaults();

		return array(
			'enabled_contact' => ! empty( $input['enabled_contact'] ),
			'enabled_home'    => ! empty( $input['enabled_home'] ),
			'provider'        => isset( self::providers()[ $input['provider'] ?? '' ] ) ? (string) $input['provider'] : $defaults['provider'],
			'address'         => sanitize_text_field( (string) ( $input['address'] ?? '' ) ),
			'lat'             => self::sanitize_coordinate( $input['lat'] ?? '', 90.0 ),
			'lng'             => self::sanitize_coordinate( $input['lng'] ?? '', 180.0 ),
			'zoom'            => max( 1, min( 20, absint( $input['zoom'] ?? $defaults['zoom'] ) ) ),
			'h_desktop'       => max( 120, min( 1200, absint( $input['h_desktop'] ?? $defaults['h_desktop'] ) ) ),
			'h_mobile'        => max( 120, min( 1200, absint( $input['h_mobile'] ?? $defaults['h_mobile'] ) ) ),
			'style'           => in_array( $input['style'] ?? '', array( 'auto', 'light', 'dark' ), true ) ? (string) $input['style'] : $defaults['style'],
			'marker'          => sanitize_text_field( (string) ( $input['marker'] ?? '' ) ),
			'api_key'         => sanitize_text_field( (string) ( $input['api_key'] ?? '' ) ),
			'show_directions' => ! empty( $input['show_directions'] ),
		);
	}

	/**
	 * Normalises one coordinate, rejecting anything out of range.
	 *
	 * Stored as a string so an unset coordinate stays distinguishable from
	 * a deliberate 0 — which is a real place, in the Gulf of Guinea.
	 *
	 * @param mixed $value Raw coordinate.
	 * @param float $limit Absolute bound for this axis.
	 * @return string Normalised coordinate, or an empty string when unset/invalid.
	 */
	private static function sanitize_coordinate( $value, float $limit ): string {
		$raw = trim( (string) $value );
		if ( '' === $raw || ! is_numeric( $raw ) ) {
			return '';
		}

		$number = (float) $raw;
		if ( abs( $number ) > $limit ) {
			return '';
		}

		return (string) $number;
	}

	/**
	 * Whether the map has enough information to point at somewhere real.
	 *
	 * FR-05.5: with neither an address nor coordinates the block is not
	 * rendered at all — no grey placeholder and no console error.
	 *
	 * @param array<string,mixed> $settings Sanitized map settings.
	 * @return bool
	 */
	public static function is_configured( array $settings ): bool {
		return '' !== trim( (string) $settings['address'] )
			|| ( '' !== (string) $settings['lat'] && '' !== (string) $settings['lng'] );
	}

	/**
	 * The location the map should centre on.
	 *
	 * FR-05.4: coordinates beat an address, because they are the more
	 * precise value and cannot be geocoded to the wrong side of town.
	 *
	 * @param array<string,mixed> $settings Sanitized map settings.
	 * @return string Query string for the provider, already URL-safe.
	 */
	public static function query( array $settings ): string {
		if ( '' !== (string) $settings['lat'] && '' !== (string) $settings['lng'] ) {
			return $settings['lat'] . ',' . $settings['lng'];
		}

		return (string) $settings['address'];
	}

	/**
	 * The iframe URL for the configured provider.
	 *
	 * The keyless Google embed is the default because it needs no API key,
	 * no billing account and cannot produce a quota error. A Google JS API
	 * selection with no key falls back to it rather than showing visitors a
	 * "for development purposes only" watermark (FR-05.8).
	 *
	 * @param array<string,mixed> $settings Sanitized map settings.
	 * @return string Embed URL, or an empty string when unconfigured.
	 */
	public static function embed_url( array $settings ): string {
		if ( ! self::is_configured( $settings ) ) {
			return '';
		}

		$query    = self::query( $settings );
		$zoom     = (int) $settings['zoom'];
		$provider = (string) $settings['provider'];

		if ( 'google-js' === $provider && '' === trim( (string) $settings['api_key'] ) ) {
			$provider = 'google-embed';
		}

		if ( 'osm' === $provider ) {
			return self::osm_url( $settings, $zoom );
		}

		return add_query_arg(
			array(
				'q'      => rawurlencode( $query ),
				'z'      => $zoom,
				'output' => 'embed',
				'hl'     => substr( (string) get_locale(), 0, 2 ),
			),
			'https://maps.google.com/maps'
		);
	}

	/**
	 * Builds an OpenStreetMap embed URL.
	 *
	 * OSM's embed takes a bounding box rather than a centre and a zoom, so
	 * the span is derived from the zoom level: each step halves it, which
	 * is what "zoom" means on a slippy map.
	 *
	 * @param array<string,mixed> $settings Sanitized map settings.
	 * @param int                 $zoom     Zoom level.
	 * @return string
	 */
	private static function osm_url( array $settings, int $zoom ): string {
		$lat = (string) $settings['lat'];
		$lng = (string) $settings['lng'];

		// Without coordinates OSM cannot centre itself, so an address-only
		// configuration is handed to the keyless Google embed, which can
		// geocode. Silently showing the wrong continent would be worse.
		if ( '' === $lat || '' === $lng ) {
			return add_query_arg(
				array(
					'q'      => rawurlencode( (string) $settings['address'] ),
					'z'      => $zoom,
					'output' => 'embed',
				),
				'https://maps.google.com/maps'
			);
		}

		$span = 360.0 / pow( 2, max( 1, $zoom ) );

		return add_query_arg(
			array(
				'bbox'   => implode(
					',',
					array(
						(float) $lng - $span,
						(float) $lat - ( $span / 2 ),
						(float) $lng + $span,
						(float) $lat + ( $span / 2 ),
					)
				),
				'layer'  => 'mapnik',
				'marker' => $lat . ',' . $lng,
			),
			'https://www.openstreetmap.org/export/embed.html'
		);
	}

	/**
	 * A link that opens the location in the visitor's own maps app.
	 *
	 * @param array<string,mixed> $settings Sanitized map settings.
	 * @return string URL, or an empty string when unconfigured.
	 */
	public static function directions_url( array $settings ): string {
		if ( ! self::is_configured( $settings ) ) {
			return '';
		}

		return add_query_arg(
			array(
				'api'         => 1,
				'destination' => rawurlencode( self::query( $settings ) ),
			),
			'https://www.google.com/maps/search/'
		);
	}
}
