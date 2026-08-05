<?php
/**
 * Navigation helpers.
 *
 * Both are guarded: mk_blog_enabled() is also provided by the plugin's
 * template-functions.php (which loads first, on plugins_loaded), and an
 * unguarded redeclaration here would be a fatal error. The theme keeps its
 * own definition only as a fallback for when the plugin is inactive.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mk_blog_enabled' ) ) {
	function mk_blog_enabled(): bool {
		$settings = get_option( 'mk_site_settings', array() );
		return ! empty( $settings['blog_enabled'] );
	}
}

if ( ! function_exists( 'mk_nav_items' ) ) {
	/**
	 * Site navigation. Uses the admin's custom nav_config when set,
	 * otherwise builds the default nav — including Blog only when the blog
	 * is enabled (§3.5).
	 *
	 * @return array<int,array{label:string,href:string}>
	 */
	function mk_nav_items(): array {
		$settings = get_option( 'mk_site_settings', array() );

		if ( ! empty( $settings['nav_config'] ) && is_array( $settings['nav_config'] ) ) {
			$custom = array();
			foreach ( $settings['nav_config'] as $item ) {
				if ( ! empty( $item['label'] ) && ! empty( $item['href'] ) ) {
					$custom[] = array(
						'label' => (string) $item['label'],
						'href'  => (string) $item['href'],
					);
				}
			}
			if ( $custom ) {
				return $custom;
			}
		}

		$items = array(
			array(
				'label' => __( 'Work', 'maapkathi' ),
				'href'  => (string) get_post_type_archive_link( 'mk_project' ) ?: home_url( '/work/' ),
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

		if ( mk_blog_enabled() ) {
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
}
