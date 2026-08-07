<?php
/**
 * Custom taxonomy registration.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the custom taxonomies used by the plugin's post types.
 */
final class Taxonomies {

	/**
	 * Wire the init hook that registers taxonomies.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register the mk_project_category taxonomy.
	 */
	public function register_taxonomies(): void {
		register_taxonomy(
			'mk_project_category',
			array( 'mk_project' ),
			array(
				'labels'            => array(
					'name'          => __( 'Project Categories', 'maapkathi' ),
					'singular_name' => __( 'Project Category', 'maapkathi' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'work-category' ),
			)
		);
	}
}
