<?php
/**
 * Theme bootstrap: setup, asset enqueues, and template-rendering hooks.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Maapkathi\Core\Theme\ThemeVarsBuilder;
use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Theme\Fonts;
use Maapkathi\Core\Support\Branding;

/**
 * Last-resort asset version, used only if both filemtime() and the
 * style.css Version header are unavailable. Never leave this empty — an
 * empty version makes WordPress drop the ?ver= cache-buster completely.
 */
if ( ! defined( 'MK_THEME_VERSION' ) ) {
	define( 'MK_THEME_VERSION', '0.1.0' );
}

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
		//
		// Every branch must return a NON-EMPTY string. wp_enqueue_style()
		// treats '' as "no version" and omits the ?ver= query entirely —
		// and with a CDN/browser Cache-Control of a week on these files,
		// a versionless URL is cached indefinitely and no deploy is ever
		// picked up. That is exactly what was happening in production:
		// every theme stylesheet was being served from
		// /assets/css/*.css with no query string at all. filemtime() can
		// legitimately fail (opcache/stat cache, restrictive open_basedir,
		// a symlinked or synced deploy), so both fallbacks are guarded.
		$ver = static function ( string $rel ) use ( $theme_dir ): string {
			$path  = $theme_dir . $rel;
			$stamp = file_exists( $path ) ? filemtime( $path ) : false;
			if ( false !== $stamp ) {
				return (string) $stamp;
			}

			$theme_version = (string) wp_get_theme()->get( 'Version' );
			return '' !== $theme_version ? $theme_version : MK_THEME_VERSION;
		};

		wp_enqueue_style( 'maapkathi-tokens', $theme_uri . '/assets/css/tokens.css', array(), $ver( '/assets/css/tokens.css' ) );
		wp_enqueue_style( 'maapkathi-base', $theme_uri . '/assets/css/base.css', array( 'maapkathi-tokens' ), $ver( '/assets/css/base.css' ) );
		wp_enqueue_style( 'maapkathi-sections', $theme_uri . '/assets/css/sections.css', array( 'maapkathi-base' ), $ver( '/assets/css/sections.css' ) );
		wp_enqueue_style( 'maapkathi-motion', $theme_uri . '/assets/css/motion.css', array( 'maapkathi-base' ), $ver( '/assets/css/motion.css' ) );

		wp_enqueue_script( 'maapkathi-motion-engine', $theme_uri . '/assets/js/motion-engine.js', array(), $ver( '/assets/js/motion-engine.js' ), true );
		wp_enqueue_script( 'maapkathi-theme-toggle', $theme_uri . '/assets/js/theme-toggle.js', array(), $ver( '/assets/js/theme-toggle.js' ), true );

		// Lightbox only where a gallery can actually appear.
		if ( is_singular( array( 'mk_project', 'mk_service' ) ) || mk_theme_has_gallery_section() ) {
			wp_enqueue_script( 'maapkathi-lightbox', $theme_uri . '/assets/js/lightbox.js', array(), $ver( '/assets/js/lightbox.js' ), true );
		}

		// The gallery script only ships where a gallery section renders.
		if ( mk_theme_has_gallery_section() ) {
			wp_enqueue_script( 'maapkathi-gallery', $theme_uri . '/assets/js/gallery.js', array(), $ver( '/assets/js/gallery.js' ), true );
			wp_localize_script(
				'maapkathi-gallery',
				'mkGallery',
				array(
					'endpoint' => esc_url_raw( rest_url( 'maapkathi/v1/gallery' ) ),
					'label'    => __( 'Load more', 'maapkathi' ),
					'loading'  => __( 'Loading…', 'maapkathi' ),
				)
			);
		}

		// The marquee script only exists to pause the band in a hidden tab,
		// so it is pointless anywhere the band is not a marquee.
		if ( mk_theme_has_partner_marquee() ) {
			wp_enqueue_script( 'maapkathi-partners-marquee', $theme_uri . '/assets/js/partners-marquee.js', array(), $ver( '/assets/js/partners-marquee.js' ), true );
		}

		// The subscribe script is only useful where the newsletter column
		// actually renders, which is the Modern footer with column 4 set to
		// the newsletter (GR-06: no script on a page that cannot use it).
		if ( mk_theme_has_subscribe_form() ) {
			wp_enqueue_script( 'maapkathi-subscribe', $theme_uri . '/assets/js/subscribe.js', array(), $ver( '/assets/js/subscribe.js' ), true );
			wp_localize_script(
				'maapkathi-subscribe',
				'mkSubscribe',
				array(
					'endpoint' => esc_url_raw( rest_url( 'maapkathi/v1/subscribe' ) ),
					'sending'  => __( 'Sending…', 'maapkathi' ),
					'invalid'  => __( 'Please enter a valid email address.', 'maapkathi' ),
					'failed'   => __( 'Something went wrong. Please try again.', 'maapkathi' ),
				)
			);
		}

		// Dashicons power the optional service icons, and are a separate
		// ~45KB render-blocking request — only load them on the two
		// templates that can actually render one.
		if ( is_front_page() || is_page_template( 'page-services.php' ) || is_singular( 'mk_service' ) ) {
			wp_enqueue_style( 'dashicons' );
		}

		$fonts_url = mk_theme_google_fonts_url();
		if ( $fonts_url ) {
			wp_enqueue_style( 'maapkathi-google-fonts', $fonts_url, array(), $ver( '/style.css' ) );
		}
	}
);

/**
 * Whether the current request renders post/page content that may contain
 * Gutenberg blocks. Only those templates need WordPress's block CSS.
 *
 * The homepage, archives and the hand-built templates render entirely from
 * this theme's own markup and CPT fields — `the_content()` is never called
 * — so the block stylesheets are pure dead weight there.
 *
 * @return bool
 */
function mk_theme_needs_block_styles(): bool {
	return (bool) apply_filters( 'mk_needs_block_styles', is_singular( array( 'post', 'page' ) ) );
}

/**
 * Drops core CSS/JS the rendered page provably does not use.
 *
 * Measured on the live homepage before this ran: 9.2KB of inline
 * `global-styles` presets (the --wp--preset--* block, generated from core
 * defaults this classic theme never references), plus the block-library
 * stylesheets and the emoji script. All of it is render-blocking or
 * parser-blocking on the LCP-critical page.
 *
 * Everything here is reversible through the `mk_needs_block_styles`
 * filter, and block styles are still loaded wherever block content can
 * actually appear.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || mk_theme_needs_block_styles() ) {
			return;
		}

		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
	},
	100
);

// The emoji polyfill is a script, an inline blob and a DNS prefetch on every
// page; this theme's content has no need for it.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Resource hints and an LCP preload.
 *
 * Google Fonts costs a DNS lookup plus a TLS handshake to two hosts before
 * a single glyph can render, which is worth several hundred ms on mobile.
 * The hero image is almost always the LCP element, and the browser cannot
 * discover it until it has parsed the stylesheet that positions it — so it
 * is preloaded explicitly.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( mk_theme_google_fonts_url() ) {
			echo "<link rel='preconnect' href='https://fonts.googleapis.com' />\n";
			echo "<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin />\n";
		}

		if ( ! is_front_page() ) {
			return;
		}

		$lcp = mk_theme_hero_lcp_image();
		if ( $lcp ) {
			printf( "<link rel='preload' as='image' href='%s' fetchpriority='high' />\n", esc_url( $lcp ) );
		}
	},
	0
);

/**
 * URL of the first hero slide's image — the homepage's LCP candidate.
 *
 * Mirrors the resolution order parts/hero.php uses for the first slide, so
 * the preload always points at the image actually rendered. Returns '' for
 * a video-or-embed first slide, where the poster is what paints and the
 * markup already carries fetchpriority.
 *
 * @return string
 */
function mk_theme_hero_lcp_image(): string {
	if ( ! function_exists( 'mk_setting' ) ) {
		return '';
	}

	$slides = (array) mk_setting( 'hero_slides', array() );
	$slides = array_values(
		array_filter(
			$slides,
			static fn( $slide ) => ! isset( $slide['is_active'] ) || $slide['is_active']
		)
	);

	$first = $slides[0] ?? null;
	if ( ! is_array( $first ) ) {
		return '';
	}

	$kind = $first['media_kind'] ?? 'image';

	if ( 'image' === $kind ) {
		return (string) ( $first['image_url'] ?? '' );
	}
	if ( 'gif' === $kind ) {
		return (string) ( $first['gif_url'] ?? '' );
	}

	return (string) ( $first['video_poster'] ?? '' );
}

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
 *
 * Priority 20 is load-bearing: WordPress prints enqueued stylesheets from
 * wp_print_styles on wp_head at priority 8, and tokens.css declares the
 * same `:root` custom properties as fallbacks. Emitting this block any
 * earlier than 8 puts it *before* tokens.css in the document, and since
 * both selectors are plain `:root` (identical specificity) the later rule
 * wins — so every admin-chosen accent, font, radius and background was
 * being silently overridden by the static fallback. Must stay > 8.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! mk_theme_plugin_active() ) {
			return;
		}
		echo '<style id="mk-theme-vars">' . "\n" . ThemeVarsBuilder::build() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated CSS, values validated against the registry.
	},
	20
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
 * Adds data-* attributes on <html> for every setting that drives behaviour
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
			$query->set(
				'orderby',
				array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				)
			);
		}
	}
);

/**
 * Accessible, predictable excerpt behaviour.
 */
add_filter( 'excerpt_more', static fn() => '…' );
add_filter( 'excerpt_length', static fn() => 28, 999 );

if ( ! function_exists( 'mk_client_initials' ) ) {
	/**
	 * Up to two uppercase initials from a client name, for the colour-mark
	 * placeholder the client-logo wall shows when no logo has been
	 * uploaded (reference: sections.tsx ClientLetterMark).
	 *
	 * @param string $name Client display name.
	 * @return string
	 */
	function mk_client_initials( string $name ): string {
		$words  = preg_split( '/\s+/', trim( $name ) );
		$first  = $words[0][0] ?? '';
		$second = $words[1][0] ?? '';
		return strtoupper( $first . $second );
	}
}

if ( ! function_exists( 'mk_client_mark_color' ) ) {
	/**
	 * A stable colour derived from a client's name, so the same client
	 * always gets the same placeholder mark colour across page loads.
	 *
	 * @param string $name Client display name.
	 * @return string CSS `hsl()` colour value.
	 */
	function mk_client_mark_color( string $name ): string {
		$hues = array( 162, 200, 20, 260, 320, 40 );
		$hue  = $hues[ abs( crc32( $name ) ) % count( $hues ) ];
		return "hsl({$hue} 45% 38%)";
	}
}

/**
 * Whether the footer currently rendering carries the newsletter form.
 *
 * Used to keep the subscribe script off every page that cannot show the
 * form at all (GR-06).
 *
 * @return bool
 */
function mk_theme_has_subscribe_form(): bool {
	if ( ! function_exists( 'mk_footer_settings' ) ) {
		return false;
	}

	$footer = mk_footer_settings();

	return 'modern' === $footer['style'] && 'newsletter' === $footer['col4']['type'];
}

/**
 * Whether a Projects section on this request is set to the Gallery layout.
 *
 * The lightbox is the gallery's only consumer outside single templates, so
 * this keeps it off every page that cannot open one (GR-06).
 *
 * @return bool
 */
function mk_theme_has_gallery_section(): bool {
	if ( ! is_front_page() || ! function_exists( 'mk_setting' ) ) {
		return false;
	}

	return 'gallery' === mk_setting( 'projects_layout', 'grid' )
		&& (bool) mk_setting( 'section_projects_enabled', true );
}

/**
 * Whether the partner band on this request is the auto-scrolling variant.
 *
 * @return bool
 */
function mk_theme_has_partner_marquee(): bool {
	if ( ! function_exists( 'mk_setting' ) || ! function_exists( 'mk_content' ) ) {
		return false;
	}

	if ( ! mk_setting( 'section_partners_enabled', true ) || 'marquee' !== mk_setting( 'partners_layout', 'grid' ) ) {
		return false;
	}

	return (bool) mk_content( 'partners' );
}
