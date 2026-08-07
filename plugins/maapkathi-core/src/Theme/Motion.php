<?php
/**
 * Motion engine registries and resolver.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Motion engine registries + resolveMotionVars(), ported verbatim from
 * src/presentation/theme/motion.ts.
 */
final class Motion {

	/**
	 * All registered scroll-reveal styles.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function scroll_reveal_styles(): array {
		return self::items(
			array(
				'none'             => 'None',
				'fade'             => 'Fade',
				'fade-up'          => 'Fade Up',
				'fade-up-soft'     => 'Fade Up Soft',
				'slide-in-side'    => 'Slide In From Side',
				'scale-in'         => 'Scale In',
				'blur-in'          => 'Blur In',
				'clip-reveal'      => 'Clip Reveal',
				'curtain-wipe'     => 'Curtain Wipe',
				'line-draw'        => 'Line Draw',
				'stagger-grid'     => 'Stagger Grid',
				'perspective-tilt' => 'Perspective Tilt',
			)
		);
	}

	/**
	 * All registered hero-animation styles.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function hero_animations(): array {
		return self::items(
			array(
				'static'           => 'Static',
				'ken-burns-drift'  => 'Ken Burns Drift',
				'crossfade-slides' => 'Crossfade Slides',
				'split-curtain'    => 'Split Curtain',
				'masked-word-swap' => 'Masked Word Swap',
				'zoom-out-on-load' => 'Zoom Out On Load',
				'video-loop'       => 'Video Loop',
				'layered-parallax' => 'Layered Parallax',
			)
		);
	}

	/**
	 * All registered image-hover styles.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function image_hover_styles(): array {
		return self::items(
			array(
				'none'               => 'None',
				'zoom'               => 'Zoom',
				'zoom-darken'        => 'Zoom + Darken',
				'duotone-colour'     => 'Duotone → Colour',
				'grayscale-colour'   => 'Grayscale → Colour',
				'caption-slide-up'   => 'Reveal Caption Slide-Up',
				'accent-border-draw' => 'Accent Border Draw',
				'tilt-follow'        => 'Tilt Follow Cursor',
			)
		);
	}

	/**
	 * All registered card-hover styles.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function card_hover_styles(): array {
		return self::items(
			array(
				'none'             => 'None',
				'lift-shadow'      => 'Lift + Shadow',
				'accent-underline' => 'Accent Underline Draw',
				'border-trace'     => 'Border Trace',
				'inner-glow'       => 'Inner Glow',
				'content-shift'    => 'Content Shift',
			)
		);
	}

	/**
	 * All registered text-reveal styles.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function text_reveal_styles(): array {
		return self::items(
			array(
				'none'              => 'None',
				'fade'              => 'Fade',
				'mask-slide-up'     => 'Mask Slide-Up',
				'word-stagger'      => 'Word Stagger',
				'character-stagger' => 'Character Stagger',
				'blur-focus'        => 'Blur Focus',
			)
		);
	}

	/**
	 * All registered page-transition styles.
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	public static function page_transitions(): array {
		return self::items(
			array(
				'none'        => 'None',
				'fade'        => 'Fade',
				'accent-wipe' => 'Accent Wipe',
				'curtain-up'  => 'Curtain Up',
				'slide-push'  => 'Slide Push',
				'logo-mask'   => 'Logo Mask',
			)
		);
	}

	public const PRESETS = array(
		'off'        => array(
			'durationMs' => 0,
			'ease'       => 'linear',
			'distancePx' => 0,
			'staggerMs'  => 0,
		),
		'subtle'     => array(
			'durationMs' => 450,
			'ease'       => 'cubic-bezier(0.16, 1, 0.3, 1)',
			'distancePx' => 8,
			'staggerMs'  => 50,
		),
		'refined'    => array(
			'durationMs' => 600,
			'ease'       => 'cubic-bezier(0.16, 1, 0.3, 1)',
			'distancePx' => 12,
			'staggerMs'  => 70,
		),
		'expressive' => array(
			'durationMs' => 750,
			'ease'       => 'cubic-bezier(0.16, 1, 0.3, 1)',
			'distancePx' => 20,
			'staggerMs'  => 90,
		),
	);

	/**
	 * Resolves the motion CSS custom-property map for the given settings.
	 *
	 * @param array{motionPreset:string,motionSpeed:int,staggerMs:int,parallaxIntensity:int} $input          Motion-related theme settings.
	 * @param bool                                                                            $reduced_motion Whether to resolve the reduced-motion variant.
	 * @return array<string,string>
	 */
	public static function resolve_vars( array $input, bool $reduced_motion ): array {
		if ( $reduced_motion ) {
			return array(
				'--motion-duration'    => '0ms',
				'--motion-ease'        => 'linear',
				'--motion-distance'    => '0px',
				'--motion-stagger'     => '0ms',
				'--parallax-intensity' => '0',
			);
		}

		$preset   = $input['motionPreset'];
		$base     = 'custom' === $preset ? self::PRESETS['refined'] : ( self::PRESETS[ $preset ] ?? self::PRESETS['refined'] );
		$speed    = min( 150, max( 50, $input['motionSpeed'] ) ) / 100;
		$duration = (int) round( $base['durationMs'] * $speed );
		$stagger  = 'custom' === $preset ? $input['staggerMs'] : $base['staggerMs'];

		return array(
			'--motion-duration'    => $duration . 'ms',
			'--motion-ease'        => $base['ease'],
			'--motion-distance'    => $base['distancePx'] . 'px',
			'--motion-stagger'     => $stagger . 'ms',
			'--parallax-intensity' => (string) ( min( 100, max( 0, $input['parallaxIntensity'] ) ) / 100 ),
		);
	}

	/**
	 * Turns an id => name map into a list of {id, name} entries.
	 *
	 * @param array<string,string> $map Id => name map.
	 * @return array<int,array{id:string,name:string}>
	 */
	private static function items( array $map ): array {
		$out = array();
		foreach ( $map as $id => $name ) {
			$out[] = array(
				'id'   => $id,
				'name' => $name,
			);
		}
		return $out;
	}
}
