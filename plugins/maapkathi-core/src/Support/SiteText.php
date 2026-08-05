<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The 29 editable copy strings from §3.3 #15 ("Site Text"), grouped by
 * page/section. Stored as a flat option keyed by field id.
 */
final class SiteText {

	public const OPTION = 'mk_site_text';

	/**
	 * @return array<string, array<int, array{key:string,label:string}>> group label => fields
	 */
	public static function schema(): array {
		return array(
			'Home'     => array(
				array( 'key' => 'home_hero_eyebrow', 'label' => __( 'Hero eyebrow', 'maapkathi' ) ),
				array( 'key' => 'home_tagline_note', 'label' => __( 'Tagline note', 'maapkathi' ) ),
				array( 'key' => 'home_clients_heading', 'label' => __( 'Client wall heading', 'maapkathi' ) ),
				array( 'key' => 'home_categories_heading', 'label' => __( 'Portfolio categories heading', 'maapkathi' ) ),
				array( 'key' => 'home_projects_heading', 'label' => __( 'Featured projects heading', 'maapkathi' ) ),
				array( 'key' => 'home_services_heading', 'label' => __( 'Services heading', 'maapkathi' ) ),
				array( 'key' => 'home_stats_heading', 'label' => __( 'Stats band heading', 'maapkathi' ) ),
				array( 'key' => 'home_values_heading', 'label' => __( 'Values heading', 'maapkathi' ) ),
				array( 'key' => 'home_team_heading', 'label' => __( 'Team preview heading', 'maapkathi' ) ),
				array( 'key' => 'home_testimonials_heading', 'label' => __( 'Testimonials heading', 'maapkathi' ) ),
				array( 'key' => 'home_awards_heading', 'label' => __( 'Awards heading', 'maapkathi' ) ),
				array( 'key' => 'home_faq_heading', 'label' => __( 'FAQ heading', 'maapkathi' ) ),
				array( 'key' => 'home_cta_heading', 'label' => __( 'Closing CTA heading', 'maapkathi' ) ),
				array( 'key' => 'home_cta_button_label', 'label' => __( 'Closing CTA button label', 'maapkathi' ) ),
			),
			'Work'     => array(
				array( 'key' => 'work_archive_heading', 'label' => __( 'Work archive heading', 'maapkathi' ) ),
				array( 'key' => 'work_empty_state', 'label' => __( 'Empty-state message', 'maapkathi' ) ),
			),
			'Services' => array(
				array( 'key' => 'services_heading', 'label' => __( 'Services page heading', 'maapkathi' ) ),
				array( 'key' => 'services_intro', 'label' => __( 'Services page intro', 'maapkathi' ) ),
			),
			'About'    => array(
				array( 'key' => 'about_heading', 'label' => __( 'About heading', 'maapkathi' ) ),
				array( 'key' => 'about_intro', 'label' => __( 'About intro', 'maapkathi' ) ),
				array( 'key' => 'about_vision_heading', 'label' => __( 'Vision heading', 'maapkathi' ) ),
				array( 'key' => 'about_mission_heading', 'label' => __( 'Mission heading', 'maapkathi' ) ),
			),
			'Team'     => array(
				array( 'key' => 'team_heading', 'label' => __( 'Team page heading', 'maapkathi' ) ),
				array( 'key' => 'team_intro', 'label' => __( 'Team page intro', 'maapkathi' ) ),
			),
			'Contact'  => array(
				array( 'key' => 'contact_heading', 'label' => __( 'Contact heading', 'maapkathi' ) ),
				array( 'key' => 'contact_intro', 'label' => __( 'Contact intro', 'maapkathi' ) ),
				array( 'key' => 'contact_form_button_label', 'label' => __( 'Form submit button label', 'maapkathi' ) ),
				array( 'key' => 'contact_success_message', 'label' => __( 'Success message', 'maapkathi' ) ),
			),
			'Footer'   => array(
				array( 'key' => 'footer_note', 'label' => __( 'Footer note', 'maapkathi' ) ),
			),
		);
	}

	public static function keys(): array {
		$keys = array();
		foreach ( self::schema() as $fields ) {
			foreach ( $fields as $field ) {
				$keys[] = $field['key'];
			}
		}
		return $keys;
	}

	/**
	 * Shipped defaults, so a fresh install renders real copy instead of
	 * blank headings before an admin has touched the Site Text screen.
	 *
	 * @return array<string,string>
	 */
	public static function defaults(): array {
		return array(
			'home_hero_eyebrow'         => __( 'Maapkathi Studio', 'maapkathi' ),
			'home_tagline_note'         => __( 'A design practice built on restraint, craft, and the long view.', 'maapkathi' ),
			'home_clients_heading'      => __( 'Trusted by', 'maapkathi' ),
			'home_categories_heading'   => __( 'What we do', 'maapkathi' ),
			'home_projects_heading'     => __( 'Featured work', 'maapkathi' ),
			'home_services_heading'     => __( 'Services', 'maapkathi' ),
			'home_stats_heading'        => __( 'By the numbers', 'maapkathi' ),
			'home_values_heading'       => __( 'What we stand for', 'maapkathi' ),
			'home_team_heading'         => __( 'The people behind the work', 'maapkathi' ),
			'home_testimonials_heading' => __( 'What our clients say', 'maapkathi' ),
			'home_awards_heading'       => __( 'Recognition', 'maapkathi' ),
			'home_faq_heading'          => __( 'Frequently asked questions', 'maapkathi' ),
			'home_cta_heading'          => __( "Let's build something considered.", 'maapkathi' ),
			'home_cta_button_label'     => __( 'Start a conversation', 'maapkathi' ),
			'work_archive_heading'      => __( 'Our work', 'maapkathi' ),
			'work_empty_state'          => __( 'New projects are being photographed — check back shortly.', 'maapkathi' ),
			'services_heading'          => __( 'Services', 'maapkathi' ),
			'services_intro'            => __( 'End-to-end design, from first sketch to final handover.', 'maapkathi' ),
			'about_heading'             => __( 'About the studio', 'maapkathi' ),
			'about_intro'               => __( 'We design spaces that are meant to be lived in, not just photographed.', 'maapkathi' ),
			'about_vision_heading'      => __( 'Vision', 'maapkathi' ),
			'about_mission_heading'     => __( 'Mission', 'maapkathi' ),
			'team_heading'              => __( 'Our team', 'maapkathi' ),
			'team_intro'                => __( 'A small studio of architects, designers, and makers.', 'maapkathi' ),
			'contact_heading'           => __( 'Get in touch', 'maapkathi' ),
			'contact_intro'             => __( 'Tell us about your space and what you would like it to become.', 'maapkathi' ),
			'contact_form_button_label' => __( 'Send message', 'maapkathi' ),
			'contact_success_message'   => __( 'Thanks — we received your message and will be in touch shortly.', 'maapkathi' ),
			'footer_note'               => __( 'Designed and built with care.', 'maapkathi' ),
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		// An empty string means "admin cleared this field" for storage
		// purposes, but for rendering we still want the shipped default
		// rather than an empty heading.
		$stored = array_filter( $stored, static fn( $v ) => '' !== $v && null !== $v );

		return array_merge( self::defaults(), $stored );
	}

	/** Single copy string, with the shipped default as fallback. */
	public static function text( string $key ): string {
		$all = self::get();
		return (string) ( $all[ $key ] ?? '' );
	}
}
