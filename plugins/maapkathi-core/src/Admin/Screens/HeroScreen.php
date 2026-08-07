<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Admin\Screens;

use Maapkathi\Core\Roles\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hero carousel slide manager (§3.3 #11, §6.3). Each slide takes exactly one
 * media kind — image, GIF, uploaded video, or video link — with the
 * length-warning helper text and per-slide hold toggle. Up to 8 slides.
 */
final class HeroScreen {

	private const MAX_SLIDES = 8;

	public function render(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'maapkathi' ) );
		}

		$notice = '';
		if ( isset( $_POST['mk_hero_nonce'] ) && check_admin_referer( 'mk_save_hero', 'mk_hero_nonce' ) ) {
			$this->save();
			$notice = __( 'Saved.', 'maapkathi' );
		}

		$site_settings = get_option( 'mk_site_settings', array() );
		$slides        = $site_settings['hero_slides'] ?? array();
		$duration      = (int) ( $site_settings['hero_slide_duration'] ?? MK_HERO_SLIDE_SECONDS );

		require MK_PLUGIN_DIR . 'src/Admin/views/hero.php';
	}

	private function save(): void {
		if ( ! current_user_can( Roles::CAP_EDIT_CONTENT ) ) {
			return;
		}

		$site_settings = get_option( 'mk_site_settings', array() );

		$duration                             = absint( $_POST['hero_slide_duration'] ?? MK_HERO_SLIDE_SECONDS );
		$site_settings['hero_slide_duration'] = max( 3, min( 20, $duration ) );

		$raw_slides = wp_unslash( $_POST['slides'] ?? array() );
		$slides     = array();

		foreach ( (array) $raw_slides as $raw ) {
			if ( empty( $raw['headline'] ) && empty( $raw['image_url'] ) && empty( $raw['video_url'] ) && empty( $raw['video_upload_url'] ) && empty( $raw['gif_url'] ) ) {
				continue; // Skip empty rows.
			}

			$kind = in_array( $raw['media_kind'] ?? '', array( 'image', 'gif', 'video_upload', 'video_link' ), true ) ? $raw['media_kind'] : 'image';

			$slide = array(
				'media_kind'            => $kind,
				'eyebrow'               => sanitize_text_field( $raw['eyebrow'] ?? '' ),
				'headline'              => sanitize_text_field( $raw['headline'] ?? '' ),
				'body'                  => sanitize_textarea_field( $raw['body'] ?? '' ),
				'cta_label'             => sanitize_text_field( $raw['cta_label'] ?? '' ),
				'cta_href'              => esc_url_raw( $raw['cta_href'] ?? '' ),
				'hold_until_video_ends' => ! empty( $raw['hold_until_video_ends'] ),
				'is_active'             => ! empty( $raw['is_active'] ),
			);

			if ( 'image' === $kind ) {
				$slide['image_url'] = esc_url_raw( $raw['image_url'] ?? '' );
			} elseif ( 'gif' === $kind ) {
				$slide['gif_url']             = esc_url_raw( $raw['gif_url'] ?? '' );
				$slide['gif_first_frame_url'] = esc_url_raw( $raw['gif_first_frame_url'] ?? '' );
			} elseif ( 'video_upload' === $kind ) {
				$slide['video_source']     = 'upload';
				$slide['video_upload_url'] = esc_url_raw( $raw['video_upload_url'] ?? '' );
				$slide['video_poster']     = esc_url_raw( $raw['video_poster'] ?? '' );
			} else {
				$link_error = ( new \Maapkathi\Core\Video\VideoResolver() )->validate_link( $raw['video_url'] ?? '' );
				if ( null !== $link_error ) {
					add_settings_error( 'mk_hero', 'mk_hero_video_url', sprintf( '%s: %s', __( 'Hero video link rejected', 'maapkathi' ), $link_error ) );
					continue;
				}
				$slide['video_source'] = 'link';
				$slide['video_url']    = esc_url_raw( $raw['video_url'] ?? '' );
				$slide['video_poster'] = esc_url_raw( $raw['video_poster'] ?? '' );
			}

			$slides[] = $slide;
		}

		$site_settings['hero_slides'] = array_slice( $slides, 0, self::MAX_SLIDES );

		update_option( 'mk_site_settings', $site_settings );
	}
}
