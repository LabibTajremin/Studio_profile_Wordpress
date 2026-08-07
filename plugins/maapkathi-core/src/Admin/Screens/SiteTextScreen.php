<?php
/**
 * Site Text screen controller.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Support\SiteText;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site Text screen (§3.3 #15): all 29 editable copy strings, grouped by
 * page/section.
 */
final class SiteTextScreen {

	/**
	 * Checks capability, saves the form on submit, and renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice = '';
		if ( isset( $_POST['mk_site_text_nonce'] ) && check_admin_referer( 'mk_save_site_text', 'mk_site_text_nonce' ) ) {
			$values = array();
			foreach ( SiteText::keys() as $key ) {
				$values[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			}
			update_option( SiteText::OPTION, $values );
			$notice = __( 'Saved.', 'maapkathi' );
		}

		$values = SiteText::get();

		require MK_PLUGIN_DIR . 'src/Admin/views/site-text.php';
	}
}
