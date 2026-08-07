<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Font pairing registry (8) plus the radius/density scales, ported verbatim
 * from src/presentation/theme/fonts.ts.
 *
 * ⚠️ radius values are sharp/subtle/soft/pill — not none/soft/round.
 */
final class Fonts {

	private const SERIF_FALLBACK = 'Georgia, "Times New Roman", serif';
	private const SANS_FALLBACK  = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

	/**
	 * @return array<int, array{id:string,name:string,display:string,body:string}>
	 */
	public static function all(): array {
		return array(
			array(
				'id'      => 'fraunces-manrope',
				'name'    => 'Fraunces + Manrope',
				'display' => '"Fraunces", ' . self::SERIF_FALLBACK,
				'body'    => '"Manrope", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'cormorant-inter',
				'name'    => 'Cormorant + Inter',
				'display' => '"Cormorant Garamond", ' . self::SERIF_FALLBACK,
				'body'    => '"Inter", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'playfair-source',
				'name'    => 'Playfair + Source Sans',
				'display' => '"Playfair Display", ' . self::SERIF_FALLBACK,
				'body'    => '"Source Sans 3", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'instrument-geist',
				'name'    => 'Instrument Serif + Geist',
				'display' => '"Instrument Serif", ' . self::SERIF_FALLBACK,
				'body'    => '"Geist", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'libre-inter',
				'name'    => 'Libre Caslon + Inter',
				'display' => '"Libre Caslon Display", ' . self::SERIF_FALLBACK,
				'body'    => '"Inter", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'dmserif-dmsans',
				'name'    => 'DM Serif + DM Sans',
				'display' => '"DM Serif Display", ' . self::SERIF_FALLBACK,
				'body'    => '"DM Sans", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'spectral-work',
				'name'    => 'Spectral + Work Sans',
				'display' => '"Spectral", ' . self::SERIF_FALLBACK,
				'body'    => '"Work Sans", ' . self::SANS_FALLBACK,
			),
			array(
				'id'      => 'bricolage-manrope',
				'name'    => 'Bricolage + Manrope',
				'display' => '"Bricolage Grotesque", ' . self::SANS_FALLBACK,
				'body'    => '"Manrope", ' . self::SANS_FALLBACK,
			),
		);
	}

	public static function by_id( string $id ): ?array {
		foreach ( self::all() as $pair ) {
			if ( $pair['id'] === $id ) {
				return $pair;
			}
		}
		return null;
	}

	public static function ids(): array {
		return array_column( self::all(), 'id' );
	}

	public const RADIUS_SCALE = array(
		'sharp'  => '0px',
		'subtle' => '6px',
		'soft'   => '14px',
		'pill'   => '9999px',
	);

	public const DENSITY_SCALE = array(
		'compact'     => '0.85',
		'comfortable' => '1',
		'spacious'    => '1.2',
	);
}
