<?php
/**
 * Native meta box field definitions and rendering for every CPT.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Fields;

use Maapkathi\Core\Icons\IconLibrary;
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
	 * The field schema for every CPT: post type slug to an ordered list of field definitions.
	 *
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
					'key'   => 'mk_icon_id',
					'label' => __( 'Icon', 'maapkathi' ),
					'type'  => 'icon',
				),
				array(
					'key'   => 'mk_icon_svg',
					'label' => __( 'Or upload an SVG', 'maapkathi' ),
					'type'  => 'media',
					'help'  => __( 'An SVG uploaded here replaces the icon chosen above and is tinted with your accent colour. PNG and JPG cannot be recoloured, so they render in their original colours instead — use SVG wherever you can.', 'maapkathi' ),
				),
				array(
					'key'   => 'mk_icon',
					'label' => __( 'Legacy icon (dashicon class)', 'maapkathi' ),
					'type'  => 'text',
					'help'  => __( 'Only used when neither of the two fields above is set. Kept so icons chosen before the icon library existed keep working.', 'maapkathi' ),
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
			'mk_partner'      => array(
				array(
					'key'   => 'mk_website',
					'label' => __( 'Website', 'maapkathi' ),
					'type'  => 'url',
				),
				array(
					'key'   => 'mk_alt_text',
					'label' => __( 'Logo alt text', 'maapkathi' ),
					'type'  => 'text',
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
					'key'   => 'mk_icon_id',
					'label' => __( 'Icon', 'maapkathi' ),
					'type'  => 'icon',
				),
				array(
					'key'   => 'mk_icon_svg',
					'label' => __( 'Or upload an SVG', 'maapkathi' ),
					'type'  => 'media',
					'help'  => __( 'An SVG uploaded here replaces the icon chosen above and is tinted with your accent colour. PNG and JPG cannot be recoloured, so they render in their original colours instead — use SVG wherever you can.', 'maapkathi' ),
				),
				array(
					'key'   => 'mk_icon',
					'label' => __( 'Legacy icon (dashicon class)', 'maapkathi' ),
					'type'  => 'text',
					'help'  => __( 'Only used when neither of the two fields above is set. Kept so icons chosen before the icon library existed keep working.', 'maapkathi' ),
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
					'key'   => 'mk_icon_id',
					'label' => __( 'Icon', 'maapkathi' ),
					'type'  => 'icon',
				),
				array(
					'key'   => 'mk_icon_svg',
					'label' => __( 'Or upload an SVG', 'maapkathi' ),
					'type'  => 'media',
					'help'  => __( 'An SVG uploaded here replaces the icon chosen above and is tinted with your accent colour. PNG and JPG cannot be recoloured, so they render in their original colours instead — use SVG wherever you can.', 'maapkathi' ),
				),
				array(
					'key'   => 'mk_icon',
					'label' => __( 'Legacy icon (dashicon class)', 'maapkathi' ),
					'type'  => 'text',
					'help'  => __( 'Only used when neither of the two fields above is set. Kept so icons chosen before the icon library existed keep working.', 'maapkathi' ),
				),
				array(
					'key'   => 'mk_sort_order',
					'label' => __( 'Sort order', 'maapkathi' ),
					'type'  => 'number',
				),
			),
		);
	}

	/**
	 * Wire the hooks that register meta, render meta boxes, and save field values.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register every field in the schema as post meta with REST support.
	 */
	public function register_meta(): void {
		foreach ( self::schema() as $post_type => $fields ) {
			foreach ( $fields as $field ) {
				register_post_meta(
					$post_type,
					$field['key'],
					array(
						'type'          => match ( $field['type'] ) {
							'number', 'media' => 'number',
							'checkbox'        => 'boolean',
							default           => 'string',
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

	/**
	 * Register the "Maapkathi Fields" meta box on every CPT edit screen that has a schema.
	 */
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
	 * Render the meta box table for a post's fields.
	 *
	 * @param \WP_Post                                                           $post   Post being edited.
	 * @param array<int,array{key:string,label:string,type:string,help?:string}> $fields Field definitions to render.
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
	 * Render a single field's input control, matched to its declared type.
	 *
	 * @param array{key:string,label:string,type:string} $field Field definition to render an input for.
	 * @param mixed                                      $value Current stored value for the field.
	 */
	private function render_input( array $field, $value ): void {
		$id = $field['key'];
		switch ( $field['type'] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="%1$s" rows="4" class="large-text">%2$s</textarea>', esc_attr( $id ), esc_textarea( (string) $value ) );
				break;
			case 'checkbox':
				printf( '<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s />', esc_attr( $id ), checked( (bool) $value, true, false ) );
				break;
			case 'number':
				printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text" />', esc_attr( $id ), esc_attr( (string) $value ) );
				break;
			case 'date':
				printf( '<input type="date" id="%1$s" name="%1$s" value="%2$s" />', esc_attr( $id ), esc_attr( (string) $value ) );
				break;
			case 'url':
				printf( '<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />', esc_attr( $id ), esc_attr( (string) $value ) );
				break;
			case 'gallery':
				printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="large-text" placeholder="12,34,56" />', esc_attr( $id ), esc_attr( (string) $value ) );
				break;
			case 'icon':
				$this->render_icon_picker( $id, (string) $value );
				break;
			case 'media':
				$this->render_media_field( $id, absint( $value ) );
				break;
			default:
				printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />', esc_attr( $id ), esc_attr( (string) $value ) );
		}
	}

	/**
	 * Renders the bundled icon picker (FR-07.2).
	 *
	 * A searchable radio grid rather than a select, so the admin picks by
	 * looking at the icon instead of guessing from a name. The live preview
	 * is the swatch itself — the chosen one is outlined — which is cheaper
	 * and more honest than a separate preview pane that could fall out of
	 * sync with the selection.
	 *
	 * @param string $id    Field key, used as the input name.
	 * @param string $value Currently stored icon id.
	 * @return void
	 */
	private function render_icon_picker( string $id, string $value ): void {
		printf(
			'<div class="mk-icon-picker" data-mk-icon-picker><input type="search" class="mk-icon-picker__search" placeholder="%s" aria-label="%s" /><div class="mk-icon-picker__grid">',
			esc_attr__( 'Search icons…', 'maapkathi' ),
			esc_attr__( 'Search icons', 'maapkathi' )
		);

		// An explicit "no icon" choice, so an icon can be removed again
		// without clearing the field by hand.
		printf(
			'<label class="mk-icon-picker__option" data-name="none"><input type="radio" name="%1$s" value="" %2$s /><span class="mk-icon-picker__none">%3$s</span></label>',
			esc_attr( $id ),
			checked( '', $value, false ),
			esc_html__( 'None', 'maapkathi' )
		);

		foreach ( IconLibrary::all() as $icon_id => $icon ) {
			printf(
				'<label class="mk-icon-picker__option" data-name="%1$s" title="%2$s"><input type="radio" name="%3$s" value="%1$s" %4$s />%5$s<span class="mk-icon-picker__label">%2$s</span></label>',
				esc_attr( $icon_id ),
				esc_attr( $icon['label'] ),
				esc_attr( $id ),
				checked( $icon_id, $value, false ),
				IconLibrary::svg( $icon_id, 28 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled inline SVG, escaped at source.
			);
		}

		echo '</div></div>';
	}

	/**
	 * Renders an attachment-id media field with a preview.
	 *
	 * @param string $id    Field key, used as the input name.
	 * @param int    $value Currently stored attachment id.
	 * @return void
	 */
	private function render_media_field( string $id, int $value ): void {
		$src = $value ? wp_get_attachment_image_url( $value, 'thumbnail' ) : '';

		printf(
			'<div data-mk-media><input type="hidden" id="%1$s" name="%1$s" value="%2$d" /><div class="mk-media__preview">%3$s</div><button type="button" class="button mk-media__choose">%4$s</button> <button type="button" class="button-link mk-media__clear" %5$s>%6$s</button></div>',
			esc_attr( $id ),
			absint( $value ),
			$src ? '<img src="' . esc_url( $src ) . '" alt="" />' : '',
			esc_html__( 'Choose image', 'maapkathi' ),
			$value ? '' : 'hidden',
			esc_html__( 'Clear', 'maapkathi' )
		);
	}

	/**
	 * Sanitize and save submitted field values for a post, after verifying the nonce and capability.
	 *
	 * @param int      $post_id ID of the post being saved.
	 * @param \WP_Post $post    Post being saved.
	 */
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
				// An icon id that is not in the library is dropped rather
				// than stored, so the front end never has to guess.
				'icon'     => IconLibrary::has( (string) $raw ) ? (string) $raw : '',
				'media'    => absint( $raw ),
				'number'   => is_numeric( $raw ) ? (float) $raw : 0,
				'url'      => esc_url_raw( $raw ),
				'date'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '',
				default    => sanitize_text_field( $raw ),
			};
			update_post_meta( $post_id, $key, $sanitized );
		}
	}
}
