<?php
/**
 * Global template helpers.
 *
 * The theme calls these instead of reaching into options or building its
 * own queries, keeping the "plugin owns data, theme only renders" rule
 * intact. All are prefixed mk_ and guarded so a theme can be swapped
 * without fatals if the plugin is ever deactivated.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

use Maapkathi\Core\Support\SiteText;
use Maapkathi\Core\Support\Content;
use Maapkathi\Core\Support\Branding;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mk_text' ) ) {
	/**
	 * One of the 29 editable copy strings, with its shipped default.
	 *
	 * @param string $key Site Text field key.
	 * @return string
	 */
	function mk_text( string $key ): string {
		return SiteText::text( $key );
	}
}

if ( ! function_exists( 'mk_the_text' ) ) {
	/**
	 * Echoes an escaped copy string.
	 *
	 * @param string $key Site Text field key.
	 * @return void
	 */
	function mk_the_text( string $key ): void {
		echo esc_html( SiteText::text( $key ) );
	}
}

if ( ! function_exists( 'mk_settings' ) ) {
	/**
	 * Reads the `mk_site_settings` option, guaranteed as an array.
	 *
	 * @return array<string,mixed>
	 */
	function mk_settings(): array {
		$settings = get_option( 'mk_site_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}
}

if ( ! function_exists( 'mk_setting' ) ) {
	/**
	 * Reads a single site setting, with a fallback default.
	 *
	 * @param string $key           Site settings key.
	 * @param mixed  $default_value Value to return when the key is unset.
	 * @return mixed
	 */
	function mk_setting( string $key, $default_value = '' ) {
		$settings = mk_settings();
		return $settings[ $key ] ?? $default_value;
	}
}

if ( ! function_exists( 'mk_content' ) ) {
	/**
	 * Content accessor: mk_content('clients'), mk_content('testimonials'),
	 * mk_content('stats'), mk_content('values'), mk_content('awards'),
	 * mk_content('process_steps'), mk_content('members'), mk_content('faqs'),
	 * mk_content('featured_projects'), mk_content('project_categories'),
	 * mk_content('top_level_services').
	 *
	 * @param string $what Name of the Content:: accessor method to call.
	 * @param mixed  ...$args Arguments forwarded to that accessor method.
	 * @return array<int,mixed>
	 */
	function mk_content( string $what, ...$args ): array {
		if ( ! is_callable( array( Content::class, $what ) ) ) {
			return array();
		}
		return call_user_func_array( array( Content::class, $what ), $args );
	}
}

if ( ! function_exists( 'mk_meta' ) ) {
	/** Post meta with a default, saving the theme repetitive guards. */
	function mk_meta( int $post_id, string $key, string $default = '' ): string {
		$value = get_post_meta( $post_id, $key, true );
		return ( '' === $value || null === $value ) ? $default : (string) $value;
	}
}

if ( ! function_exists( 'mk_blog_enabled' ) ) {
	function mk_blog_enabled(): bool {
		return ! empty( mk_setting( 'blog_enabled' ) );
	}
}

if ( ! function_exists( 'mk_logo_light_url' ) ) {
	function mk_logo_light_url(): string {
		return Branding::logo_light_url();
	}
}

if ( ! function_exists( 'mk_logo_dark_url' ) ) {
	function mk_logo_dark_url(): string {
		return Branding::logo_dark_url();
	}
}

if ( ! function_exists( 'mk_default_mark_url' ) ) {
	function mk_default_mark_url(): string {
		return Branding::default_mark_url();
	}
}

if ( ! function_exists( 'mk_tel_href' ) ) {
	/** Normalises a phone number into a tel: href value. */
	function mk_tel_href( string $phone ): string {
		return 'tel:' . preg_replace( '/[^0-9+]/', '', $phone );
	}
}

if ( ! function_exists( 'mk_placeholder_url' ) ) {
	/**
	 * On-the-fly gradient SVG placeholder (§3.5). Entirely local — no
	 * third-party image host, so a fresh install has no broken images and
	 * no external requests.
	 */
	function mk_placeholder_url( string $label = '', int $width = 1600, int $height = 1000 ): string {
		return \Maapkathi\Core\Rest\PlaceholderController::url( $label, $width, $height );
	}
}
