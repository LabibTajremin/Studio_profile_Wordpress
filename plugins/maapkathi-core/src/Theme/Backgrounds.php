<?php
/**
 * Background tone registry.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The site's base --background/--foreground pair is never plain white —
 * only the temperature is a client-facing choice, between a warm cream
 * (the default, matching the reference app) and a cooler off-white/grey.
 *
 * Dark mode is a blackish midnight blue rather than a flat near-black —
 * warm keeps a touch more neutrality, cool leans bluer.
 */
final class Backgrounds {

	public const DEFAULT_TONE = 'warm';

	/**
	 * Both registered tones, each with a light and dark mode pair.
	 *
	 * @return array<string, array{name:string, light:array{background:string, foreground:string}, dark:array{background:string, foreground:string}}>
	 */
	public static function all(): array {
		return array(
			'warm' => array(
				'name'  => 'Warm',
				'light' => array(
					'background' => '#f9f0e4',
					'foreground' => '#171310',
				),
				'dark'  => array(
					'background' => '#161b28',
					'foreground' => '#faf6f1',
				),
			),
			'cool' => array(
				'name'  => 'Cool',
				'light' => array(
					'background' => '#f1f4f6',
					'foreground' => '#12161a',
				),
				'dark'  => array(
					'background' => '#101728',
					'foreground' => '#f4f7f9',
				),
			),
		);
	}

	/**
	 * Looks up a single tone by its id.
	 *
	 * @param string $id Tone id to look up ('warm' or 'cool').
	 * @return array{name:string, light:array{background:string, foreground:string}, dark:array{background:string, foreground:string}}|null
	 */
	public static function by_id( string $id ): ?array {
		return self::all()[ $id ] ?? null;
	}

	/**
	 * All registered tone ids.
	 *
	 * @return string[]
	 */
	public static function ids(): array {
		return array_keys( self::all() );
	}
}
