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

	public static function get(): array {
		return get_option( self::OPTION, array() );
	}
}
