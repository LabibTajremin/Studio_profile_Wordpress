<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the nav automatically when no custom nav_config is set, including
 * Blog only when the blog is enabled (§3.5).
 *
 * @return array<int,array{label:string,href:string}>
 */
function mk_nav_items(): array {
	$site_settings = get_option( 'mk_site_settings', array() );

	if ( ! empty( $site_settings['nav_config'] ) && is_array( $site_settings['nav_config'] ) ) {
		return $site_settings['nav_config'];
	}

	$items = array(
		array(
			'label' => __( 'Work', 'maapkathi' ),
			'href'  => home_url( '/work/' ),
		),
		array(
			'label' => __( 'Services', 'maapkathi' ),
			'href'  => home_url( '/services/' ),
		),
		array(
			'label' => __( 'About', 'maapkathi' ),
			'href'  => home_url( '/about/' ),
		),
		array(
			'label' => __( 'Team', 'maapkathi' ),
			'href'  => home_url( '/team/' ),
		),
	);

	if ( ! empty( $site_settings['blog_enabled'] ) ) {
		$items[] = array(
			'label' => __( 'Blog', 'maapkathi' ),
			'href'  => home_url( '/blog/' ),
		);
	}

	$items[] = array(
		'label' => __( 'Contact', 'maapkathi' ),
		'href'  => home_url( '/contact/' ),
	);

	return $items;
}

function mk_blog_enabled(): bool {
	$site_settings = get_option( 'mk_site_settings', array() );
	return ! empty( $site_settings['blog_enabled'] );
}
