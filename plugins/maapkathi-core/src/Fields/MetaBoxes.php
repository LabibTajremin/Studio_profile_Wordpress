<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Fields;

use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions for every CPT (§3.1), version-controlled PHP — the
 * "no click-configure ACF" requirement, satisfied with native
 * register_post_meta() + meta boxes instead of a Composer field-engine
 * dependency this build cannot assume is installable on shared hosting.
 */
final class MetaBoxes {

	/**
	 * @return array<string, array<int, array{key:string,label:string,type:string,help?:string}>>
	 */
	public static function schema(): array {
		return array(
			'mk_project'      => array(
				array(
					'key'   => 'mk_summary',
					'label' => __( 'Summary', 'maapkathi' ),
					'type'  => 'textarea',
				),
				array(
					'key'   => 'mk_is_featured',
					'label' => __( 'Featured on homepage', 'maapkathi' ),
					'type'  => 'checkbox',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_completed_at',
					'label' => __( 'Completed', 'maapkathi' ),
					'type'  => 'date',
				),
				array(
					'key'   => 'mk_location',
					'label' => __( 'Location', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_client_name',
					'label' => __( 'Client name', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_area_sqft',
					'label' => __( 'Area (sq ft)', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_gallery',
					'label' => __( 'Gallery (comma-separated attachment IDs)', 'maapkathi' ),
					'type'  => 'gallery',
					'help'  => __( 'Media ratio hint: project gallery images should be 4:5.', 'maapkathi' ),
				),
			),
			'mk_service'      => array(
				array(
					'key'   => 'mk_icon',
					'label' => __( 'Icon (dashicon class)', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_gallery',
					'label' => __( 'Gallery (comma-separated attachment IDs)', 'maapkathi' ),
					'type'  => 'gallery',
				),
			),
			'mk_member'       => array(
				array(
					'key'   => 'mk_role_title',
					'label' => __( 'Role / title', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_bio',
					'label' => __( 'Bio', 'maapkathi' ),
					'type'  => 'textarea',
				),
				array(
					'key'   => 'mk_social_linkedin',
					'label' => __( 'LinkedIn URL', 'maapkathi' ),
					'type'  => 'url',
				),
				array(
					'key'   => 'mk_social_instagram',
					'label' => __( 'Instagram URL', 'maapkathi' ),
					'type'  => 'url',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_testimonial'  => array(
				array(
					'key'   => 'mk_author_name',
					'label' => __( 'Author name', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_author_role',
					'label' => __( 'Author role', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_company',
					'label' => __( 'Company', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_quote',
					'label' => __( 'Quote', 'maapkathi' ),
					'type'  => 'textarea',
				),
				array(
					'key'   => 'mk_rating',
					'label' => __( 'Rating (1-5)', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_client'       => array(
				array(
					'key'   => 'mk_website',
					'label' => __( 'Website', 'maapkathi' ),
					'type'  => 'url',
				),
				array(
					'key'   => 'mk_is_featured',
					'label' => __( 'Featured', 'maapkathi' ),
					'type'  => 'checkbox',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_award'        => array(
				array(
					'key'   => 'mk_issuer',
					'label' => __( 'Issuer', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_year',
					'label' => __( 'Year', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_link',
					'label' => __( 'Link', 'maapkathi' ),
					'type'  => 'url',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_faq'          => array(
				array(
					'key'   => 'mk_group',
					'label' => __( 'Group', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_value'        => array(
				array(
					'key'   => 'mk_icon',
					'label' => __( 'Icon (dashicon class)', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_stat'         => array(
				array(
					'key'   => 'mk_value_number',
					'label' => __( 'Value', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_suffix',
					'label' => __( 'Suffix (e.g. +, %)', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
			'mk_process_step' => array(
				array(
					'key'   => 'mk_step_no',
					'label' => __( 'Step number', 'maapkathi' ),
					'type'  => 'number',
				),
				array(
					'key'   => 'mk_icon',
					'label' => __( 'Icon (dashicon class)', 'maapkathi' ),
					'type'  => 'text',
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	public function register_meta(): void {
		foreach ( self::schema() as $post_type => $fields ) {
			foreach ( $fields as $field ) {
				register_post_meta(
					$post_type,
					$field['key'],
					array(
						'type'          => match ( $field['type'] ) {
							'number'   => 'number',
							'checkbox' => 'boolean',
							default    => 'string',
						},
						'single'        => true,
						'show_in_rest'  => true,
						'auth_callback' => static function () {
							return current_user_can( Roles::CAP_EDIT_CONTENT );
						},
					)
				);
			}
		}
	}

	public function add_meta_boxes(): void {
		foreach ( self::schema() as $post_type => $fields ) {
			add_meta_box(
				'mk_fields_' . $post_type,
				__( 'Maapkathi Fields', 'maapkathi' ),
				function ( \WP_Post $post ) use ( $fields ): void {
					$this->render( $post, $fields );
				},
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * @param array<int,array{key:string,label:string,type:string,help?:string}> $fields
	 */
	private function render( \WP_Post $post, array $fields ): void {
		wp_nonce_field( 'mk_save_fields_' . $post->ID, 'mk_fields_nonce' );
		echo '<table class="form-table">';
		foreach ( $fields as $field ) {
			$value = get_post_meta( $post->ID, $field['key'], true );
			echo '<tr><th><label for="' . esc_attr( $field['key'] ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			$this->render_input( $field, $value );
			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * @param array{key:string,label:string,type:string} $field
	 */
	private function render_input( array $field, $value ): void {
		$id = esc_attr( $field['key'] );
		switch ( $field['type'] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="%1$s" rows="4" class="large-text">%2$s</textarea>', $id, esc_textarea( (string) $value ) );
				break;
			case 'checkbox':
				printf( '<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s />', $id, checked( (bool) $value, true, false ) );
				break;
			case 'number':
				printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text" />', $id, esc_attr( (string) $value ) );
				break;
			case 'date':
				printf( '<input type="date" id="%1$s" name="%1$s" value="%2$s" />', $id, esc_attr( (string) $value ) );
				break;
			case 'url':
				printf( '<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />', $id, esc_attr( (string) $value ) );
				break;
			case 'gallery':
				printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="large-text" placeholder="12,34,56" />', $id, esc_attr( (string) $value ) );
				break;
			default:
				printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />', $id, esc_attr( (string) $value ) );
		}
	}

	public function save( int $post_id, \WP_Post $post ): void {
		$fields = self::schema()[ $post->post_type ] ?? null;
		if ( null === $fields ) {
			return;
		}
		if ( ! isset( $_POST['mk_fields_nonce'] ) || ! check_admin_referer( 'mk_save_fields_' . $post_id, 'mk_fields_nonce' ) ) {
			return;
		}
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			return;
		}

		foreach ( $fields as $field ) {
			$key = $field['key'];
			if ( 'checkbox' === $field['type'] ) {
				update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
				continue;
			}
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw       = wp_unslash( $_POST[ $key ] );
			$sanitized = match ( $field['type'] ) {
				'textarea' => sanitize_textarea_field( $raw ),
				'number'   => is_numeric( $raw ) ? (float) $raw : 0,
				'url'      => esc_url_raw( $raw ),
				'date'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '',
				default    => sanitize_text_field( $raw ),
			};
			update_post_meta( $post_id, $key, $sanitized );
		}
	}
}
