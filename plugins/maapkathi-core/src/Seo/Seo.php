<?php
/**
 * Hand-written meta tags and JSON-LD structured data for the public site.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Seo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hand-written meta + JSON-LD (§12) — deliberately independent of whether
 * RankMath is installed, so SEO basics work even on a fresh install before
 * a site owner adds RankMath from the plugin browser. WordPress core's own
 * sitemap (wp-sitemap.xml, since WP 5.5) already indexes every public post
 * type registered with show_in_rest — mk_project, mk_service, and post
 * (when the blog is enabled) — with no code required.
 */
final class Seo {

	/**
	 * Registers every SEO-related hook: meta tags, JSON-LD, robots.txt and
	 * the sitemap post-type filter.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_head', array( $this, 'render_meta' ), 2 );
		add_action( 'wp_head', array( $this, 'render_json_ld' ), 5 );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'filter_sitemap_post_types' ) );
	}

	/**
	 * Prints the description, canonical link and Open Graph/Twitter meta
	 * tags for the current request, on the `wp_head` hook.
	 *
	 * @return void
	 */
	public function render_meta(): void {
		$seo = get_option( 'mk_seo_settings', array() );

		$title       = $this->resolve_title( $seo );
		$description = $this->resolve_description( $seo );
		$image       = $this->resolve_image( $seo );
		$canonical   = $this->canonical_url();

		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );

		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
		printf( '<meta property="og:type" content="%s" />' . "\n", is_singular( 'mk_project' ) ? 'article' : 'website' );

		if ( $image ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
			printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
		} else {
			printf( '<meta name="twitter:card" content="summary" />' . "\n" );
		}

		if ( ! empty( $seo['ga_id'] ) ) {
			printf( '<!-- GA: %s (loaded by RankMath/Site Kit if installed) -->' . "\n", esc_html( $seo['ga_id'] ) );
		}
	}

	/**
	 * Reads the `mk_site_settings` option, guaranteed as an array even if
	 * the option was never saved.
	 *
	 * @return array<string,mixed>
	 */
	private function site_settings(): array {
		$settings = get_option( 'mk_site_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Resolves the page title: the post title when singular, otherwise the
	 * first non-empty candidate from the SEO default, studio name, or the
	 * site's own name.
	 *
	 * @param array<string,mixed> $seo The `mk_seo_settings` option contents.
	 * @return string
	 */
	private function resolve_title( array $seo ): string {
		if ( is_singular() ) {
			return wp_strip_all_tags( get_the_title() );
		}

		$site = $this->site_settings();

		foreach ( array( $seo['default_title'] ?? '', $site['studio_name'] ?? '', get_bloginfo( 'name' ) ) as $candidate ) {
			if ( '' !== trim( (string) $candidate ) ) {
				return (string) $candidate;
			}
		}

		return '';
	}

	/**
	 * Every page must end up with a non-empty description (§11 gate), so
	 * this walks a real fallback chain rather than trusting one option to
	 * be filled in.
	 *
	 * @param array<string,mixed> $seo The `mk_seo_settings` option contents.
	 * @return string
	 */
	private function resolve_description( array $seo ): string {
		$site = $this->site_settings();

		$candidates = array();

		if ( is_singular() ) {
			if ( has_excerpt() ) {
				$candidates[] = get_the_excerpt();
			}
			$post_id = get_the_ID();
			if ( $post_id ) {
				$candidates[] = get_post_meta( $post_id, 'mk_summary', true );
				$candidates[] = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 32 );
			}
		}

		$candidates[] = $seo['default_description'] ?? '';
		$candidates[] = $site['tagline'] ?? '';
		$candidates[] = get_bloginfo( 'description' );

		foreach ( $candidates as $candidate ) {
			$candidate = trim( wp_strip_all_tags( (string) $candidate ) );
			if ( '' !== $candidate ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * OG image chain: the post's own cover → the configured default →
	 * the site logo → a generated branded placeholder, so social shares
	 * never fall back to a blank card.
	 *
	 * @param array<string,mixed> $seo The `mk_seo_settings` option contents.
	 * @return string
	 */
	private function resolve_image( array $seo ): string {
		if ( is_singular() && has_post_thumbnail() ) {
			$src = get_the_post_thumbnail_url( null, 'large' );
			if ( $src ) {
				return (string) $src;
			}
		}

		if ( ! empty( $seo['og_image'] ) ) {
			return (string) $seo['og_image'];
		}

		$logo_light = \Maapkathi\Core\Support\Branding::logo_light_url();
		$logo       = $logo_light ? $logo_light : \Maapkathi\Core\Support\Branding::logo_dark_url();
		if ( $logo ) {
			return $logo;
		}

		$site = $this->site_settings();
		return \Maapkathi\Core\Rest\PlaceholderController::url(
			(string) ( $site['studio_name'] ?? get_bloginfo( 'name' ) ),
			1200,
			630
		);
	}

	/**
	 * Builds the canonical URL for the current request from the global
	 * `$wp` request object, with any query args stripped.
	 *
	 * @return string
	 */
	private function canonical_url(): string {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ?? '' ) );
	}

	/**
	 * Prints every JSON-LD block relevant to the current page: FAQPage on
	 * the front page, and CreativeWork/BreadcrumbList on project and
	 * service singulars.
	 *
	 * @return void
	 */
	public function render_json_ld(): void {
		if ( is_front_page() ) {
			$this->render_faq_page();
		}

		if ( is_singular( 'mk_project' ) ) {
			$this->render_creative_work();
			$this->render_breadcrumbs();
		}

		if ( is_singular( 'mk_service' ) ) {
			$this->render_breadcrumbs();
		}
	}

	/**
	 * Prints a FAQPage JSON-LD block built from the site's FAQ content, if
	 * any FAQs exist.
	 *
	 * @return void
	 */
	private function render_faq_page(): void {
		// Read through the same accessor the template uses, so the
		// structured data can never describe a different set of FAQs than
		// the ones actually rendered on the page (which would be a
		// structured-data violation, not just a mismatch).
		$faqs = \Maapkathi\Core\Support\Content::faqs();

		if ( empty( $faqs ) ) {
			return;
		}

		$entities = array();
		foreach ( $faqs as $faq ) {
			if ( empty( $faq['question'] ) ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $faq['answer'] ),
				),
			);
		}

		if ( empty( $entities ) ) {
			return;
		}

		$this->print_ld_json(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			)
		);
	}

	/**
	 * Prints a CreativeWork JSON-LD block describing the current project.
	 *
	 * @return void
	 */
	private function render_creative_work(): void {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}

		$summary          = get_post_meta( $post_id, 'mk_summary', true );
		$description      = $summary ? $summary : get_the_excerpt( $post_id );
		$thumbnail_url    = get_the_post_thumbnail_url( $post_id, 'large' );
		$location_created = get_post_meta( $post_id, 'mk_location', true );
		$date_created     = get_post_meta( $post_id, 'mk_completed_at', true );

		$this->print_ld_json(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'CreativeWork',
				'name'            => get_the_title( $post_id ),
				'description'     => wp_strip_all_tags( $description ),
				'image'           => $thumbnail_url ? $thumbnail_url : null,
				'locationCreated' => $location_created ? $location_created : null,
				'dateCreated'     => $date_created ? $date_created : null,
			)
		);
	}

	/**
	 * Prints a BreadcrumbList JSON-LD block for the current project or
	 * service page.
	 *
	 * @return void
	 */
	private function render_breadcrumbs(): void {
		$items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'maapkathi' ),
				'item'     => home_url( '/' ),
			),
		);

		if ( is_singular( 'mk_project' ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Work', 'maapkathi' ),
				'item'     => home_url( '/work/' ),
			);
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => get_the_title(),
				'item'     => get_permalink(),
			);
		} else {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Services', 'maapkathi' ),
				'item'     => home_url( '/services/' ),
			);
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => get_the_title(),
				'item'     => get_permalink(),
			);
		}

		$this->print_ld_json(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $items,
			)
		);
	}

	/**
	 * Filters out null values and echoes the given data as a JSON-LD
	 * `<script>` block.
	 *
	 * @param array<string,mixed> $data Structured-data payload to encode.
	 * @return void
	 */
	private function print_ld_json( array $data ): void {
		$data = array_filter( $data, static fn( $v ) => null !== $v );
		echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Appends a Disallow rule and the sitemap URL to robots.txt output when
	 * the site is set to be publicly indexable.
	 *
	 * @param string $output       Existing robots.txt output.
	 * @param bool   $is_public Whether the site is marked public (Settings > Reading).
	 * @return string
	 */
	public function robots_txt( string $output, bool $is_public ): string {
		if ( $is_public ) {
			$output .= "Disallow: /wp-admin/\n";
			$output .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
		}
		return $output;
	}

	/**
	 * Removes the built-in `post` type from the WP sitemap when the blog
	 * feature is disabled in site settings.
	 *
	 * @param string[] $post_types Post type names currently included in the sitemap.
	 * @return string[]
	 */
	public function filter_sitemap_post_types( $post_types ) {
		$site_settings = get_option( 'mk_site_settings', array() );
		if ( empty( $site_settings['blog_enabled'] ) && isset( $post_types['post'] ) ) {
			unset( $post_types['post'] );
		}
		return $post_types;
	}
}
