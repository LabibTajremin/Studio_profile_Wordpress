<?php
/**
 * Section Builder screen (FR-03).
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Sections\SectionLayout;
use Maapkathi\Core\Sections\SectionRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add, duplicate, reorder, disable and delete the homepage's sections.
 *
 * Saving goes through admin-ajax so the order can be persisted without a
 * page reload; the screen still posts normally if JavaScript is absent, so
 * the builder is never a dead end.
 */
final class SectionBuilderScreen {

	/**
	 * Wire the AJAX endpoint.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_ajax_mk_save_section_layout', array( $this, 'handle_ajax_save' ) );
	}

	/**
	 * Checks capability, handles a non-JS submit, and renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice = '';

		if ( isset( $_POST['mk_layout_nonce'] ) && check_admin_referer( 'mk_save_layout', 'mk_layout_nonce' ) ) {
			// The no-JavaScript path posts the layout as a JSON string from
			// a hidden field; the AJAX path posts a real array.
			$raw = wp_unslash( $_POST['mk_layout'] ?? array() );
			if ( is_string( $raw ) ) {
				$decoded = json_decode( $raw, true );
				$raw     = is_array( $decoded ) ? $decoded : array();
			}

			$this->persist( is_array( $raw ) ? $raw : array() );
			$notice = __( 'Saved.', 'maapkathi' );
		}

		$layout   = SectionLayout::get();
		$registry = SectionRegistry::all();
		$state    = SectionRegistry::get();
		$orphans  = $this->orphaned_menu_items( $layout );

		require MK_PLUGIN_DIR . 'src/Admin/views/section-builder.php';
	}

	/**
	 * Saves a layout posted over AJAX.
	 *
	 * Returns an explicit error rather than a silent failure, because the
	 * screen keeps the admin's unsaved edits on screen and needs to know
	 * whether to warn them (FR-03.8).
	 *
	 * @return void
	 */
	public function handle_ajax_save(): void {
		if ( ! check_ajax_referer( 'mk_save_layout', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'This page has expired. Reload and try again — your changes are still here.', 'maapkathi' ) ), 403 );
		}

		if ( ! current_user_can( Roles::CAP_MANAGE_SETTINGS ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to change the layout.', 'maapkathi' ) ), 403 );
		}

		$raw = wp_unslash( $_POST['layout'] ?? array() );
		if ( ! is_array( $raw ) ) {
			wp_send_json_error( array( 'message' => __( 'The layout could not be read. Nothing has been changed.', 'maapkathi' ) ), 400 );
		}

		$layout = $this->persist( $raw );

		wp_send_json_success(
			array(
				'message' => __( 'Layout saved.', 'maapkathi' ),
				'count'   => count( $layout ),
			)
		);
	}

	/**
	 * Validates and stores a layout, and clears out any per-section records
	 * belonging to sections that no longer exist.
	 *
	 * @param array<int|string,mixed> $raw Raw layout rows.
	 * @return array<int,array{id:string,type:string,enabled:bool}>
	 */
	private function persist( array $raw ): array {
		$layout = SectionLayout::sanitize( $raw );

		update_option( SectionLayout::OPTION, $layout );

		// A deleted section's title, subtitle and anchor go with it —
		// otherwise the options table accumulates rows for sections nobody
		// can see or edit any more (FR-03 AC-4).
		$live    = array_column( $layout, 'id' );
		$stored  = get_option( SectionRegistry::OPTION, array() );
		$stored  = is_array( $stored ) ? $stored : array();
		$types   = SectionRegistry::all();
		$cleaned = array();

		foreach ( $stored as $id => $row ) {
			$is_inner_page = isset( $types[ $id ] ) && 'home' !== $types[ $id ]['page'];

			if ( in_array( $id, $live, true ) || $is_inner_page ) {
				$cleaned[ $id ] = $row;
			}
		}

		if ( $cleaned !== $stored ) {
			update_option( SectionRegistry::OPTION, $cleaned );
		}

		return $layout;
	}

	/**
	 * Menu items pointing at a section that is no longer on the page.
	 *
	 * Deleting a section must not break the site: the link simply resolves
	 * to the top of the page, and the admin is told which items are now
	 * pointing nowhere so they can tidy up (FR-03.7).
	 *
	 * @param array<int,array{id:string,type:string,enabled:bool}> $layout Current layout.
	 * @return string[] Menu item titles.
	 */
	private function orphaned_menu_items( array $layout ): array {
		$anchors = array();
		$state   = SectionRegistry::get();

		foreach ( $layout as $row ) {
			if ( isset( $state[ $row['id'] ] ) ) {
				$anchors[] = $state[ $row['id'] ]['anchor'];
			}
		}

		$orphans = array();

		foreach ( wp_get_nav_menus() as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				$fragment = (string) wp_parse_url( (string) $item->url, PHP_URL_FRAGMENT );

				if ( '' === $fragment || in_array( $fragment, $anchors, true ) ) {
					continue;
				}

				$orphans[] = $item->title;
			}
		}

		return array_values( array_unique( $orphans ) );
	}
}
