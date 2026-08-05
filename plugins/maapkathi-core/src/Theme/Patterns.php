<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Background pattern registry (22), ported verbatim from
 * src/presentation/theme/patterns.ts. Pure CSS/SVG, tinted from
 * var(--pattern-color) and faded by var(--pattern-opacity).
 */
final class Patterns {

	/**
	 * @return array<int, array{id:string,name:string,css:string}>
	 */
	public static function all(): array {
		$c = 'var(--pattern-color, currentColor)';

		return array(
			array( 'id' => 'none', 'name' => 'None', 'css' => 'background: none;' ),
			array(
				'id' => 'fine-grid',
				'name' => 'Fine Grid',
				'css' => "background-image: linear-gradient({$c} 1px, transparent 1px), linear-gradient(90deg, {$c} 1px, transparent 1px); background-size: 24px 24px;",
			),
			array(
				'id' => 'blueprint-grid',
				'name' => 'Blueprint Grid',
				'css' => "background-image: linear-gradient({$c} 1px, transparent 1px), linear-gradient(90deg, {$c} 1px, transparent 1px), linear-gradient({$c} 1px, transparent 1px), linear-gradient(90deg, {$c} 1px, transparent 1px); background-size: 96px 96px, 96px 96px, 24px 24px, 24px 24px;",
			),
			array(
				'id' => 'dot-matrix',
				'name' => 'Dot Matrix',
				'css' => "background-image: radial-gradient({$c} 1.2px, transparent 1.2px); background-size: 20px 20px;",
			),
			array(
				'id' => 'diagonal-hatch',
				'name' => 'Diagonal Hatch',
				'css' => "background-image: repeating-linear-gradient(45deg, {$c} 0, {$c} 1px, transparent 1px, transparent 12px);",
			),
			array(
				'id' => 'cross-hatch',
				'name' => 'Cross Hatch',
				'css' => "background-image: repeating-linear-gradient(45deg, {$c} 0, {$c} 1px, transparent 1px, transparent 12px), repeating-linear-gradient(-45deg, {$c} 0, {$c} 1px, transparent 1px, transparent 12px);",
			),
			array(
				'id' => 'isometric',
				'name' => 'Isometric',
				'css' => "background-image: repeating-linear-gradient(30deg, {$c} 0, {$c} 1px, transparent 1px, transparent 20px), repeating-linear-gradient(150deg, {$c} 0, {$c} 1px, transparent 1px, transparent 20px), repeating-linear-gradient(270deg, {$c} 0, {$c} 1px, transparent 1px, transparent 20px);",
			),
			array(
				'id' => 'subtle-noise',
				'name' => 'Subtle Noise/Grain',
				'css' => "background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E\");",
			),
			array(
				'id' => 'topographic',
				'name' => 'Topographic Lines',
				'css' => "background-image: repeating-radial-gradient(circle at 30% 40%, transparent 0, transparent 18px, {$c} 18px, {$c} 19px);",
			),
			array(
				'id' => 'concentric-arcs',
				'name' => 'Concentric Arcs',
				'css' => "background-image: repeating-radial-gradient(circle at 100% 0, transparent 0, transparent 28px, {$c} 28px, {$c} 29px);",
			),
			array(
				'id' => 'herringbone',
				'name' => 'Herringbone',
				'css' => "background-image: repeating-linear-gradient(45deg, {$c} 0 1px, transparent 1px 10px), repeating-linear-gradient(135deg, {$c} 0 1px, transparent 1px 10px); background-size: 20px 20px;",
			),
			array(
				'id' => 'terrazzo',
				'name' => 'Terrazzo',
				'css' => "background-image: radial-gradient({$c} 2px, transparent 2.5px), radial-gradient({$c} 1.5px, transparent 2px), radial-gradient({$c} 1px, transparent 1.5px); background-size: 60px 60px, 42px 42px, 28px 28px; background-position: 0 0, 12px 18px, 30px 8px;",
			),
			array(
				'id' => 'linen-weave',
				'name' => 'Linen Weave',
				'css' => "background-image: repeating-linear-gradient(0deg, {$c} 0 1px, transparent 1px 4px), repeating-linear-gradient(90deg, {$c} 0 1px, transparent 1px 4px);",
			),
			array(
				'id' => 'marble-veins',
				'name' => 'Marble Veins',
				'css' => "background-image: repeating-linear-gradient(115deg, transparent 0 40px, {$c} 40px 41px, transparent 41px 44px, {$c} 44px 45px, transparent 45px 90px);",
			),
			array(
				'id' => 'art-deco-fan',
				'name' => 'Art Deco Fan',
				'css' => "background-image: repeating-conic-gradient(from 0deg at 0 100%, {$c} 0deg 1deg, transparent 1deg 12deg); background-size: 80px 80px;",
			),
			array(
				'id' => 'moroccan-trellis',
				'name' => 'Moroccan Trellis',
				'css' => "background-image: radial-gradient(circle at 50% 0, transparent 12px, {$c} 12px 13px, transparent 13px), radial-gradient(circle at 50% 100%, transparent 12px, {$c} 12px 13px, transparent 13px); background-size: 26px 26px;",
			),
			array(
				'id' => 'hexagon-mesh',
				'name' => 'Hexagon Mesh',
				'css' => "background-image: repeating-linear-gradient(60deg, {$c} 0 1px, transparent 1px 18px), repeating-linear-gradient(-60deg, {$c} 0 1px, transparent 1px 18px), repeating-linear-gradient(0deg, {$c} 0 1px, transparent 1px 18px);",
			),
			array(
				'id' => 'vertical-pinstripe',
				'name' => 'Vertical Pinstripe',
				'css' => "background-image: repeating-linear-gradient(90deg, {$c} 0 1px, transparent 1px 8px);",
			),
			array(
				'id' => 'wave-contour',
				'name' => 'Wave Contour',
				'css' => "background-image: repeating-radial-gradient(circle at 50% 120%, transparent 0 20px, {$c} 20px 21px);",
			),
			array(
				'id' => 'blueprint-corner-rules',
				'name' => 'Blueprint Corner Rules',
				'css' => "background-image: linear-gradient({$c} 1px, transparent 1px), linear-gradient(90deg, {$c} 1px, transparent 1px); background-size: 120px 120px; background-position: 0 0;",
			),
			array(
				'id' => 'soft-radial-glow',
				'name' => 'Soft Radial Glow',
				'css' => "background-image: radial-gradient(circle at 50% 0, {$c} 0, transparent 60%);",
			),
			array(
				'id' => 'mesh-gradient',
				'name' => 'Mesh Gradient',
				'css' => "background-image: radial-gradient(at 20% 20%, {$c} 0, transparent 40%), radial-gradient(at 80% 30%, {$c} 0, transparent 35%), radial-gradient(at 50% 80%, {$c} 0, transparent 45%);",
			),
		);
	}

	public static function by_id( string $id ): ?array {
		foreach ( self::all() as $pattern ) {
			if ( $pattern['id'] === $id ) {
				return $pattern;
			}
		}
		return null;
	}

	public static function ids(): array {
		return array_column( self::all(), 'id' );
	}
}
