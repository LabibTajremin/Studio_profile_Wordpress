<?php
/**
 * Read-side accessors for public content types, used by the theme.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-side accessors for every content type the public templates render.
 *
 * The theme must never build its own WP_Query against mk_* post types —
 * ordering rules (menu_order, then the mk_sort_order meta, then date) live
 * here so every section orders consistently and the theme stays
 * presentation-only.
 */
final class Content {

	/**
	 * Runs a shared, consistently-ordered `WP_Query` for one post type.
	 *
	 * @param string              $post_type Post type slug to query.
	 * @param int                 $limit     Max posts to return, or -1 for all.
	 * @param array<string,mixed> $extra     Extra `get_posts()` args, merged over the defaults.
	 * @return \WP_Post[]
	 */
	public static function items( string $post_type, int $limit = -1, array $extra = array() ): array {
		$args = array_merge(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'posts_per_page'   => $limit,
				'orderby'          => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'no_found_rows'    => true,
				'suppress_filters' => false,
			),
			$extra
		);

		return get_posts( $args );
	}

	/**
	 * Projects flagged as featured, falling back to the most recent
	 * projects when nothing is flagged yet.
	 *
	 * @param int $limit Max projects to return.
	 * @return \WP_Post[]
	 */
	public static function featured_projects( int $limit = 6 ): array {
		$featured = self::items(
			'mk_project',
			$limit,
			array(
				'meta_query' => array(
					array(
						'key'     => 'mk_is_featured',
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);

		// A brand-new site has nothing flagged featured yet — fall back to
		// the most recent projects so the homepage is never empty.
		if ( empty( $featured ) ) {
			$featured = self::items( 'mk_project', $limit );
		}

		return $featured;
	}

	/**
	 * All published clients.
	 *
	 * @return \WP_Post[]
	 */
	public static function clients(): array {
		return self::items( 'mk_client' );
	}

	/**
	 * All published testimonials.
	 *
	 * @return \WP_Post[]
	 */
	public static function testimonials(): array {
		return self::items( 'mk_testimonial' );
	}

	/**
	 * All published awards.
	 *
	 * @return \WP_Post[]
	 */
	public static function awards(): array {
		return self::items( 'mk_award' );
	}

	/**
	 * All published stats.
	 *
	 * @return \WP_Post[]
	 */
	public static function stats(): array {
		return self::items( 'mk_stat' );
	}

	/**
	 * All published values.
	 *
	 * @return \WP_Post[]
	 */
	public static function values(): array {
		return self::items( 'mk_value' );
	}

	/**
	 * All published process steps.
	 *
	 * @return \WP_Post[]
	 */
	public static function process_steps(): array {
		return self::items( 'mk_process_step' );
	}

	/**
	 * All published team members.
	 *
	 * @param int $limit Max members to return, or -1 for all.
	 * @return \WP_Post[]
	 */
	public static function members( int $limit = -1 ): array {
		return self::items( 'mk_member', $limit );
	}

	/**
	 * Top-level services (no parent service).
	 *
	 * @param int $limit Max services to return, or -1 for all.
	 * @return \WP_Post[]
	 */
	public static function top_level_services( int $limit = -1 ): array {
		return self::items( 'mk_service', $limit, array( 'post_parent' => 0 ) );
	}

	/**
	 * Services whose parent is the given service.
	 *
	 * @param int $parent_id Parent service post ID.
	 * @return \WP_Post[]
	 */
	public static function child_services( int $parent_id ): array {
		return self::items( 'mk_service', -1, array( 'post_parent' => $parent_id ) );
	}

	/**
	 * FAQs, normalised to {question, answer} regardless of whether they
	 * live as mk_faq posts (the canonical source) or as legacy rows on
	 * mk_site_settings.
	 *
	 * @return array<int,array{question:string,answer:string}>
	 */
	public static function faqs(): array {
		$out = array();

		foreach ( self::items( 'mk_faq' ) as $faq ) {
			$out[] = array(
				'question' => get_the_title( $faq ),
				'answer'   => wp_strip_all_tags( $faq->post_content ),
			);
		}

		if ( empty( $out ) ) {
			$settings = get_option( 'mk_site_settings', array() );
			foreach ( (array) ( $settings['faqs'] ?? array() ) as $row ) {
				if ( ! empty( $row['question'] ) ) {
					$out[] = array(
						'question' => (string) $row['question'],
						'answer'   => (string) ( $row['answer'] ?? '' ),
					);
				}
			}
		}

		return $out;
	}

	/**
	 * Project categories that actually have published projects, for the
	 * homepage "portfolio categories" section.
	 *
	 * @return \WP_Term[]
	 */
	public static function project_categories(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'mk_project_category',
				'hide_empty' => true,
			)
		);

		return is_wp_error( $terms ) ? array() : $terms;
	}
}
