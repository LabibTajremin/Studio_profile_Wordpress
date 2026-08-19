<?php
/**
 * The one bundled icon set used across the theme (FR-06.4, FR-07.2, FR-07.5,
 * FR-08.9).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Icons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single stroke-drawn icon set, deliberately shared by the Services
 * section, the "What we stand for" section and the footer contact rows.
 *
 * FR-07.5 requires one library rather than several so the site's icon style
 * stays consistent; keeping it in the plugin (not the theme) means the
 * admin picker and the front end are guaranteed to be reading the same set.
 *
 * Every icon is drawn with stroke="currentColor" and no fill, which is what
 * lets a single `color: var(--accent)` recolour all of them (FR-06.4).
 */
final class IconLibrary {

	/**
	 * Icon id => [label, path data]. Paths are drawn on a 24x24 grid.
	 *
	 * @return array<string,array{label:string,path:string}>
	 */
	public static function all(): array {
		return array(
			'compass'   => array(
				'label' => __( 'Compass', 'maapkathi' ),
				'path'  => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm3.5 5.5-2 5-5 2 2-5 5-2Z',
			),
			'ruler'     => array(
				'label' => __( 'Ruler', 'maapkathi' ),
				'path'  => 'M4 14.5 14.5 4 20 9.5 9.5 20 4 14.5Zm3.5-3.5 1.5 1.5m1-4 1.5 1.5m1-4L14 8',
			),
			'blueprint' => array(
				'label' => __( 'Blueprint', 'maapkathi' ),
				'path'  => 'M3 5h18v14H3V5Zm5 0v14M3 12h5m5-7v7h8',
			),
			'building'  => array(
				'label' => __( 'Building', 'maapkathi' ),
				'path'  => 'M4 21V6l7-3v18M4 21h16M14 21V10l6 2v9M7 9h1m-1 4h1m-1 4h1m9-2h1m-1 4h1',
			),
			'home'      => array(
				'label' => __( 'Home', 'maapkathi' ),
				'path'  => 'M3 11 12 4l9 7M5 10v10h14V10M10 20v-6h4v6',
			),
			'sofa'      => array(
				'label' => __( 'Interiors', 'maapkathi' ),
				'path'  => 'M4 12V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4M3 12h18v5H3v-5Zm2 5v2m14-2v2M7 12V9h10v3',
			),
			'lightbulb' => array(
				'label' => __( 'Idea', 'maapkathi' ),
				'path'  => 'M9 18h6m-5 3h4M12 3a6 6 0 0 0-3.5 10.9V16h7v-2.1A6 6 0 0 0 12 3Z',
			),
			'palette'   => array(
				'label' => __( 'Palette', 'maapkathi' ),
				'path'  => 'M12 3a9 9 0 0 0 0 18c1.1 0 2-.9 2-2 0-.5-.2-1-.6-1.4-.3-.4-.4-.7-.4-1.1 0-.8.7-1.5 1.5-1.5H16a5 5 0 0 0 5-5c0-3.9-4-7-9-7Zm-4.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm2-4a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm5 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
			),
			'leaf'      => array(
				'label' => __( 'Sustainability', 'maapkathi' ),
				'path'  => 'M4 20c0-8 5-13 16-13 0 9-5 13-11 13H4Zm3-3c2-4 5-6 8-7',
			),
			'shield'    => array(
				'label' => __( 'Integrity', 'maapkathi' ),
				'path'  => 'M12 3 5 6v6c0 4 3 7.5 7 9 4-1.5 7-5 7-9V6l-7-3Zm-2.5 9 2 2 4-4',
			),
			'handshake' => array(
				'label' => __( 'Partnership', 'maapkathi' ),
				'path'  => 'M3 12h3l3-3 3 2 3-2 3 3h3M6 12v4h3l3 3 3-3h3v-4',
			),
			'users'     => array(
				'label' => __( 'People', 'maapkathi' ),
				'path'  => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7 0a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM3 20v-1.5C3 16 5.5 14 9 14s6 2 6 4.5V20m2-6c2.5.4 4 2 4 4v2',
			),
			'star'      => array(
				'label' => __( 'Quality', 'maapkathi' ),
				'path'  => 'm12 4 2.4 5 5.6.8-4 4 .9 5.6-4.9-2.6-4.9 2.6.9-5.6-4-4 5.6-.8L12 4Z',
			),
			'clock'     => array(
				'label' => __( 'Punctuality', 'maapkathi' ),
				'path'  => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 4v5l3.5 2',
			),
			'target'    => array(
				'label' => __( 'Precision', 'maapkathi' ),
				'path'  => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 4.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Zm0 3.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z',
			),
			'sparkle'   => array(
				'label' => __( 'Craft', 'maapkathi' ),
				'path'  => 'M12 3v4m0 10v4M3 12h4m10 0h4M6.5 6.5l2.5 2.5m6 6 2.5 2.5m0-11-2.5 2.5m-6 6-2.5 2.5',
			),
			'layers'    => array(
				'label' => __( 'Layers', 'maapkathi' ),
				'path'  => 'm12 4 8 4-8 4-8-4 8-4Zm8 8-8 4-8-4m16 4-8 4-8-4',
			),
			'camera'    => array(
				'label' => __( 'Photography', 'maapkathi' ),
				'path'  => 'M3 8h4l1.5-2h7L17 8h4v11H3V8Zm9 8.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z',
			),
			'wrench'    => array(
				'label' => __( 'Build', 'maapkathi' ),
				'path'  => 'M20 5.5 16.5 9 15 7.5 18.5 4a4.5 4.5 0 0 0-5.8 5.6L4 18.3 5.7 20l8.7-8.7A4.5 4.5 0 0 0 20 5.5Z',
			),
			'pen'       => array(
				'label' => __( 'Design', 'maapkathi' ),
				'path'  => 'M4 20h4L20 8l-4-4L4 16v4Zm11-13 4 4',
			),
			// Contact-row icons (FR-08.9).
			'map-pin'   => array(
				'label' => __( 'Location', 'maapkathi' ),
				'path'  => 'M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
			),
			'mail'      => array(
				'label' => __( 'Email', 'maapkathi' ),
				'path'  => 'M3 6h18v12H3V6Zm0 1 9 6 9-6',
			),
			'phone'     => array(
				'label' => __( 'Phone', 'maapkathi' ),
				'path'  => 'M6 3h3l2 5-2.5 1.5a12 12 0 0 0 6 6L16 13l5 2v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3Z',
			),
			'info'      => array(
				'label' => __( 'Information', 'maapkathi' ),
				'path'  => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm0 4.5v.5m0 3.5v5',
			),
		);
	}

	/**
	 * All icon ids, in registry order.
	 *
	 * @return string[]
	 */
	public static function ids(): array {
		return array_keys( self::all() );
	}

	/**
	 * Whether an icon id exists in the library.
	 *
	 * @param string $id Icon id.
	 * @return bool
	 */
	public static function has( string $id ): bool {
		return isset( self::all()[ $id ] );
	}

	/**
	 * The default icon for a footer contact row type (FR-08.9).
	 *
	 * @param string $type One of address, email, phone, hours, custom.
	 * @return string Icon id.
	 */
	public static function for_contact_type( string $type ): string {
		return match ( $type ) {
			'address'   => 'map-pin',
			'email'     => 'mail',
			'phone'     => 'phone',
			'hours'     => 'clock',
			default   => 'info',
		};
	}

	/**
	 * Renders one icon as inline SVG.
	 *
	 * Stroke-only and currentColor throughout, so the caller sets the colour
	 * with plain CSS and the icon follows the accent without any per-icon
	 * markup (FR-06.4).
	 *
	 * @param string $id        Icon id.
	 * @param int    $size      Rendered size in pixels, or 0 to let CSS size it.
	 * @param string $css_class Optional CSS class for the svg element.
	 * @return string SVG markup, or an empty string when the id is unknown.
	 */
	public static function svg( string $id, int $size = 24, string $css_class = '' ): string {
		$icons = self::all();
		if ( ! isset( $icons[ $id ] ) ) {
			return '';
		}

		// A size of 0 means "let CSS decide": the admin-set icon size is a
		// custom property on the section, and baked-in width/height
		// attributes would win over it.
		$dimensions = $size > 0 ? sprintf( ' width="%1$d" height="%1$d"', $size ) : '';

		return sprintf(
			'<svg class="%1$s" viewBox="0 0 24 24"%2$s fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%3$s" /></svg>',
			esc_attr( $css_class ),
			$dimensions,
			esc_attr( $icons[ $id ]['path'] )
		);
	}
}
