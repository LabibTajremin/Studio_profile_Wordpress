<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Cli;

use Maapkathi\Core\Theme\ThemeSettings;
use Maapkathi\Core\Support\SiteText;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp maapkathi seed` (Phase 13).
 *
 * Creates a complete, presentable demo site from scratch, entirely within
 * WordPress — no PostgreSQL/Neon dependency, no legacy migration. Demo
 * imagery is generated locally by DemoAssets and imported through the
 * Media Library, so a fresh install looks finished with zero outbound
 * network access.
 *
 * Idempotent: guarded on the mk_seed_installed marker, and every created
 * object is looked up by slug before being created, so running it twice
 * changes nothing.
 */
final class SeedCommand {

	/**
	 * Seed demo content. Safe to run repeatedly.
	 *
	 * ## OPTIONS
	 *
	 * [--fresh]
	 * : Delete previously seeded demo content and generated images first,
	 *   then reseed from scratch.
	 *
	 * ## EXAMPLES
	 *
	 *     wp maapkathi seed
	 *     wp maapkathi seed --fresh
	 *
	 * @when after_wp_load
	 *
	 * @param array<int,string>    $args       Positional args.
	 * @param array<string,string> $assoc_args Flags.
	 */
	public function __invoke( array $args = array(), array $assoc_args = array() ): void {
		$fresh = isset( $assoc_args['fresh'] );

		if ( $fresh ) {
			$this->tear_down();
			\WP_CLI::log( 'Existing demo content removed.' );
		} elseif ( get_option( 'mk_seed_installed' ) ) {
			\WP_CLI::log( 'Demo content already seeded — nothing to do (use --fresh to rebuild).' );
			return;
		}

		if ( ! DemoAssets::available() ) {
			\WP_CLI::warning( 'PHP GD is unavailable, so demo images cannot be generated. Content will be seeded without imagery.' );
		}

		$this->seed_site_settings();
		$this->seed_site_text();
		$this->seed_theme_settings();
		$this->seed_branding();
		$this->seed_categories_and_projects();
		$this->seed_services();
		$this->seed_team();
		$this->seed_testimonials();
		$this->seed_clients();
		$this->seed_awards();
		$this->seed_stats();
		$this->seed_values();
		$this->seed_process_steps();
		$this->seed_faqs();
		$this->seed_hero_slides();
		$this->ensure_pages();

		update_option( 'mk_seed_installed', time() );
		flush_rewrite_rules();

		\WP_CLI::success( 'Demo content seeded. Visit the site to see it.' );
	}

	// ---------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------

	/**
	 * Finds an existing post by slug+type, or creates it. This is what
	 * makes the whole seeder idempotent.
	 *
	 * @param array<string,mixed> $data
	 */
	private function upsert( string $post_type, string $slug, array $data ): int {
		$existing = get_posts(
			array(
				'post_type'      => $post_type,
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( $existing ) {
			return (int) $existing[0];
		}

		$id = wp_insert_post(
			array_merge(
				array(
					'post_type'   => $post_type,
					'post_name'   => $slug,
					'post_status' => 'publish',
				),
				$data
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			\WP_CLI::warning( sprintf( 'Could not create %s "%s": %s', $post_type, $slug, $id->get_error_message() ) );
			return 0;
		}

		update_post_meta( $id, '_mk_demo_content', 1 );

		return (int) $id;
	}

	/** @param array<string,mixed> $meta */
	private function set_meta( int $post_id, array $meta ): void {
		if ( ! $post_id ) {
			return;
		}
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	private function attach_cover( int $post_id, int $attachment_id ): void {
		if ( $post_id && $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}
	}

	private function tear_down(): void {
		foreach ( array( 'mk_project', 'mk_service', 'mk_member', 'mk_testimonial', 'mk_client', 'mk_award', 'mk_faq', 'mk_value', 'mk_stat', 'mk_process_step' ) as $type ) {
			$ids = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_key'       => '_mk_demo_content',
					'no_found_rows'  => true,
				)
			);
			foreach ( $ids as $id ) {
				wp_delete_post( (int) $id, true );
			}
		}

		DemoAssets::delete_all();
		delete_option( 'mk_seed_installed' );
	}

	// ---------------------------------------------------------------
	// Seeders
	// ---------------------------------------------------------------

	private function seed_site_settings(): void {
		$existing = get_option( 'mk_site_settings', array() );
		$existing = is_array( $existing ) ? $existing : array();

		update_option(
			'mk_site_settings',
			array_merge(
				array(
					'studio_name'                  => 'Maapkathi Studio',
					'tagline'                      => 'Interior and architectural design, considered.',
					'contact_email'                => 'hello@maapkathi.example',
					'contact_phone'                => '+880 1700 000000',
					'whatsapp'                     => '+880 1700 000000',
					'address'                      => "12 Riverside Lane\nGulshan, Dhaka 1212",
					'socials'                      => array(
						'instagram' => 'https://instagram.com/maapkathi',
						'linkedin'  => 'https://linkedin.com/company/maapkathi',
					),
					'logo_show_title'              => true,
					'blog_enabled'                 => true,
					'editor_verification_required' => true,
					'show_admin_shield'            => false,
					'vision_mission_enabled'       => true,
					'vision_text'                  => 'A studio where every space tells a considered story — quietly, and for a long time.',
					'mission_text'                 => 'To design environments that outlast trend cycles, built on restraint, craft, and honest materials.',
					'media_ratios'                 => array(
						'heroImage'      => '16:9',
						'heroVideo'      => '16:9',
						'projectCover'   => '4:5',
						'projectGallery' => '4:5',
						'memberPhoto'    => '3:4',
						'ogImage'        => '1.91:1',
					),
				),
				$existing
			)
		);
	}

	private function seed_site_text(): void {
		$existing = get_option( SiteText::OPTION, array() );
		if ( empty( $existing ) ) {
			update_option( SiteText::OPTION, SiteText::defaults() );
		}
	}

	private function seed_theme_settings(): void {
		if ( ! get_option( ThemeSettings::OPTION ) ) {
			update_option( ThemeSettings::OPTION, ThemeSettings::defaults() );
		}
	}

	private function seed_branding(): void {
		$settings = get_option( 'mk_site_settings', array() );

		$light = DemoAssets::logo( 'mk-demo-logo-light', 'Maapkathi', 0 );
		$dark  = DemoAssets::logo( 'mk-demo-logo-dark', 'Maapkathi', 3 );

		if ( $light ) {
			$settings['logo_light'] = $light;
		}
		if ( $dark ) {
			$settings['logo_dark'] = $dark;
		}

		$favicon = DemoAssets::logo( 'mk-demo-favicon', 'M', 0 );
		if ( $favicon ) {
			$settings['favicon'] = $favicon;
		}

		update_option( 'mk_site_settings', $settings );
	}

	private function seed_categories_and_projects(): void {
		$categories = array(
			'residential' => 'Residential',
			'commercial'  => 'Commercial',
			'hospitality' => 'Hospitality',
		);

		$term_ids = array();
		foreach ( $categories as $slug => $name ) {
			$term = term_exists( $slug, 'mk_project_category' );
			if ( ! $term ) {
				$term = wp_insert_term( $name, 'mk_project_category', array( 'slug' => $slug ) );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}

		$projects = array(
			array( 'riverside-residence', 'Riverside Residence', 'A calm family home organised around light and water views.', 'Dhaka', 'Private Client', 3200 ),
			array( 'atelier-loft', 'Atelier Loft', 'A working loft for a ceramicist, built around a north-facing studio wall.', 'Chattogram', 'Private Client', 1850 ),
			array( 'gulshan-townhouse', 'Gulshan Townhouse', 'A vertical townhouse that trades corridors for a stacked courtyard.', 'Dhaka', 'Private Client', 4100 ),
			array( 'harbour-cafe', 'Harbour Café', 'A 60-cover café with a terrazzo counter and reclaimed teak seating.', 'Cox\'s Bazar', 'Harbour Group', 1400 ),
			array( 'monsoon-retreat', 'Monsoon Retreat', 'A hillside guesthouse designed to be beautiful in heavy rain.', 'Sylhet', 'Monsoon Hospitality', 5600 ),
			array( 'quarry-offices', 'Quarry Offices', 'A workplace carved into an old stone yard, keeping the original walls.', 'Dhaka', 'Quarry Ltd', 7800 ),
		);

		foreach ( $projects as $i => list( $slug, $title, $summary, $location, $client, $area ) ) {
			$id = $this->upsert(
				'mk_project',
				$slug,
				array(
					'post_title'   => $title,
					'post_content' => $summary . "\n\nThe brief asked for durability without heaviness. We worked with a restrained material palette — lime plaster, oak, and blackened steel — and let the daylight do the decorating.",
					'post_excerpt' => $summary,
					'menu_order'   => $i,
				)
			);

			if ( ! $id ) {
				continue;
			}

			wp_set_post_terms( $id, array( $term_ids[ $i % max( 1, count( $term_ids ) ) ] ), 'mk_project_category' );

			$cover = DemoAssets::image( 'mk-demo-project-' . $slug, $title, 1200, 1500, $i );
			$this->attach_cover( $id, $cover );

			$gallery = array();
			for ( $g = 1; $g <= 3; $g++ ) {
				$shot = DemoAssets::image( 'mk-demo-project-' . $slug . '-' . $g, $title . ' 0' . $g, 1200, 1500, $i + $g );
				if ( $shot ) {
					$gallery[] = $shot;
				}
			}

			$this->set_meta(
				$id,
				array(
					'mk_summary'      => $summary,
					'mk_is_featured'  => $i < 3 ? 1 : 0,
					'mk_sort_order'   => $i,
					'mk_location'     => $location,
					'mk_client_name'  => $client,
					'mk_area_sqft'    => $area,
					'mk_completed_at' => gmdate( 'Y-m-d', strtotime( '-' . ( $i * 4 + 2 ) . ' months' ) ),
					'mk_gallery'      => implode( ',', $gallery ),
				)
			);
		}
	}

	private function seed_services(): void {
		$groups = array(
			array(
				'slug'     => 'interior-design',
				'title'    => 'Interior Design',
				'body'     => 'Full-scope interiors from concept through to styling and handover.',
				'icon'     => 'dashicons-admin-home',
				'children' => array(
					array( 'residential-interiors', 'Residential Interiors', 'Homes designed around how you actually live.' ),
					array( 'workplace-interiors', 'Workplace Interiors', 'Offices and studios that people are glad to arrive at.' ),
				),
			),
			array(
				'slug'     => 'architecture',
				'title'    => 'Architecture',
				'body'     => 'New builds, extensions, and adaptive reuse, from feasibility to completion.',
				'icon'     => 'dashicons-building',
				'children' => array(
					array( 'new-build', 'New Build', 'Ground-up projects with a long view on material and climate.' ),
					array( 'adaptive-reuse', 'Adaptive Reuse', 'Giving good bones a second, better life.' ),
				),
			),
		);

		foreach ( $groups as $i => $group ) {
			$parent_id = $this->upsert(
				'mk_service',
				$group['slug'],
				array(
					'post_title'   => $group['title'],
					'post_content' => $group['body'],
					'post_excerpt' => $group['body'],
					'menu_order'   => $i,
				)
			);

			$this->set_meta(
				$parent_id,
				array(
					'mk_icon'       => $group['icon'],
					'mk_sort_order' => $i,
				)
			);
			$this->attach_cover( $parent_id, DemoAssets::image( 'mk-demo-service-' . $group['slug'], $group['title'], 1600, 1000, $i + 2 ) );

			foreach ( $group['children'] as $j => list( $child_slug, $child_title, $child_body ) ) {
				$child_id = $this->upsert(
					'mk_service',
					$child_slug,
					array(
						'post_title'   => $child_title,
						'post_content' => $child_body,
						'post_excerpt' => $child_body,
						'post_parent'  => $parent_id,
						'menu_order'   => $j,
					)
				);

				$this->set_meta(
					$child_id,
					array(
						'mk_icon'       => 'dashicons-yes-alt',
						'mk_sort_order' => $j,
					)
				);
				$this->attach_cover( $child_id, DemoAssets::image( 'mk-demo-service-' . $child_slug, $child_title, 1600, 1000, $i + $j + 3 ) );
			}
		}
	}

	private function seed_team(): void {
		$members = array(
			array( 'amara-khan', 'Amara Khan', 'Principal Designer', 'Amara founded the studio in 2013 after a decade in adaptive reuse.' ),
			array( 'rafi-hossain', 'Rafi Hossain', 'Lead Architect', 'Rafi leads technical delivery and keeps our detailing honest.' ),
			array( 'priya-das', 'Priya Das', 'Interiors Lead', 'Priya runs the material library and every FF&E specification.' ),
			array( 'sam-chowdhury', 'Sam Chowdhury', 'Studio Manager', 'Sam keeps programmes, budgets, and everyone else on track.' ),
		);

		foreach ( $members as $i => list( $slug, $name, $role, $bio ) ) {
			$id = $this->upsert(
				'mk_member',
				$slug,
				array(
					'post_title' => $name,
					'menu_order' => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_role_title'      => $role,
					'mk_bio'             => $bio,
					'mk_sort_order'      => $i,
					'mk_social_linkedin' => 'https://linkedin.com/in/' . $slug,
				)
			);

			$this->attach_cover( $id, DemoAssets::image( 'mk-demo-member-' . $slug, $name, 600, 800, $i + 1 ) );
		}
	}

	private function seed_testimonials(): void {
		$quotes = array(
			array( 'nadia-r', 'Nadia R.', 'Homeowner', 'Riverside Residence', 'They understood what we wanted before we could describe it ourselves.' ),
			array( 'omar-f', 'Omar F.', 'Founder', 'Harbour Group', 'On time, on budget, and the space genuinely changed how the business runs.' ),
			array( 'lina-s', 'Lina S.', 'Homeowner', 'Gulshan Townhouse', 'Every detail was considered. Two years on it still feels new.' ),
		);

		foreach ( $quotes as $i => list( $slug, $author, $role, $company, $quote ) ) {
			$id = $this->upsert(
				'mk_testimonial',
				$slug,
				array(
					'post_title' => $author,
					'menu_order' => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_author_name' => $author,
					'mk_author_role' => $role,
					'mk_company'     => $company,
					'mk_quote'       => $quote,
					'mk_rating'      => 5,
					'mk_sort_order'  => $i,
				)
			);
		}
	}

	private function seed_clients(): void {
		$clients = array( 'Harbour Group', 'Monsoon Hospitality', 'Quarry Ltd', 'Northline', 'Studio Verde', 'Atelier Co' );

		foreach ( $clients as $i => $name ) {
			$slug = sanitize_title( $name );
			$id   = $this->upsert(
				'mk_client',
				$slug,
				array(
					'post_title' => $name,
					'menu_order' => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_website'     => 'https://example.com/' . $slug,
					'mk_is_featured' => $i < 3 ? 1 : 0,
					'mk_sort_order'  => $i,
				)
			);

			$this->attach_cover( $id, DemoAssets::logo( 'mk-demo-client-' . $slug, $name, $i ) );
		}
	}

	private function seed_awards(): void {
		$awards = array(
			array( 'regional-design-award', 'Regional Design Award', 'Bangladesh Institute of Architects' ),
			array( 'interior-of-the-year', 'Interior of the Year', 'South Asia Design Council' ),
			array( 'adaptive-reuse-commendation', 'Adaptive Reuse Commendation', 'Heritage Trust' ),
		);

		foreach ( $awards as $i => list( $slug, $title, $issuer ) ) {
			$id = $this->upsert(
				'mk_award',
				$slug,
				array(
					'post_title' => $title,
					'menu_order' => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_issuer'     => $issuer,
					'mk_year'       => (int) gmdate( 'Y' ) - $i - 1,
					'mk_sort_order' => $i,
				)
			);
		}
	}

	private function seed_stats(): void {
		$stats = array(
			array( 'projects-completed', 'Projects completed', 120, '+' ),
			array( 'years-of-practice', 'Years of practice', 12, '' ),
			array( 'cities-served', 'Cities served', 8, '' ),
			array( 'repeat-clients', 'Repeat clients', 64, '%' ),
		);

		foreach ( $stats as $i => list( $slug, $label, $value, $suffix ) ) {
			$id = $this->upsert(
				'mk_stat',
				$slug,
				array(
					'post_title' => $label,
					'menu_order' => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_value_number' => $value,
					'mk_suffix'       => $suffix,
					'mk_sort_order'   => $i,
				)
			);
		}
	}

	private function seed_values(): void {
		$values = array(
			array( 'restraint', 'Restraint', 'We design for longevity, not for the trend cycle.', 'dashicons-minus' ),
			array( 'craft', 'Craft', 'Every material choice is deliberate and specified by name.', 'dashicons-hammer' ),
			array( 'transparency', 'Transparency', 'Clients see the process, the programme, and the numbers.', 'dashicons-visibility' ),
			array( 'collaboration', 'Collaboration', 'The best spaces are built with people, not merely for them.', 'dashicons-groups' ),
		);

		foreach ( $values as $i => list( $slug, $title, $body, $icon ) ) {
			$id = $this->upsert(
				'mk_value',
				$slug,
				array(
					'post_title'   => $title,
					'post_content' => $body,
					'menu_order'   => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_icon'       => $icon,
					'mk_sort_order' => $i,
				)
			);
		}
	}

	private function seed_process_steps(): void {
		$steps = array(
			array( 'discovery', 'Discovery', 'We walk the space, listen, and agree what success looks like.' ),
			array( 'concept', 'Concept', 'Sketches, samples, and a clear direction before anything is committed.' ),
			array( 'documentation', 'Documentation', 'Drawings and specifications precise enough to build from.' ),
			array( 'delivery', 'Delivery', 'On-site coordination through to snagging and handover.' ),
		);

		foreach ( $steps as $i => list( $slug, $title, $body ) ) {
			$id = $this->upsert(
				'mk_process_step',
				$slug,
				array(
					'post_title'   => $title,
					'post_content' => $body,
					'menu_order'   => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_step_no'    => $i + 1,
					'mk_sort_order' => $i,
				)
			);
		}
	}

	private function seed_faqs(): void {
		$faqs = array(
			array( 'typical-timeline', 'What is your typical project timeline?', 'Most residential projects run 8–14 weeks from concept to handover. Larger builds and hospitality projects vary — we give you a programme in writing before you commit.' ),
			array( 'work-outside-city', 'Do you work outside the city?', 'Yes. We take on a limited number of projects nationwide each year, and travel is built into the fee proposal up front.' ),
			array( 'how-fees-work', 'How do your fees work?', 'A fixed fee for design stages, and a percentage for delivery. You see the full breakdown before anything is signed.' ),
		);

		foreach ( $faqs as $i => list( $slug, $question, $answer ) ) {
			$id = $this->upsert(
				'mk_faq',
				$slug,
				array(
					'post_title'   => $question,
					'post_content' => $answer,
					'menu_order'   => $i,
				)
			);

			$this->set_meta(
				$id,
				array(
					'mk_group'      => 'general',
					'mk_sort_order' => $i,
				)
			);
		}
	}

	private function seed_hero_slides(): void {
		$settings = get_option( 'mk_site_settings', array() );

		$still = DemoAssets::image( 'mk-demo-hero-still', 'Spaces, considered', 2400, 1350, 0 );
		$gif   = DemoAssets::image( 'mk-demo-hero-motion', 'Texture in motion', 2400, 1350, 2 );
		$vposter = DemoAssets::image( 'mk-demo-hero-video-poster', 'Craft in every frame', 2400, 1350, 4 );
		$lposter = DemoAssets::image( 'mk-demo-hero-link-poster', 'Every angle intentional', 2400, 1350, 6 );

		$url = static function ( int $id ): string {
			$src = $id ? wp_get_attachment_url( $id ) : '';
			return $src ? $src : '';
		};

		$settings['hero_slide_duration'] = 6;
		$settings['hero_slides']         = array(
			array(
				'media_kind' => 'image',
				'image_url'  => $url( $still ),
				'eyebrow'    => 'Maapkathi Studio',
				'headline'   => 'Spaces, considered.',
				'body'       => 'Interior and architectural design for people who live in the details.',
				'cta_label'  => 'View our work',
				'cta_href'   => home_url( '/work/' ),
				'is_active'  => true,
			),
			array(
				// A generated PNG stands in for the GIF slot so the GIF code
				// path renders correctly on a fresh install; replace it with
				// a real animated GIF from the Hero screen at any time.
				'media_kind'          => 'gif',
				'gif_url'             => $url( $gif ),
				'gif_first_frame_url' => $url( $gif ),
				'eyebrow'             => 'Material first',
				'headline'            => 'Texture in motion.',
				'body'                => 'Lime plaster, oak, blackened steel — and the daylight between them.',
				'is_active'           => true,
			),
			array(
				// Left without a file deliberately: uploading a real ~6s MP4
				// here is the one step that needs a human. The poster keeps
				// the slide looking finished until then.
				'media_kind'            => 'video_upload',
				'video_source'          => 'upload',
				'video_upload_url'      => '',
				'video_poster'          => $url( $vposter ),
				'eyebrow'               => 'On site',
				'headline'              => 'Craft in every frame.',
				'body'                  => 'Upload a short MP4 on the Hero screen to bring this slide to life.',
				'hold_until_video_ends' => false,
				'is_active'             => true,
			),
			array(
				'media_kind'   => 'video_link',
				'video_source' => 'link',
				'video_url'    => 'https://vimeo.com/76979871',
				'video_poster' => $url( $lposter ),
				'eyebrow'      => 'Film',
				'headline'     => 'Every angle, intentional.',
				'is_active'    => true,
			),
		);

		update_option( 'mk_site_settings', $settings );
	}

	/**
	 * Creates the WordPress Pages the custom templates need, assigns the
	 * right template, and points Settings → Reading at the front page —
	 * otherwise a fresh install shows the blog index and every custom page
	 * 404s, which is exactly the "it looks broken on first load" problem.
	 */
	private function ensure_pages(): void {
		$pages = array(
			'home'     => array( 'Home', '' ),
			'about'    => array( 'About', 'page-about.php' ),
			'team'     => array( 'Team', 'page-team.php' ),
			'services' => array( 'Services', 'page-services.php' ),
			'contact'  => array( 'Contact', 'page-contact.php' ),
			'blog'     => array( 'Blog', '' ),
		);

		$ids = array();

		foreach ( $pages as $slug => list( $title, $template ) ) {
			$id = $this->upsert(
				'page',
				$slug,
				array(
					'post_title'   => $title,
					'post_content' => '',
				)
			);

			if ( $id && $template ) {
				update_post_meta( $id, '_wp_page_template', $template );
			}

			$ids[ $slug ] = $id;
		}

		if ( ! empty( $ids['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $ids['home'] );
		}
		if ( ! empty( $ids['blog'] ) ) {
			update_option( 'page_for_posts', $ids['blog'] );
		}

		// Pretty permalinks — the CPT routes (/work/{slug}) depend on them.
		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
	}
}
