<?php
/**
 * Footer configuration: style, colours, logo, socials, contacts, columns
 * (FR-08, FR-09).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Footer;

use Maapkathi\Core\Theme\HexColor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One serialised option holding everything the footer renders.
 *
 * Kept out of ThemeSettings deliberately: that registry is a flat map of
 * scalars with a fixed key list, and the footer needs repeaters. Storing it
 * separately also means a footer change busts only the footer's cache.
 */
final class FooterSettings {

	public const OPTION = 'mk_footer_settings';

	/**
	 * Platforms offered in the social repeater (FR-08.8), in render order.
	 *
	 * @return array<string,string> slug => human label
	 */
	public static function platforms(): array {
		return array(
			'facebook'  => __( 'Facebook', 'maapkathi' ),
			'instagram' => __( 'Instagram', 'maapkathi' ),
			'youtube'   => __( 'YouTube', 'maapkathi' ),
			'linkedin'  => __( 'LinkedIn', 'maapkathi' ),
			'x'         => __( 'X / Twitter', 'maapkathi' ),
			'tiktok'    => __( 'TikTok', 'maapkathi' ),
			'pinterest' => __( 'Pinterest', 'maapkathi' ),
			'whatsapp'  => __( 'WhatsApp', 'maapkathi' ),
			'behance'   => __( 'Behance', 'maapkathi' ),
			'dribbble'  => __( 'Dribbble', 'maapkathi' ),
			'threads'   => __( 'Threads', 'maapkathi' ),
		);
	}

	/**
	 * Contact row types offered in the contacts repeater (FR-08.9).
	 *
	 * @return array<string,string> slug => human label
	 */
	public static function contact_types(): array {
		return array(
			'address' => __( 'Address', 'maapkathi' ),
			'email'   => __( 'Email', 'maapkathi' ),
			'phone'   => __( 'Phone', 'maapkathi' ),
			'hours'   => __( 'Opening hours', 'maapkathi' ),
			'custom'  => __( 'Custom', 'maapkathi' ),
		);
	}

	/**
	 * Sources a link column can draw its items from (FR-08.11).
	 *
	 * @return array<string,string> slug => human label
	 */
	public static function column_sources(): array {
		return array(
			'menu'     => __( 'Navigation menu', 'maapkathi' ),
			'services' => __( 'Services', 'maapkathi' ),
			'projects' => __( 'Projects', 'maapkathi' ),
			'custom'   => __( 'Custom links', 'maapkathi' ),
		);
	}

	/**
	 * Shipped defaults.
	 *
	 * Style defaults to Modern for a fresh install; the upgrade migration
	 * pins an existing site to Classic instead, so nobody's live footer
	 * changes shape underneath them (FR-08.1, GR-03).
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'style'         => 'modern',
			'bg_mode'       => 'dark',
			'bg_hex'        => null,
			'logo_light'    => 0,
			'logo_dark'     => 0,
			'logo_mode'     => 'auto',
			'logo_max_h'    => 64,
			'socials'       => array(),
			'contacts'      => array(),
			'col3'          => array(
				'type'    => 'menu',
				'heading' => '',
				'limit'   => 5,
				'links'   => array(),
			),
			'col4'          => array(
				'type'    => 'newsletter',
				'heading' => '',
				'helper'  => '',
				'source'  => 'menu',
				'limit'   => 5,
			),
			'show_divider'  => false,
			'centre_mobile' => false,
		);
	}

	/**
	 * Current footer settings, defaults merged under stored values.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $stored ) ? array_merge( self::defaults(), $stored ) : self::defaults() );
	}

	/**
	 * Validates and normalises a full settings payload.
	 *
	 * @param array<string,mixed> $input Raw payload.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults = self::defaults();
		$out      = array();

		$out['style']         = in_array( $input['style'] ?? '', array( 'classic', 'modern' ), true ) ? $input['style'] : $defaults['style'];
		$out['bg_mode']       = in_array( $input['bg_mode'] ?? '', array( 'dark', 'accent', 'custom', 'surface' ), true ) ? $input['bg_mode'] : $defaults['bg_mode'];
		$out['bg_hex']        = HexColor::normalize( $input['bg_hex'] ?? null );
		$out['logo_light']    = absint( $input['logo_light'] ?? 0 );
		$out['logo_dark']     = absint( $input['logo_dark'] ?? 0 );
		$out['logo_mode']     = in_array( $input['logo_mode'] ?? '', array( 'auto', 'light', 'dark' ), true ) ? $input['logo_mode'] : $defaults['logo_mode'];
		$out['logo_max_h']    = max( 24, min( 160, absint( $input['logo_max_h'] ?? $defaults['logo_max_h'] ) ) );
		$out['show_divider']  = ! empty( $input['show_divider'] );
		$out['centre_mobile'] = ! empty( $input['centre_mobile'] );
		$out['socials']       = self::sanitize_socials( $input['socials'] ?? array() );
		$out['contacts']      = self::sanitize_contacts( $input['contacts'] ?? array() );
		$out['col3']          = self::sanitize_links_column( $input['col3'] ?? array(), $defaults['col3'] );
		$out['col4']          = self::sanitize_fourth_column( $input['col4'] ?? array(), $defaults['col4'] );

		return $out;
	}

	/**
	 * Normalises the social repeater rows.
	 *
	 * @param mixed $rows Raw social repeater rows.
	 * @return array<int,array{platform:string,url:string,enabled:bool}>
	 */
	private static function sanitize_socials( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$platforms = self::platforms();
		$out       = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$platform = (string) ( $row['platform'] ?? '' );
			$url      = esc_url_raw( (string) ( $row['url'] ?? '' ) );

			// FR-08.7: a row with no URL is not a social link, so it is
			// dropped at the door rather than rendered as an empty circle.
			if ( ! isset( $platforms[ $platform ] ) || '' === $url ) {
				continue;
			}

			$out[] = array(
				'platform' => $platform,
				'url'      => $url,
				'enabled'  => ! isset( $row['enabled'] ) || ! empty( $row['enabled'] ),
			);
		}

		return $out;
	}

	/**
	 * Normalises the contact repeater rows.
	 *
	 * @param mixed $rows Raw contact repeater rows.
	 * @return array<int,array{type:string,value:string,link:string}>
	 */
	private static function sanitize_contacts( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$types = self::contact_types();
		$out   = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$type  = (string) ( $row['type'] ?? '' );
			$value = sanitize_textarea_field( (string) ( $row['value'] ?? '' ) );

			if ( ! isset( $types[ $type ] ) || '' === trim( $value ) ) {
				continue;
			}

			$out[] = array(
				'type'  => $type,
				'value' => $value,
				'link'  => esc_url_raw( (string) ( $row['link'] ?? '' ) ),
			);
		}

		return $out;
	}

	/**
	 * Normalises a links column.
	 *
	 * @param mixed               $column   Raw column payload.
	 * @param array<string,mixed> $fallback Default column shape.
	 * @return array<string,mixed>
	 */
	private static function sanitize_links_column( $column, array $fallback ): array {
		$column = is_array( $column ) ? $column : array();

		return array(
			'type'    => isset( self::column_sources()[ $column['type'] ?? '' ] ) ? $column['type'] : $fallback['type'],
			'heading' => sanitize_text_field( (string) ( $column['heading'] ?? '' ) ),
			'limit'   => max( 1, min( 20, absint( $column['limit'] ?? $fallback['limit'] ) ) ),
			'links'   => self::sanitize_custom_links( $column['links'] ?? array() ),
		);
	}

	/**
	 * The fourth column is either a newsletter block or a second links
	 * column, so it carries both shapes and renders whichever is selected.
	 *
	 * @param mixed               $column   Raw column payload.
	 * @param array<string,mixed> $fallback Default column shape.
	 * @return array<string,mixed>
	 */
	private static function sanitize_fourth_column( $column, array $fallback ): array {
		$column = is_array( $column ) ? $column : array();

		return array(
			'type'    => in_array( $column['type'] ?? '', array( 'newsletter', 'links', 'none' ), true ) ? $column['type'] : $fallback['type'],
			'heading' => sanitize_text_field( (string) ( $column['heading'] ?? '' ) ),
			'helper'  => sanitize_text_field( (string) ( $column['helper'] ?? '' ) ),
			'source'  => isset( self::column_sources()[ $column['source'] ?? '' ] ) ? $column['source'] : $fallback['source'],
			'limit'   => max( 1, min( 20, absint( $column['limit'] ?? $fallback['limit'] ) ) ),
			'links'   => self::sanitize_custom_links( $column['links'] ?? array() ),
		);
	}

	/**
	 * Normalises hand-entered link rows, dropping any that are incomplete.
	 *
	 * @param mixed $links Raw custom-link rows.
	 * @return array<int,array{label:string,url:string}>
	 */
	private static function sanitize_custom_links( $links ): array {
		if ( ! is_array( $links ) ) {
			return array();
		}

		$out = array();
		foreach ( $links as $link ) {
			if ( ! is_array( $link ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $link['label'] ?? '' ) );
			$url   = esc_url_raw( (string) ( $link['url'] ?? '' ) );

			if ( '' === $label || '' === $url ) {
				continue;
			}

			$out[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}

		return $out;
	}
}
