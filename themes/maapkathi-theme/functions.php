<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Maapkathi\Core\Theme\ThemeVarsBuilder;
use Maapkathi\Core\Theme\ThemeSettings;

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style' ) );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'responsive-embeds' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary', 'maapkathi' ),
				'footer'  => __( 'Footer', 'maapkathi' ),
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$theme_uri = get_stylesheet_directory_uri();
		$version   = wp_get_theme()->get( 'Version' );

		wp_enqueue_style( 'maapkathi-tokens', $theme_uri . '/assets/css/tokens.css', array(), $version );
		wp_enqueue_style( 'maapkathi-base', $theme_uri . '/assets/css/base.css', array( 'maapkathi-tokens' ), $version );
		wp_enqueue_style( 'maapkathi-sections', $theme_uri . '/assets/css/sections.css', array( 'maapkathi-base' ), $version );
		wp_enqueue_style( 'maapkathi-motion', $theme_uri . '/assets/css/motion.css', array( 'maapkathi-base' ), $version );

		wp_enqueue_script( 'maapkathi-motion-engine', $theme_uri . '/assets/js/motion-engine.js', array(), $version, true );
		wp_enqueue_script( 'maapkathi-theme-toggle', $theme_uri . '/assets/js/theme-toggle.js', array(), $version, true );
		wp_enqueue_script( 'maapkathi-lightbox', $theme_uri . '/assets/js/lightbox.js', array(), $version, true );

		$fonts = self_font_families();
		if ( ! empty( $fonts ) ) {
			wp_enqueue_style(
				'maapkathi-google-fonts',
				'https://fonts.googleapis.com/css2?' . $fonts . '&display=swap',
				array(),
				null
			);
		}
	}
);

/**
 * Builds the Google Fonts query string for the active font pair, so only the
 * families actually in use are requested.
 */
function self_font_families(): string {
	$settings = ThemeSettings::get();
	$pair     = \Maapkathi\Core\Theme\Fonts::by_id( $settings['font_pair_id'] );
	if ( ! $pair ) {
		return '';
	}

	// Family names extracted from the CSS stack's first quoted segment.
	preg_match( '/"([^"]+)"/', $pair['display'], $display_match );
	preg_match( '/"([^"]+)"/', $pair['body'], $body_match );

	$families = array_filter( array( $display_match[1] ?? null, $body_match[1] ?? null ) );
	$parts    = array();
	foreach ( array_unique( $families ) as $family ) {
		$parts[] = 'family=' . rawurlencode( $family ) . ':wght@400;500;600;700';
	}

	return implode( '&', $parts );
}

/**
 * Injects the theme-vars CSS block inline in <head> — no second request, no
 * flash of unstyled theme (§10).
 */
add_action(
	'wp_head',
	static function (): void {
		echo "<style id=\"mk-theme-vars\">\n" . wp_kses( ThemeVarsBuilder::build(), array() ) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	1
);

/**
 * Pre-paint inline script: reads the visitor's stored mode (cookie/
 * localStorage) and sets data-theme on <html> before first render, so
 * mode:'system' follows the OS until the visitor overrides it and there is
 * never a flash of the wrong theme (§3.5, §10).
 */
add_action(
	'wp_head',
	static function (): void {
		?>
		<script>
		(function () {
			try {
				var stored = localStorage.getItem('mk-theme') || (document.cookie.match(/(?:^|; )mk_theme=([^;]+)/) || [])[1];
				var mode = stored || '<?php echo esc_js( ThemeSettings::get()['mode'] ); ?>';
				var resolved = mode === 'system'
					? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
					: mode;
				document.documentElement.setAttribute('data-theme', resolved);
			} catch (e) {
				document.documentElement.setAttribute('data-theme', 'light');
			}
		})();
		</script>
		<?php
	},
	0
);

/**
 * Sets the remaining data-* attributes driving CSS-only motion/appearance
 * behaviour (§11.3) — everything the JS engine reads instead of hardcoding.
 */
add_filter(
	'language_attributes',
	static function ( string $output ): string {
		$settings = ThemeSettings::get();
		foreach ( ThemeVarsBuilder::data_attrs_for( $settings ) as $attr => $value ) {
			$output .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
		}
		return $output;
	}
);

require get_template_directory() . '/parts/nav.php';
