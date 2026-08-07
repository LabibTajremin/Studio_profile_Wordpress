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
				// No CPT archive: /services is a hand-built WP Page
				// (page-services.php) per §3.2, not a generic archive.
				// Individual services still resolve at /services/{slug}.
				'has_archive'  => false,
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
			'label'              => $definition['label'],
			'labels'             => array(
				'name'          => $definition['label'],
				'singular_name' => $definition['singular'],
			),
			'public'             => $public,
			'publicly_queryable' => $public,
			'show_ui'            => true,
			'show_in_menu'       => 'maapkathi', // Nests as a submenu under the custom "Maapkathi" top-level menu (§9).
			'show_in_rest'       => true,
			'menu_icon'          => $definition['icon'],
			// 'custom-fields' is required for WordPress to expose a `meta`
			// property in the REST schema at all — without it, every field
			// Fields\MetaBoxes registers via register_post_meta() is
			// invisible to the block editor's REST-based save, even though
			// register_post_meta() itself succeeds silently. Verified live:
			// WP_REST_Posts_Controller::get_item_schema() gates the whole
			// 'meta' property on post_type_supports( $type, 'custom-fields' ).
			'supports'           => array_unique( array_merge( $definition['supports'], array( 'custom-fields' ) ) ),
			'hierarchical'       => $definition['hierarchical'] ?? false,
			'has_archive'        => $definition['has_archive'] ?? $public,
			'rewrite'            => $public ? array( 'slug' => $definition['slug'] ) : false,
			'capability_type'    => array( 'mk_post', 'mk_posts' ),
			'map_meta_cap'       => true,
			// Deliberately do NOT override 'edit_post' / 'read_post' /
			// 'delete_post' (the singular meta-cap keys). WordPress's
			// _post_type_meta_capabilities() globally registers whatever
			// string is used as their VALUE as an alias requiring a specific
			// post ID (via the $post_type_meta_caps global, consulted on
			// every map_meta_cap() call, for every post type). Reusing our
			// own bare capability strings there — as an earlier version of
			// this file did — silently breaks every bare
			// current_user_can(Roles::CAP_EDIT_CONTENT) check across the
			// entire plugin/admin, including basic login access. Leaving
			// these three unset lets WordPress auto-derive harmless,
			// collision-free per-post-type strings (edit_mk_post, etc.)
			// that are never checked directly by our own code.
			'capabilities'       => array(
				'edit_posts'             => \Maapkathi\Core\Roles\Roles::CAP_EDIT_CONTENT,
				'delete_posts'           => \Maapkathi\Core\Roles\Roles::CAP_EDIT_CONTENT,
				'read_private_posts'     => \Maapkathi\Core\Roles\Roles::CAP_EDIT_CONTENT,
				'edit_others_posts'      => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'edit_private_posts'     => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'edit_published_posts'   => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'publish_posts'          => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'delete_private_posts'   => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'delete_published_posts' => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
				'delete_others_posts'    => \Maapkathi\Core\Roles\Roles::CAP_PUBLISH_CONTENT,
			),
		);
	}
}
