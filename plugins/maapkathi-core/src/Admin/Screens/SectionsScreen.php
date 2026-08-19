<?php
/**
 * Sections screen: per-section title, subtitle, visibility and anchor
 * (FR-02).
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Sections\SectionRegistry;
use Maapkathi\Core\Support\SiteText;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One screen for every section's heading and chrome.
 *
 * The heading itself still lives in Site Text — this screen writes to the
 * same field rather than introducing a second place where a title could be
 * set, which would leave the admin guessing which one wins.
 */
final class SectionsScreen {

	/**
	 * Checks capability, saves the form on submit, and renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice       = '';
		$field_errors = array();

		if ( isset( $_POST['mk_sections_nonce'] ) && check_admin_referer( 'mk_save_sections', 'mk_sections_nonce' ) ) {
			$raw       = wp_unslash( $_POST['mk_sections'] ?? array() );
			$sanitized = SectionRegistry::sanitize( is_array( $raw ) ? $raw : array() );

			update_option( SectionRegistry::OPTION, $sanitized['values'] );
			$field_errors = $sanitized['errors'];

			$this->save_titles( is_array( $raw ) ? $raw : array() );

			$notice = $field_errors
				? __( 'Saved, but some anchors were already in use — see the errors below.', 'maapkathi' )
				: __( 'Saved.', 'maapkathi' );
		}

		$sections = SectionRegistry::all();
		$state    = SectionRegistry::get();
		$text     = SiteText::get();

		require MK_PLUGIN_DIR . 'src/Admin/views/sections.php';
	}

	/**
	 * Writes the posted headings back into Site Text.
	 *
	 * Only keys that actually belong to a section are touched, so this can
	 * never clobber the copy fields the Site Text screen owns.
	 *
	 * @param array<string,mixed> $raw Raw posted payload keyed by section id.
	 * @return void
	 */
	private function save_titles( array $raw ): void {
		$stored = get_option( SiteText::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();

		foreach ( SectionRegistry::all() as $id => $section ) {
			if ( ! isset( $raw[ $id ]['title'] ) ) {
				continue;
			}

			$title = sanitize_text_field( (string) $raw[ $id ]['title'] );

			// An emptied field is stored empty rather than removed, and
			// SiteText::get() falls back to the shipped default when
			// rendering — so the front end never shows a blank heading and
			// the admin still sees their field as they left it.
			$stored[ $section['text_key'] ] = $title;
		}

		update_option( SiteText::OPTION, $stored );
	}
}
