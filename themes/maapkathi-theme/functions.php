<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Maapkathi\Core\Theme\ThemeVarsBuilder;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Theme\Fonts;
use Maapkathi\Core\Support\Branding;

require get_template_directory() . '/parts/nav.php';

/**
 * The theme renders; the plugin owns the data. If the plugin is ever
 * deactivated the site must degrade to plain-but-working rather than
 * white-screening, so every plugin call below is guarded on this.
 */
function mk_theme_plugin_active(): bool {
	return class_exists( ThemeSettings::class ) && class_exists( ThemeVarsBuilder::class );
}

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'automatic-feed-links' );

		// Image sizes matching the media-ratio hints in §3.5.
		add_image_size( 'mk-project-cover', 1200, 1500, true );   // 4:5
		add_image_size( 'mk-member-photo', 600, 800, true );      // 3:4
		add_image_size( 'mk-hero', 2400, 1350, true );            // 16:9

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
		$theme_dir = get_stylesheet_directory();

		// File-mtime versioning busts browser caches on deploy without
		// anyone having to remember to bump a version constant.
		$ver = static function ( string $rel ) use ( $theme_dir ): string {
			$path = $theme_dir . $rel;
			return (string) ( file_exists( $path ) ? filemtime( $path ) : wp_get_theme()->get( 'Version' ) );
		};

		wp_enqueue_style( 'maapkathi-tokens', $theme_uri . '/assets/css/tokens.css', array(), $ver( '/assets/css/tokens.css' ) );
		wp_enqueue_style( 'maapkathi-base', $theme_uri . '/assets/css/base.css', array( 'maapkathi-tokens' ), $ver( '/assets/css/base.css' ) );
		wp_enqueue_style( 'maapkathi-sections', $theme_uri . '/assets/css/sections.css', array( 'maapkathi-base' ), $ver( '/assets/css/sections.css' ) );
		wp_enqueue_style( 'maapkathi-motion', $theme_uri . '/assets/css/motion.css', array( 'maapkathi-base' ), $ver( '/assets/css/motion.css' ) );

		wp_enqueue_script( 'maapkathi-motion-engine', $theme_uri . '/assets/js/motion-engine.js', array(), $ver( '/assets/js/motion-engine.js' ), true );
		wp_enqueue_script( 'maapkathi-theme-toggle', $theme_uri . '/assets/js/theme-toggle.js', array(), $ver( '/assets/js/theme-toggle.js' ), true );

		// Lightbox only where a gallery can actually appear.
		if ( is_singular( array( 'mk_project', 'mk_service' ) ) ) {
			wp_enqueue_script( 'maapkathi-lightbox', $theme_uri . '/assets/js/lightbox.js', array(), $ver( '/assets/js/lightbox.js' ), true );
		}

		// Dashicons power the optional service/value icons on the front end.
		wp_enqueue_style( 'dashicons' );

		$fonts_url = mk_theme_google_fonts_url();
		if ( $fonts_url ) {
			wp_enqueue_style( 'maapkathi-google-fonts', $fonts_url, array(), null );
		}
	}
);

/**
 * Google Fonts URL for the active pair only — never the whole registry.
 */
function mk_theme_google_fonts_url(): string {
	if ( ! mk_theme_plugin_active() ) {
		return '';
	}

	$settings = ThemeSettings::get();
	$families = array();

	foreach ( array( 'headings', 'body', 'nav', 'buttons', 'hero', 'accents' ) as $area ) {
		$override = $settings['font_overrides'][ $area ]['fontId'] ?? '';
		if ( $override ) {
			$families[] = $override;
		}
	}
	$families[] = $settings['font_pair_id'];

	$names = array();
	foreach ( array_unique( $families ) as $pair_id ) {
		$pair = Fonts::by_id( (string) $pair_id );
		if ( ! $pair ) {
			continue;
		}
		foreach ( array( $pair['display'], $pair['body'] ) as $stack ) {
			if ( preg_match( '/"([^"]+)"/', $stack, $m ) ) {
				$names[] = $m[1];
			}
		}
	}

	$names = array_unique( array_filter( $names ) );
	if ( ! $names ) {
		return '';
	}

	$parts = array();
	foreach ( $names as $name ) {
		$parts[] = 'family=' . rawurlencode( $name ) . ':wght@400;500;600;700';
	}

	return 'https://fonts.googleapis.com/css2?' . implode( '&', $parts ) . '&display=swap';
}

/**
 * Theme-vars block, injected inline in <head> so there is no second
 * request and no flash of unstyled theme (§10).
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! mk_theme_plugin_active() ) {
			return;
		}
		echo '<style id="mk-theme-vars">' . "\n" . ThemeVarsBuilder::build() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated CSS, values validated against the registry.
	},
	1
);

/**
 * Favicon with the §3.5 fallback chain: favicon → light logo → dark logo →
 * built-in mark.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( class_exists( Branding::class ) ) {
			Branding::render_favicon_tags();
		}
	},
	2
);

/**
 * Pre-paint mode script: sets data-theme on <html> before first paint so
 * the visitor's stored light/dark choice never flashes (§3.5, §10).
 */
add_action(
	'wp_head',
	static function (): void {
		$mode = mk_theme_plugin_active() ? ThemeSettings::get()['mode'] : 'system';
		?>
		<script>
		(function () {
			try {
				var stored = localStorage.getItem('mk-theme') || (document.cookie.match(/(?:^|; )mk_theme=([^;]+)/) || [])[1];
				var mode = stored || <?php echo wp_json_encode( $mode ); ?>;
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
 * data-* attributes on <html> for every setting that drives behaviour
 * rather than a single CSS variable (§11.3).
 */
add_filter(
	'language_attributes',
	static function ( string $output ): string {
		if ( ! mk_theme_plugin_active() ) {
			return $output;
		}
		foreach ( ThemeVarsBuilder::data_attrs_for( ThemeSettings::get() ) as $attr => $value ) {
			$output .= sprintf( ' %s="%s"', esc_attr( $attr ), esc_attr( $value ) );
		}
		return $output;
	}
);

/**
 * 404 the blog routes entirely when the blog is disabled (§3.4) — hiding
 * the nav item alone would leave the URLs publicly reachable.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( mk_blog_enabled() ) {
			return;
		}

		// Never 404 the front page. Until a static front page is assigned
		// (a fresh install, or before the seeder runs), the site's homepage
		// IS the blog index — so is_home() is true there, and without this
		// guard disabling the blog would 404 the entire site.
		if ( is_front_page() ) {
			return;
		}

		if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}
);

/**
 * Ordered queries for the public archives, so the admin's sort order is
 * respected without the theme building its own WP_Query.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->is_post_type_archive( 'mk_project' ) || $query->is_tax( 'mk_project_category' ) ) {
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
		}
	}
);

/**
 * Accessible, predictable excerpt behaviour.
 */
add_filter( 'excerpt_more', static fn() => '…' );
add_filter( 'excerpt_length', static fn() => 28, 999 );
