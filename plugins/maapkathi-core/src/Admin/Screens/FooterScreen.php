<?php
/**
 * Footer settings screen (FR-08, FR-09).
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Footer\FooterSettings;
use Maapkathi\Core\Roles\Roles;
use Maapkathi\Core\Theme\HexColor;
use Maapkathi\Core\Theme\ThemeSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Everything the footer renders: which layout, its colours, the logo, and
 * the four columns' contents.
 */
final class FooterScreen {

	/**
	 * Checks capability, saves the form on submit, and renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Roles::CAP_MANAGE_APPEARANCE ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice       = '';
		$field_errors = array();

		if ( isset( $_POST['mk_footer_nonce'] ) && check_admin_referer( 'mk_save_footer', 'mk_footer_nonce' ) ) {
			$raw = wp_unslash( $_POST['mk_footer'] ?? array() );
			$raw = is_array( $raw ) ? $raw : array();

			// Same rule as the header hex: an unparseable value is reported
			// and the previous one kept, rather than silently nulled.
			$posted_hex = isset( $raw['bg_hex'] ) ? trim( (string) $raw['bg_hex'] ) : '';
			if ( '' !== $posted_hex && ! HexColor::is_valid( $posted_hex ) ) {
				$current                = FooterSettings::get();
				$raw['bg_hex']          = $current['bg_hex'];
				$field_errors['bg_hex'] = sprintf(
					/* translators: %s: the rejected value the admin typed. */
					__( '"%s" is not a colour. Use #rgb or #rrggbb — the previous value has been kept.', 'maapkathi' ),
					$posted_hex
				);
			}

			update_option( FooterSettings::OPTION, FooterSettings::sanitize( $raw ) );

			// The footer colours are baked into the cached CSS variable
			// block, so that cache has to go when they change.
			ThemeSettings::flush_cache();

			$notice = $field_errors ? __( 'Saved, but one field was rejected — see the error below.', 'maapkathi' ) : __( 'Saved.', 'maapkathi' );
		}

		$footer = FooterSettings::get();

		require MK_PLUGIN_DIR . 'src/Admin/views/footer.php';
	}
}
