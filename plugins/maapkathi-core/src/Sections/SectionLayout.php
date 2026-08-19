<?php
/**
 * The ordered list of homepage section instances (FR-03).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns which sections the homepage renders, in what order, and whether
 * each is switched on.
 *
 * A section is an *instance* of a type. The distinction matters because
 * FR-03 allows several Services sections on one page: each instance has
 * its own id, its own anchor and its own settings record, and nothing in
 * the theme may assume a type appears only once.
 */
final class SectionLayout {

	public const OPTION = 'mk_section_layout';

	/**
	 * Section types that can be added more than once.
	 *
	 * The rest are singletons — a second hero or a second closing CTA is a
	 * mistake rather than a feature, and allowing it would only produce
	 * pages nobody meant to build.
	 *
	 * @return string[]
	 */
	public static function repeatable_types(): array {
		return array( 'clients', 'projects', 'services', 'values', 'testimonials', 'awards', 'faq', 'partners', 'tagline', 'stats', 'team', 'categories' );
	}

	/**
	 * The layout a site starts with: one instance of each homepage type, in
	 * the order the theme has always rendered them.
	 *
	 * Instance ids equal the type id here, which is what lets a site that
	 * predates the builder keep the per-section titles and anchors it
	 * already had.
	 *
	 * @return array<int,array{id:string,type:string,enabled:bool}>
	 */
	public static function defaults(): array {
		$out = array();

		foreach ( SectionRegistry::all() as $type => $section ) {
			if ( 'home' !== $section['page'] ) {
				continue;
			}

			$out[] = array(
				'id'      => $type,
				'type'    => $type,
				'enabled' => true,
			);
		}

		return $out;
	}

	/**
	 * The current layout.
	 *
	 * Any homepage type missing from a stored layout is appended rather
	 * than dropped, so a plugin update that introduces a new section type
	 * does not require the admin to go and find it.
	 *
	 * @return array<int,array{id:string,type:string,enabled:bool}>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, null );

		if ( ! is_array( $stored ) ) {
			return self::defaults();
		}

		$layout = self::sanitize( $stored );
		$types  = array_column( $layout, 'type' );

		foreach ( self::defaults() as $default ) {
			if ( ! in_array( $default['type'], $types, true ) ) {
				$layout[] = $default;
			}
		}

		return $layout;
	}

	/**
	 * Validates a posted layout.
	 *
	 * @param array<int|string,mixed> $input Raw layout rows.
	 * @return array<int,array{id:string,type:string,enabled:bool}>
	 */
	public static function sanitize( array $input ): array {
		$registry = SectionRegistry::all();
		$out      = array();
		$seen     = array();

		foreach ( $input as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$type = (string) ( $row['type'] ?? '' );
			if ( ! isset( $registry[ $type ] ) || 'home' !== $registry[ $type ]['page'] ) {
				continue;
			}

			$id = self::sanitize_id( (string) ( $row['id'] ?? '' ) );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = self::new_id( $type, array_keys( $seen ) );
			}

			$seen[ $id ] = true;

			$out[] = array(
				'id'      => $id,
				'type'    => $type,
				'enabled' => ! empty( $row['enabled'] ),
			);
		}

		return $out ? $out : self::defaults();
	}

	/**
	 * Whether a section instance may be deleted.
	 *
	 * The first instance of a core type is kept — it can be switched off,
	 * but removing it outright would leave a site with no way back to a
	 * hero or a contact call to action except by editing the database.
	 * Duplicates are always removable.
	 *
	 * @param string                                               $id     Instance id.
	 * @param array<int,array{id:string,type:string,enabled:bool}> $layout Current layout.
	 * @return bool
	 */
	public static function is_deletable( string $id, array $layout ): bool {
		$registry = SectionRegistry::all();

		foreach ( $layout as $row ) {
			if ( $row['id'] !== $id ) {
				continue;
			}

			if ( empty( $registry[ $row['type'] ]['core'] ) ) {
				return true;
			}

			// A core type is protected only in its first instance.
			foreach ( $layout as $candidate ) {
				if ( $candidate['type'] === $row['type'] ) {
					return $candidate['id'] !== $id;
				}
			}
		}

		return false;
	}

	/**
	 * A fresh instance id for a type.
	 *
	 * @param string   $type     Section type.
	 * @param string[] $existing Ids already in use.
	 * @return string
	 */
	public static function new_id( string $type, array $existing = array() ): string {
		do {
			$id = $type . '-' . substr( str_replace( '.', '', uniqid( '', true ) ), -6 );
		} while ( in_array( $id, $existing, true ) );

		return $id;
	}

	/**
	 * Normalises an instance id.
	 *
	 * @param string $id Raw id.
	 * @return string Sanitized id, or an empty string when unusable.
	 */
	private static function sanitize_id( string $id ): string {
		$id = strtolower( trim( $id ) );
		$id = (string) preg_replace( '/[^a-z0-9_\-]/', '', $id );

		return $id;
	}
}
