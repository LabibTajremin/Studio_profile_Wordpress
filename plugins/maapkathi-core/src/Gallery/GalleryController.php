<?php
/**
 * "Load more" endpoint for the projects gallery (FR-04.10).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Gallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves the next page of gallery items as ready-made markup.
 *
 * Returning markup rather than JSON is deliberate: the front end appends
 * exactly what the server would have rendered on first paint, so an
 * appended row cannot style or behave differently from the rows above it.
 *
 * Route: GET /wp-json/maapkathi/v1/gallery
 */
final class GalleryController {

	private const NAMESPACE = 'maapkathi/v1';

	/**
	 * Wire the REST route.
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the public GET /gallery route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/gallery',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'page' ),
				// Reads nothing but already-published content, so it is as
				// public as the page that shows it.
				'permission_callback' => '__return_true',
				'args'                => array(
					'offset'   => array(
						'type'    => 'integer',
						'default' => 0,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 12,
					),
					'category' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Returns one page of gallery items.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function page( \WP_REST_Request $request ): \WP_REST_Response {
		$offset   = max( 0, (int) $request->get_param( 'offset' ) );
		$per_page = max( 1, min( 60, (int) $request->get_param( 'per_page' ) ) );
		$category = sanitize_title( (string) $request->get_param( 'category' ) );

		$settings = get_option( 'mk_site_settings', array() );
		$click    = is_array( $settings ) && 'link' === ( $settings['gallery_click'] ?? 'lightbox' ) ? 'link' : 'lightbox';

		$query_args = array(
			'post_type'        => 'mk_project',
			'post_status'      => 'publish',
			'posts_per_page'   => $per_page,
			'offset'           => $offset,
			// FR-04.9: the admin's manual order first, publish date after.
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'suppress_filters' => false,
		);

		if ( '' !== $category ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'mk_project_category',
					'field'    => 'slug',
					'terms'    => $category,
				),
			);
		}

		$query = new \WP_Query( $query_args );

		return new \WP_REST_Response(
			array(
				'html'      => GalleryRenderer::items(
					$query->posts,
					array(
						'click'  => $click,
						'offset' => $offset,
					)
				),
				// The front end needs to know whether to keep the button, and
				// working that out from the item count alone would be wrong
				// as soon as one item is skipped for a missing image.
				'remaining' => max( 0, (int) $query->found_posts - ( $offset + count( $query->posts ) ) ),
			),
			200
		);
	}
}
