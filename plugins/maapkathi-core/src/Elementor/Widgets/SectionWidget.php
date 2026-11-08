<?php
/**
 * Base class for the section widgets.
 *
 * Each widget renders the same theme partial the homepage renders, so a
 * section dropped into Elementor is the real section and not a lookalike
 * that drifts out of step with it.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Maapkathi\Core\Elementor\Support;
use Maapkathi\Core\Sections\SectionLayout;
use Maapkathi\Core\Sections\SectionRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders one theme section as an Elementor widget.
 */
abstract class SectionWidget extends Widget_Base {

	/**
	 * The section type this widget renders, e.g. "services".
	 *
	 * @return string
	 */
	abstract protected function section_type(): string;

	/**
	 * Elementor's internal widget name.
	 */
	public function get_name(): string {
		return 'mk-' . $this->section_type();
	}

	/**
	 * Panel label.
	 */
	public function get_title(): string {
		$registry = SectionRegistry::all();
		$type     = $this->section_type();

		return isset( $registry[ $type ]['label'] )
			? (string) $registry[ $type ]['label']
			: ucfirst( $type );
	}

	/**
	 * Panel icon.
	 */
	public function get_icon(): string {
		$icons = array(
			'tagline'      => 'eicon-text',
			'clients'      => 'eicon-logo',
			'categories'   => 'eicon-folder-o',
			'projects'     => 'eicon-gallery-grid',
			'services'     => 'eicon-bullet-list',
			'stats'        => 'eicon-counter',
			'values'       => 'eicon-info-circle-o',
			'team'         => 'eicon-person',
			'testimonials' => 'eicon-testimonial',
			'awards'       => 'eicon-star-o',
			'faq'          => 'eicon-help-o',
			'partners'     => 'eicon-handshake-o',
			'cta'          => 'eicon-button',
			'map'          => 'eicon-google-maps',
		);

		return $icons[ $this->section_type() ] ?? 'eicon-section';
	}

	/**
	 * Panel category.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( Support::CATEGORY );
	}

	/**
	 * Keywords so the section is findable by what it shows, not only by
	 * the name the admin may have renamed it to.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array( 'maapkathi', 'section', $this->section_type() );
	}

	/**
	 * These sections pull their own content from the Maapkathi screens, so
	 * nothing about them is cached per-widget.
	 *
	 * @return bool
	 */
	public function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * The instance ids this section type already has in the homepage
	 * layout, offered so a widget can borrow an existing instance's
	 * heading and anchor rather than inventing a second set.
	 *
	 * @return array<string,string>
	 */
	protected function instance_options(): array {
		$type = $this->section_type();
		$out  = array( $type => __( 'Default', 'maapkathi' ) );

		foreach ( SectionLayout::get() as $row ) {
			if ( $row['type'] !== $type || $row['id'] === $type ) {
				continue;
			}

			$out[ $row['id'] ] = $row['id'];
		}

		return $out;
	}

	/**
	 * Widget controls.
	 *
	 * Deliberately few. The section's own content is edited on the
	 * Maapkathi screens, and duplicating those fields here would give an
	 * admin two places to change the same thing and no way to tell which
	 * one won.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$this->start_controls_section(
			'mk_section',
			array(
				'label' => __( 'Section', 'maapkathi' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$options = $this->instance_options();

		if ( count( $options ) > 1 ) {
			$this->add_control(
				'mk_instance_id',
				array(
					'label'       => __( 'Which copy', 'maapkathi' ),
					'type'        => Controls_Manager::SELECT,
					'options'     => $options,
					'default'     => $this->section_type(),
					'description' => __( 'Which saved copy of this section to show, with its own heading and anchor.', 'maapkathi' ),
				)
			);
		}

		$this->add_control(
			'mk_edit_hint',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => wp_kses(
					sprintf(
						/* translators: %s: link to the Maapkathi admin menu. */
						esc_html__( 'The words and items in this section are edited under %s, so they stay the same everywhere the section appears.', 'maapkathi' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=maapkathi' ) ) . '" target="_blank">' . esc_html__( 'Maapkathi', 'maapkathi' ) . '</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renders the theme partial.
	 *
	 * @return void
	 */
	protected function render(): void {
		$type = $this->section_type();

		$file = function_exists( 'get_theme_file_path' )
			? get_theme_file_path( 'parts/sections/' . $type . '.php' )
			: '';

		if ( '' === $file || ! file_exists( $file ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		// $mk_id is the contract every section partial reads: it derives the
		// anchor and every DOM id from it, which is what keeps two copies of
		// one section type from colliding on the same page.
		$mk_id = isset( $settings['mk_instance_id'] ) && is_string( $settings['mk_instance_id'] ) && '' !== $settings['mk_instance_id']
			? $settings['mk_instance_id']
			: $type;

		// A section hides itself when it has no content, which in the editor
		// leaves an empty box the admin cannot select or delete. Say so
		// instead, but only in the editor — never on the public page.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			ob_start();
			require $file;
			$html = (string) ob_get_clean();

			if ( '' === trim( $html ) ) {
				printf(
					'<div class="mk-elementor-empty" style="padding:2rem;text-align:center;border:1px dashed currentColor;opacity:.6">%s</div>',
					esc_html(
						sprintf(
							/* translators: %s: section name. */
							__( '%s has nothing to show yet. Add content on the Maapkathi screens and it will appear here.', 'maapkathi' ),
							$this->get_title()
						)
					)
				);
				return;
			}

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme partial output, escaped at source.
			return;
		}

		require $file;
	}
}
