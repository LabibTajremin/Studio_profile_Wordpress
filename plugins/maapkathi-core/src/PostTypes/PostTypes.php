<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers every custom post type from §3.1. Fields on each CPT are defined
 * separately in Fields\MetaBoxes — native register_post_meta() + meta boxes,
 * version-controlled PHP, no ACF/Carbon Fields dependency (that would be a
 * Composer package this build cannot assume is fetchable at deploy time on
 * shared hosting) and no click-configuration.
 */
final class PostTypes {

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		return array(
			'mk_project'      => array(
				'label'    => __( 'Projects', 'maapkathi' ),
				'singular' => __( 'Project', 'maapkathi' ),
				'slug'     => 'work',
				'icon'     => 'dashicons-portfolio',
				'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			),
			'mk_service'      => array(
				'label'        => __( 'Services', 'maapkathi' ),
				'singular'     => __( 'Service', 'maapkathi' ),
				'slug'         => 'services',
				'icon'         => 'dashicons-hammer',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
				'hierarchical' => true,
			),
			'mk_member'       => array(
				'label'    => __( 'Team Members', 'maapkathi' ),
				'singular' => __( 'Team Member', 'maapkathi' ),
				'slug'     => 'team-member',
				'icon'     => 'dashicons-groups',
				'supports' => array( 'title', 'thumbnail' ),
				'public'   => false,
			),
			'mk_testimonial'  => array(
				'label'    => __( 'Testimonials', 'maapkathi' ),
				'singular' => __( 'Testimonial', 'maapkathi' ),
				'slug'     => 'testimonial',
				'icon'     => 'dashicons-format-quote',
				'supports' => array( 'title' ),
				'public'   => false,
			),
			'mk_client'       => array(
				'label'    => __( 'Clients', 'maapkathi' ),
				'singular' => __( 'Client', 'maapkathi' ),
				'slug'     => 'client',
				'icon'     => 'dashicons-store',
				'supports' => array( 'title', 'thumbnail' ),
				'public'   => false,
			),
			'mk_award'        => array(
				'label'    => __( 'Awards', 'maapkathi' ),
				'singular' => __( 'Award', 'maapkathi' ),
				'slug'     => 'award',
				'icon'     => 'dashicons-awards',
				'supports' => array( 'title', 'thumbnail' ),
				'public'   => false,
			),
			'mk_faq'          => array(
				'label'    => __( 'FAQs', 'maapkathi' ),
				'singular' => __( 'FAQ', 'maapkathi' ),
				'slug'     => 'faq',
				'icon'     => 'dashicons-editor-help',
				'supports' => array( 'title', 'editor' ),
				'public'   => false,
			),
			'mk_value'        => array(
				'label'    => __( 'Values', 'maapkathi' ),
				'singular' => __( 'Value', 'maapkathi' ),
				'slug'     => 'value',
				'icon'     => 'dashicons-heart',
				'supports' => array( 'title', 'editor' ),
				'public'   => false,
			),
			'mk_stat'         => array(
				'label'    => __( 'Stats', 'maapkathi' ),
				'singular' => __( 'Stat', 'maapkathi' ),
				'slug'     => 'stat',
				'icon'     => 'dashicons-chart-bar',
				'supports' => array( 'title' ),
				'public'   => false,
			),
			'mk_process_step' => array(
				'label'    => __( 'Process Steps', 'maapkathi' ),
				'singular' => __( 'Process Step', 'maapkathi' ),
				'slug'     => 'process-step',
				'icon'     => 'dashicons-list-view',
				'supports' => array( 'title', 'editor' ),
				'public'   => false,
			),
		);
	}

	public function register(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	public function register_post_types(): void {
		foreach ( self::definitions() as $post_type => $definition ) {
			register_post_type( $post_type, $this->args_for( $definition ) );
		}
	}

	/**
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	private function args_for( array $definition ): array {
		$public = $definition['public'] ?? true;

		return array(
			'label'               => $definition['label'],
			'labels'              => array(
				'name'          => $definition['label'],
				'singular_name' => $definition['singular'],
			),
			'public'              => $public,
			'publicly_queryable'  => $public,
			'show_ui'             => true,
			'show_in_menu'        => 'maapkathi', // Nests as a submenu under the custom "Maapkathi" top-level menu (§9).
			'show_in_rest'        => true,
			'menu_icon'           => $definition['icon'],
			'supports'            => $definition['supports'],
			'hierarchical'        => $definition['hierarchical'] ?? false,
			'has_archive'         => $public,
			'rewrite'             => $public ? array( 'slug' => $definition['slug'] ) : false,
			'capability_type'     => array( 'mk_post', 'mk_posts' ),
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'edit_post'          => \Maapkathi\Core\Roles\Roles::CAP_EDIT_CONTENT,
				'edit_posts'         => \Maapkathi\Core\Roles\Roles::CAP_EDIT_CONTENT,
				'edit_others_posts'  => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'publish_posts'      => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'read_post'          => 'read',
				'read_private_posts' => \Maapkathi\Core\Roles\Roles::CAP_EDIT_CONTENT,
				'delete_post'        => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
			),
		);
	}
}
