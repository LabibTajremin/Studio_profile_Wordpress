<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Cli;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp maapkathi seed` (§13/Phase 13). Idempotent: guarded on the
 * `mk_seed_installed` option marker exactly like the source app's seeder.
 * Creates presentable demo content entirely within WordPress — no
 * PostgreSQL/Neon dependency, no legacy migration.
 */
final class SeedCommand {

	/**
	 * Seed demo content. Safe to run multiple times.
	 *
	 * ## EXAMPLES
	 *
	 *     wp maapkathi seed
	 *
	 * @when after_wp_load
	 */
	public function __invoke(): void {
		if ( get_option( 'mk_seed_installed' ) ) {
			\WP_CLI::log( 'Demo content already seeded — skipping (idempotent).' );
			return;
		}

		$this->seed_site_settings();
		$this->seed_theme_settings();
		$this->seed_categories_and_projects();
		$this->seed_services();
		$this->seed_team();
		$this->seed_testimonials();
		$this->seed_clients();
		$this->seed_awards();
		$this->seed_stats();
		$this->seed_values();
		$this->seed_faqs();
		$this->seed_hero_slides();

		update_option( 'mk_seed_installed', time() );

		\WP_CLI::success( 'Demo content seeded.' );
	}

	private function placeholder( string $label, int $w = 1600, int $h = 1000 ): string {
		return sprintf( 'https://placehold.co/%dx%d?text=%s', $w, $h, rawurlencode( $label ) );
	}

	private function seed_site_settings(): void {
		update_option(
			'mk_site_settings',
			array(
				'studio_name'                   => 'Maapkathi Studio',
				'tagline'                        => 'Interior and architectural design, considered.',
				'contact_email'                  => 'hello@maapkathi.example',
				'contact_phone'                  => '+1 555 010 2020',
				'address'                        => '12 Riverside Lane, Dhaka',
				'blog_enabled'                    => true,
				'editor_verification_required'   => true,
				'show_admin_shield'               => false,
				'vision_mission_enabled'          => true,
				'vision_text'                     => 'A studio where every space tells a considered story.',
				'mission_text'                    => 'We design environments that outlast trend cycles.',
				'faqs'                            => array(
					array( 'question' => 'What is your typical project timeline?', 'answer' => 'Most residential projects run 8-14 weeks from concept to handover.' ),
					array( 'question' => 'Do you work outside the city?', 'answer' => 'Yes — we take on select projects nationwide.' ),
					array( 'question' => 'Can I see a portfolio in person?', 'answer' => 'Absolutely, our studio is open for consultations by appointment.' ),
				),
			)
		);
	}

	private function seed_theme_settings(): void {
		update_option( \Maapkathi\Core\Theme\ThemeSettings::OPTION, \Maapkathi\Core\Theme\ThemeSettings::defaults() );
	}

	private function seed_categories_and_projects(): void {
		$categories = array( 'Residential', 'Commercial', 'Hospitality' );
		$term_ids   = array();

		foreach ( $categories as $i => $name ) {
			$term = term_exists( $name, 'mk_project_category' );
			$term_ids[] = $term ? (int) $term['term_id'] : (int) wp_insert_term( $name, 'mk_project_category' )['term_id'];
		}

		for ( $i = 1; $i <= 6; $i++ ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'mk_project',
					'post_title'   => "Demo Project {$i}",
					'post_content' => 'A considered renovation balancing light, material, and restraint.',
					'post_status'  => 'publish',
				)
			);
			wp_set_post_terms( $post_id, array( $term_ids[ $i % count( $term_ids ) ] ), 'mk_project_category' );
			update_post_meta( $post_id, 'mk_summary', 'A short, considered summary of demo project ' . $i . '.' );
			update_post_meta( $post_id, 'mk_is_featured', $i <= 3 ? 1 : 0 );
			update_post_meta( $post_id, 'mk_sort_order', $i );
			update_post_meta( $post_id, 'mk_location', 'Dhaka' );
			update_post_meta( $post_id, 'mk_client_name', 'Private Client' );
			update_post_meta( $post_id, 'mk_area_sqft', 1200 + $i * 100 );
			update_post_meta( $post_id, 'mk_completed_at', gmdate( 'Y-m-d' ) );
		}
	}

	private function seed_services(): void {
		$parents = array( 'Interior Design', 'Architecture' );
		foreach ( $parents as $name ) {
			$parent_id = wp_insert_post(
				array(
					'post_type'    => 'mk_service',
					'post_title'   => $name,
					'post_content' => "Full-scope {$name} services from concept to delivery.",
					'post_status'  => 'publish',
				)
			);
			$child_id = wp_insert_post(
				array(
					'post_type'    => 'mk_service',
					'post_title'   => $name . ' Consultation',
					'post_content' => 'A focused engagement for early-stage guidance.',
					'post_status'  => 'publish',
					'post_parent'  => $parent_id,
				)
			);
			update_post_meta( $parent_id, 'mk_icon', 'dashicons-admin-home' );
			update_post_meta( $child_id, 'mk_icon', 'dashicons-lightbulb' );
		}
	}

	private function seed_team(): void {
		$members = array(
			array( 'Amara Khan', 'Principal Designer' ),
			array( 'Rafi Hossain', 'Lead Architect' ),
			array( 'Priya Das', 'Interiors Lead' ),
			array( 'Sam Chowdhury', 'Studio Manager' ),
		);
		foreach ( $members as $i => [ $name, $role ] ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'mk_member',
					'post_title'  => $name,
					'post_status' => 'publish',
				)
			);
			update_post_meta( $id, 'mk_role_title', $role );
			update_post_meta( $id, 'mk_bio', "{$name} brings a considered, material-first approach to every project." );
			update_post_meta( $id, 'mk_sort_order', $i );
		}
	}

	private function seed_testimonials(): void {
		$quotes = array(
			array( 'Nadia R.', 'Homeowner', 'The team understood exactly what we wanted before we did.' ),
			array( 'Omar F.', 'Restaurant Owner', 'Professional, on time, and the space transformed our business.' ),
			array( 'Lina S.', 'Homeowner', 'Every detail was considered. Would work with them again.' ),
		);
		foreach ( $quotes as $i => [ $author, $role, $quote ] ) {
			$id = wp_insert_post( array( 'post_type' => 'mk_testimonial', 'post_title' => $author, 'post_status' => 'publish' ) );
			update_post_meta( $id, 'mk_author_name', $author );
			update_post_meta( $id, 'mk_author_role', $role );
			update_post_meta( $id, 'mk_quote', $quote );
			update_post_meta( $id, 'mk_rating', 5 );
			update_post_meta( $id, 'mk_sort_order', $i );
		}
	}

	private function seed_clients(): void {
		for ( $i = 1; $i <= 6; $i++ ) {
			$id = wp_insert_post( array( 'post_type' => 'mk_client', 'post_title' => "Client {$i}", 'post_status' => 'publish' ) );
			update_post_meta( $id, 'mk_sort_order', $i );
			update_post_meta( $id, 'mk_is_featured', $i <= 3 ? 1 : 0 );
		}
	}

	private function seed_awards(): void {
		for ( $i = 1; $i <= 3; $i++ ) {
			$id = wp_insert_post( array( 'post_type' => 'mk_award', 'post_title' => "Design Award {$i}", 'post_status' => 'publish' ) );
			update_post_meta( $id, 'mk_issuer', 'Regional Design Council' );
			update_post_meta( $id, 'mk_year', (int) gmdate( 'Y' ) - $i );
			update_post_meta( $id, 'mk_sort_order', $i );
		}
	}

	private function seed_stats(): void {
		$stats = array(
			array( 'Projects completed', 120, '+' ),
			array( 'Years of practice', 12, '' ),
			array( 'Cities served', 8, '' ),
			array( 'Client satisfaction', 98, '%' ),
		);
		foreach ( $stats as $i => [ $label, $value, $suffix ] ) {
			$id = wp_insert_post( array( 'post_type' => 'mk_stat', 'post_title' => $label, 'post_status' => 'publish' ) );
			update_post_meta( $id, 'mk_value_number', $value );
			update_post_meta( $id, 'mk_suffix', $suffix );
			update_post_meta( $id, 'mk_sort_order', $i );
		}
	}

	private function seed_values(): void {
		$values = array(
			array( 'Restraint', 'We design for longevity, not trend cycles.' ),
			array( 'Craft', 'Every material choice is deliberate.' ),
			array( 'Transparency', 'Clients see the process, not just the result.' ),
			array( 'Collaboration', 'The best spaces are built with, not for.' ),
		);
		foreach ( $values as $i => [ $title, $body ] ) {
			$id = wp_insert_post( array( 'post_type' => 'mk_value', 'post_title' => $title, 'post_content' => $body, 'post_status' => 'publish' ) );
			update_post_meta( $id, 'mk_sort_order', $i );
		}
	}

	private function seed_faqs(): void {
		// Homepage FAQ content is seeded directly on mk_site_settings.faqs
		// (see seed_site_settings) since the JSON-LD/accordion render from
		// that option, matching the source app's data shape.
	}

	private function seed_hero_slides(): void {
		$site_settings = get_option( 'mk_site_settings', array() );
		$site_settings['hero_slide_duration'] = 6;
		$site_settings['hero_slides']         = array(
			array(
				'media_kind' => 'image',
				'image_url'  => $this->placeholder( 'Hero+Still' ),
				'eyebrow'    => 'Maapkathi Studio',
				'headline'   => 'Spaces, considered.',
				'body'       => 'Interior and architectural design for people who live in the details.',
				'cta_label'  => 'View our work',
				'cta_href'   => home_url( '/work/' ),
			),
			array(
				'media_kind'          => 'gif',
				'gif_url'             => $this->placeholder( 'Hero+GIF' ),
				'gif_first_frame_url' => $this->placeholder( 'Hero+GIF+Frame' ),
				'headline'            => 'Texture in motion.',
			),
			array(
				'media_kind'        => 'video_upload',
				'video_source'      => 'upload',
				'video_upload_url'  => '', // Left empty: no bundled demo MP4 in this environment; upload one via Hero screen to exercise this path.
				'video_poster'      => $this->placeholder( 'Hero+Video+Poster' ),
				'headline'          => 'Craft in every frame.',
			),
			array(
				'media_kind'   => 'video_link',
				'video_source' => 'link',
				'video_url'    => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
				'video_poster' => $this->placeholder( 'Hero+External+Poster' ),
				'headline'     => 'Every angle, intentional.',
			),
		);
		update_option( 'mk_site_settings', $site_settings );
	}
}
