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
	/**
	 * Post meta with a default, saving the theme repetitive guards.
	 *
	 * @param int    $post_id       Post ID to read meta from.
	 * @param string $key           Meta key.
	 * @param string $default_value Value to return when the meta is empty.
	 * @return string
	 */
	function mk_meta( int $post_id, string $key, string $default_value = '' ): string {
		$value = get_post_meta( $post_id, $key, true );
		return ( '' === $value || null === $value ) ? $default_value : (string) $value;
	}
}

if ( ! function_exists( 'mk_blog_enabled' ) ) {
	/**
	 * Whether the site's blog feature is enabled in site settings.
	 *
	 * @return bool
	 */
	function mk_blog_enabled(): bool {
		return ! empty( mk_setting( 'blog_enabled' ) );
	}
}

if ( ! function_exists( 'mk_logo_light_url' ) ) {
	/**
	 * URL of the configured light-mode logo, or an empty string if unset.
	 *
	 * @return string
	 */
	function mk_logo_light_url(): string {
		return Branding::logo_light_url();
	}
}

if ( ! function_exists( 'mk_logo_dark_url' ) ) {
	/**
	 * URL of the configured dark-mode logo, or an empty string if unset.
	 *
	 * @return string
	 */
	function mk_logo_dark_url(): string {
		return Branding::logo_dark_url();
	}
}

if ( ! function_exists( 'mk_default_mark_url' ) ) {
	/**
	 * URL of the built-in wordmark, used when no logo has been uploaded.
	 *
	 * @return string
	 */
	function mk_default_mark_url(): string {
		return Branding::default_mark_url();
	}
}

if ( ! function_exists( 'mk_tel_href' ) ) {
	/**
	 * Normalises a phone number into a tel: href value.
	 *
	 * @param string $phone Phone number in any format.
	 * @return string
	 */
	function mk_tel_href( string $phone ): string {
		return 'tel:' . preg_replace( '/[^0-9+]/', '', $phone );
	}
}

if ( ! function_exists( 'mk_placeholder_url' ) ) {
	/**
	 * On-the-fly gradient SVG placeholder (§3.5). Entirely local — no
	 * third-party image host, so a fresh install has no broken images and
	 * no external requests.
	 *
	 * @param string $label  Text label to render on the placeholder.
	 * @param int    $width  Placeholder width in pixels.
	 * @param int    $height Placeholder height in pixels.
	 * @return string
	 */
	function mk_placeholder_url( string $label = '', int $width = 1600, int $height = 1000 ): string {
		return \Maapkathi\Core\Rest\PlaceholderController::url( $label, $width, $height );
	}
}

if ( ! function_exists( 'mk_header_is_custom' ) ) {
	/**
	 * Whether the header is painted with a colour of its own (FR-01).
	 *
	 * The theme paints a solid, auto-contrasted bar when this is true and
	 * keeps its translucent accent wash when it is false, so a site that
	 * never opened the setting looks exactly as it did before (GR-03).
	 *
	 * @return bool
	 */
	function mk_header_is_custom(): bool {
		return \Maapkathi\Core\Theme\HeaderColor::is_custom( \Maapkathi\Core\Theme\ThemeSettings::get() );
	}
}

if ( ! function_exists( 'mk_header_logo_mode' ) ) {
	/**
	 * Which logo variant the header should show (FR-01 edge case).
	 *
	 * Once the header can be any colour, "follow the site's light/dark mode"
	 * stops being enough — a dark logo on a dark custom header disappears.
	 * "auto" therefore resolves against the header's own luminance rather
	 * than the page mode whenever a custom colour is set.
	 *
	 * @return string One of 'auto', 'light' or 'dark'.
	 */
	function mk_header_logo_mode(): string {
		$settings = \Maapkathi\Core\Theme\ThemeSettings::get();
		$mode     = (string) ( $settings['header_logo_mode'] ?? 'auto' );

		if ( 'auto' !== $mode ) {
			return $mode;
		}

		if ( ! \Maapkathi\Core\Theme\HeaderColor::is_custom( $settings ) ) {
			return 'auto';
		}

		// on_accent() returns the ink that reads on the bar; a cream ink
		// means the bar is dark, which is where the light logo belongs.
		$resolved = \Maapkathi\Core\Theme\HeaderColor::resolve(
			$settings,
			\Maapkathi\Core\Theme\Accents::by_id( $settings['accent_id'] )['light'] ?? '#6e1f2a'
		);

		return \Maapkathi\Core\Theme\Accents::CREAM === $resolved['fg'] ? 'light' : 'dark';
	}
}
