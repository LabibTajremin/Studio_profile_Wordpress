<?php
/**
 * Homepage.
 *
 * The page is assembled from the section layout rather than a fixed list,
 * so an admin can reorder, disable, duplicate or remove sections without
 * this file changing (FR-03). Each section lives in its own partial under
 * parts/sections/ and is handed the instance id it is rendering, which is
 * what lets the same type appear more than once on one page.
 *
 * Every section still self-hides when it has no content, so a partially
 * filled site never renders an empty heading.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

get_template_part( 'parts/hero' );

foreach ( mk_section_layout() as $mk_section_row ) {
	if ( empty( $mk_section_row['enabled'] ) ) {
		continue;
	}

	// The hero is rendered above, outside the loop, because it sits before
	// the main content region rather than inside it.
	if ( 'hero' === $mk_section_row['type'] ) {
		continue;
	}

	$mk_section_file = get_theme_file_path( 'parts/sections/' . $mk_section_row['type'] . '.php' );
	if ( ! file_exists( $mk_section_file ) ) {
		continue;
	}

	// require rather than get_template_part(), so $mk_id reaches the
	// partial; get_theme_file_path() still allows a child-theme override.
	$mk_id = (string) $mk_section_row['id'];
	require $mk_section_file;
}

// FR-05.1: the map, behind its own separate homepage toggle.
get_template_part( 'parts/section-map', null, array( 'context' => 'home' ) );

get_footer();
