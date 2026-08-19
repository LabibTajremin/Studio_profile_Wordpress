<?php
/**
 * The registry of front-end sections and their per-section chrome
 * (FR-02, FR-03).
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One list of every section the theme can render, and the per-section
 * title/subtitle/anchor/visibility that goes with it.
 *
 * Deliberately keyed by a stable internal id that has nothing to do with
 * the section's displayed title (FR-02.4): renaming "Trusted by" to "Our
 * Clients" changes what a visitor reads and nothing else — not the id, not
 * the DOM id, not the anchor, and not any menu link pointing at it.
 */
final class SectionRegistry {

	public const OPTION = 'mk_sections';

	/**
	 * Every section, in the order the theme renders them by default.
	 *
	 * 'text_key' points at the Site Text field that already holds the
	 * heading, so the two do not become two competing sources of truth.
	 *
	 * @return array<string,array{label:string,anchor:string,text_key:string,page:string,core:bool}>
	 */
	public static function all(): array {
		return array(
			'hero'          => array(
				'label'    => __( 'Hero', 'maapkathi' ),
				'anchor'   => 'hero',
				'text_key' => 'home_hero_eyebrow',
				'page'     => 'home',
				'core'     => true,
			),
			'tagline'       => array(
				'label'    => __( 'Tagline note', 'maapkathi' ),
				'anchor'   => 'tagline',
				'text_key' => 'home_tagline_note',
				'page'     => 'home',
				'core'     => false,
			),
			'clients'       => array(
				'label'    => __( 'Trusted by / Our Clients', 'maapkathi' ),
				'anchor'   => 'clients',
				'text_key' => 'home_clients_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'categories'    => array(
				'label'    => __( 'Portfolio categories', 'maapkathi' ),
				'anchor'   => 'categories',
				'text_key' => 'home_categories_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'projects'      => array(
				'label'    => __( 'Featured work', 'maapkathi' ),
				'anchor'   => 'work',
				'text_key' => 'home_projects_heading',
				'page'     => 'home',
				'core'     => true,
			),
			'services'      => array(
				'label'    => __( 'Services', 'maapkathi' ),
				'anchor'   => 'services',
				'text_key' => 'home_services_heading',
				'page'     => 'home',
				'core'     => true,
			),
			'stats'         => array(
				'label'    => __( 'Stats band', 'maapkathi' ),
				'anchor'   => 'stats',
				'text_key' => 'home_stats_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'values'        => array(
				'label'    => __( 'What we stand for', 'maapkathi' ),
				'anchor'   => 'values',
				'text_key' => 'home_values_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'team'          => array(
				'label'    => __( 'Team', 'maapkathi' ),
				'anchor'   => 'team',
				'text_key' => 'home_team_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'testimonials'  => array(
				'label'    => __( 'Testimonials', 'maapkathi' ),
				'anchor'   => 'testimonials',
				'text_key' => 'home_testimonials_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'awards'        => array(
				'label'    => __( 'Awards', 'maapkathi' ),
				'anchor'   => 'awards',
				'text_key' => 'home_awards_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'faq'           => array(
				'label'    => __( 'FAQ', 'maapkathi' ),
				'anchor'   => 'faq',
				'text_key' => 'home_faq_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'partners'      => array(
				'label'    => __( 'Our Partners', 'maapkathi' ),
				'anchor'   => 'partners',
				'text_key' => 'home_partners_heading',
				'page'     => 'home',
				'core'     => false,
			),
			'cta'           => array(
				'label'    => __( 'Closing call to action', 'maapkathi' ),
				'anchor'   => 'contact-cta',
				'text_key' => 'home_cta_heading',
				'page'     => 'home',
				'core'     => true,
			),
			'work_archive'  => array(
				'label'    => __( 'Work archive (page)', 'maapkathi' ),
				'anchor'   => 'work-archive',
				'text_key' => 'work_archive_heading',
				'page'     => 'work',
				'core'     => true,
			),
			'services_page' => array(
				'label'    => __( 'Services (page)', 'maapkathi' ),
				'anchor'   => 'services-page',
				'text_key' => 'services_heading',
				'page'     => 'services',
				'core'     => true,
			),
			'about_page'    => array(
				'label'    => __( 'About (page)', 'maapkathi' ),
				'anchor'   => 'about-page',
				'text_key' => 'about_heading',
				'page'     => 'about',
				'core'     => true,
			),
			'team_page'     => array(
				'label'    => __( 'Team (page)', 'maapkathi' ),
				'anchor'   => 'team-page',
				'text_key' => 'team_heading',
				'page'     => 'team',
				'core'     => true,
			),
			'contact_page'  => array(
				'label'    => __( 'Contact (page)', 'maapkathi' ),
				'anchor'   => 'contact-page',
				'text_key' => 'contact_heading',
				'page'     => 'contact',
				'core'     => true,
			),
		);
	}

	/**
	 * Every section id, in registry order.
	 *
	 * @return string[]
	 */
	public static function ids(): array {
		return array_keys( self::all() );
	}

	/**
	 * Every section instance that can render, keyed by instance id.
	 *
	 * The homepage's instances come from the layout, so a duplicated
	 * section gets its own record; the inner pages are one instance each,
	 * keyed by their type.
	 *
	 * @return array<string,array{type:string,title:string,subtitle:string,show_title:bool,anchor:string}>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$types  = self::all();
		$out    = array();

		$instances = array();
		foreach ( SectionLayout::get() as $row ) {
			$instances[ $row['id'] ] = $row['type'];
		}
		foreach ( $types as $type => $section ) {
			if ( 'home' !== $section['page'] ) {
				$instances[ $type ] = $type;
			}
		}

		foreach ( $instances as $id => $type ) {
			$section = $types[ $type ] ?? null;
			if ( null === $section ) {
				continue;
			}

			$row = is_array( $stored[ $id ] ?? null ) ? $stored[ $id ] : array();

			// The first instance of a type keeps its anchor and title from
			// the registry/Site Text, so an existing site is untouched. A
			// duplicate has to differ, or the two would collide.
			$is_original = ( $id === $type );

			$out[ $id ] = array(
				'type'       => $type,
				'title'      => (string) ( $row['title'] ?? '' ),
				'subtitle'   => (string) ( $row['subtitle'] ?? '' ),
				// Defaults to shown: a section that has always had a heading
				// must keep it after the update (GR-03).
				'show_title' => ! isset( $row['show_title'] ) || (bool) $row['show_title'],
				'anchor'     => '' !== (string) ( $row['anchor'] ?? '' )
					? (string) $row['anchor']
					: ( $is_original ? $section['anchor'] : sanitize_title( $id ) ),
			);
		}

		return $out;
	}

	/**
	 * The type a section instance is an instance of.
	 *
	 * @param string $id Instance id.
	 * @return string Type id, or an empty string when unknown.
	 */
	public static function type_of( string $id ): string {
		$state = self::get();

		return (string) ( $state[ $id ]['type'] ?? ( isset( self::all()[ $id ] ) ? $id : '' ) );
	}

	/**
	 * One section's stored state.
	 *
	 * @param string $id Section instance id.
	 * @return array{type:string,title:string,subtitle:string,show_title:bool,anchor:string}
	 */
	public static function for_section( string $id ): array {
		$all = self::get();

		return $all[ $id ] ?? array(
			'type'       => isset( self::all()[ $id ] ) ? $id : '',
			'title'      => '',
			'subtitle'   => '',
			'show_title' => true,
			'anchor'     => sanitize_title( $id ),
		);
	}

	/**
	 * Validates a posted payload.
	 *
	 * Anchors are lowercase letters, digits and hyphens only, and must be
	 * unique across the page — a duplicate would make one of two sections
	 * unreachable from a menu link, silently (FR-02.5).
	 *
	 * @param array<string,mixed> $input Raw payload keyed by section id.
	 * @return array{values:array<string,array{subtitle:string,show_title:bool,anchor:string}>,errors:array<string,string>}
	 */
	public static function sanitize( array $input ): array {
		$types    = self::all();
		$sections = array();
		$values   = array();
		$errors   = array();
		$seen     = array();

		// Iterate instances rather than types, so a duplicated section is
		// validated in its own right instead of being folded into the
		// original's record.
		foreach ( self::get() as $id => $state ) {
			$sections[ $id ] = $types[ $state['type'] ];
		}

		foreach ( $sections as $id => $section ) {
			$row = is_array( $input[ $id ] ?? null ) ? $input[ $id ] : array();

			$anchor = sanitize_title( (string) ( $row['anchor'] ?? '' ) );
			if ( '' === $anchor ) {
				$anchor = self::for_section( $id )['anchor'];
			}

			// Uniqueness is per page: two sections on different templates
			// can share an anchor without either becoming unreachable.
			if ( isset( $seen[ $section['page'] . '#' . $anchor ] ) ) {
				$taken = $sections[ $seen[ $section['page'] . '#' . $anchor ] ]['label'];

				// Neither the previously saved anchor nor the registry
				// default is guaranteed free — the section that took this
				// anchor may well have taken it FROM this one. So the
				// fallback appends a suffix until it actually is free,
				// which cannot loop forever and cannot silently leave two
				// sections sharing an anchor.
				$anchor = self::free_anchor( $section['page'], $anchor, $seen );

				$errors[ $id ] = sprintf(
					/* translators: 1: the requested anchor, 2: the section already using it, 3: the anchor that was used instead. */
					__( '"%1$s" is already used by %2$s, so this section was given "%3$s" instead.', 'maapkathi' ),
					sanitize_title( (string) ( $row['anchor'] ?? '' ) ),
					$taken,
					$anchor
				);
			}

			$seen[ $section['page'] . '#' . $anchor ] = $id;

			$values[ $id ] = array(
				'title'      => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
				'subtitle'   => sanitize_textarea_field( (string) ( $row['subtitle'] ?? '' ) ),
				'show_title' => ! empty( $row['show_title'] ),
				'anchor'     => $anchor,
			);
		}

		return array(
			'values' => $values,
			'errors' => $errors,
		);
	}

	/**
	 * The first free variant of an anchor on a given page.
	 *
	 * @param string               $page   Page the anchor lives on.
	 * @param string               $anchor Anchor that was already taken.
	 * @param array<string,string> $seen   Anchors already assigned, keyed "page#anchor".
	 * @return string An anchor not present in $seen.
	 */
	private static function free_anchor( string $page, string $anchor, array $seen ): string {
		$suffix = 2;

		while ( isset( $seen[ $page . '#' . $anchor . '-' . $suffix ] ) ) {
			++$suffix;
		}

		return $anchor . '-' . $suffix;
	}
}
