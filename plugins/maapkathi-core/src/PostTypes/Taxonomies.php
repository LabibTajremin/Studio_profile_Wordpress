<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Taxonomies {

	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

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
