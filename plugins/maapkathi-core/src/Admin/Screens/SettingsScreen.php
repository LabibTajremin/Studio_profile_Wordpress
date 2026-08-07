<?php
/**
 * Settings screen (§3.3 #16) controller.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings screen (§3.3 #16): studio name, tagline, contact, socials, SEO,
 * logos, favicon, blog toggle, vision/mission, verification toggle,
 * admin-shield toggle.
 */
final class SettingsScreen {

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
		if ( isset( $_POST['mk_settings_nonce'] ) && check_admin_referer( 'mk_save_settings', 'mk_settings_nonce' ) ) {
			$this->save();
			$notice = __( 'Saved.', 'maapkathi' );
		}

		$settings = get_option( 'mk_site_settings', array() );
		$seo      = get_option( 'mk_seo_settings', array() );

		require MK_PLUGIN_DIR . 'src/Admin/views/settings.php';
	}

	/**
	 * Sanitizes and persists the posted site-settings and SEO-settings fields.
	 *
	 * Called only from render(), which has already verified the
	 * mk_save_settings nonce before invoking this method.
	 *
	 * @return void
	 */
	private function save(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce already verified in render() before this method is called.
		$settings = get_option( 'mk_site_settings', array() );

		$settings['studio_name']     = sanitize_text_field( wp_unslash( $_POST['studio_name'] ?? '' ) );
		$settings['tagline']         = sanitize_text_field( wp_unslash( $_POST['tagline'] ?? '' ) );
		$settings['contact_email']   = sanitize_email( wp_unslash( $_POST['contact_email'] ?? '' ) );
		$settings['contact_phone']   = sanitize_text_field( wp_unslash( $_POST['contact_phone'] ?? '' ) );
		$settings['whatsapp']        = sanitize_text_field( wp_unslash( $_POST['whatsapp'] ?? '' ) );
		$settings['address']         = sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) );
		$settings['logo_show_title'] = ! empty( $_POST['logo_show_title'] );

		// Branding images are stored as attachment IDs (0/'' clears them),
		// which Branding::attachment_url() resolves at render time.
		foreach ( array( 'logo_light', 'logo_dark', 'favicon' ) as $image_key ) {
			$settings[ $image_key ] = absint( $_POST[ $image_key ] ?? 0 );
		}
		$settings['blog_enabled']                 = ! empty( $_POST['blog_enabled'] );
		$settings['vision_mission_enabled']       = ! empty( $_POST['vision_mission_enabled'] );
		$settings['vision_text']                  = sanitize_textarea_field( wp_unslash( $_POST['vision_text'] ?? '' ) );
		$settings['mission_text']                 = sanitize_textarea_field( wp_unslash( $_POST['mission_text'] ?? '' ) );
		$settings['editor_verification_required'] = ! empty( $_POST['editor_verification_required'] );
		$settings['show_admin_shield']            = ! empty( $_POST['show_admin_shield'] );

		$socials = array();
		foreach ( array( 'facebook', 'instagram', 'linkedin', 'twitter', 'pinterest' ) as $platform ) {
			$url = esc_url_raw( wp_unslash( $_POST[ 'social_' . $platform ] ?? '' ) );
			if ( $url ) {
				$socials[ $platform ] = $url;
			}
		}
		$settings['socials'] = $socials;

		$settings['media_ratios'] = array(
			'heroImage'      => '16:9',
			'heroVideo'      => '16:9',
			'projectCover'   => '4:5',
			'projectGallery' => '4:5',
			'memberPhoto'    => '3:4',
			'ogImage'        => '1.91:1',
		);

		update_option( 'mk_site_settings', $settings );

		$seo = array(
			'default_title'       => sanitize_text_field( wp_unslash( $_POST['seo_default_title'] ?? '' ) ),
			'default_description' => sanitize_textarea_field( wp_unslash( $_POST['seo_default_description'] ?? '' ) ),
			'og_image'            => esc_url_raw( wp_unslash( $_POST['seo_og_image'] ?? '' ) ),
			'ga_id'               => sanitize_text_field( wp_unslash( $_POST['seo_ga_id'] ?? '' ) ),
			'gtm_id'              => sanitize_text_field( wp_unslash( $_POST['seo_gtm_id'] ?? '' ) ),
			'meta_pixel_id'       => sanitize_text_field( wp_unslash( $_POST['seo_meta_pixel_id'] ?? '' ) ),
		);
		update_option( 'mk_seo_settings', $seo );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
