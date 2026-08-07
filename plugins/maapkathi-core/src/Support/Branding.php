<?php
/**
 * Logo, favicon and accent-color resolution for site branding.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logo + favicon resolution (§3.5).
 *
 * Favicon fallback chain, in order: admin favicon → light logo → dark logo
 * → the built-in default mark. There is never a broken-image or an empty
 * tab icon.
 */
final class Branding {

	public static function settings(): array {
		$settings = get_option( 'mk_site_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	public static function logo_light_url(): string {
		return self::attachment_url( 'logo_light' );
	}

	public static function logo_dark_url(): string {
		return self::attachment_url( 'logo_dark' );
	}

	public static function favicon_url(): string {
		$chain = array(
			self::attachment_url( 'favicon' ),
			self::logo_light_url(),
			self::logo_dark_url(),
		);

		foreach ( $chain as $url ) {
			if ( '' !== $url ) {
				return $url;
			}
		}

		return self::default_mark_url();
	}

	/**
	 * The built-in wordmark, rendered as an inline-SVG data URI so it needs
	 * no uploaded file and no HTTP request, and it picks up the site accent.
	 */
	public static function default_mark_url(): string {
		$accent = self::accent_hex();

		$svg = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="12" fill="%1$s"/><text x="50%%" y="52%%" dominant-baseline="middle" text-anchor="middle" font-family="Georgia,serif" font-size="34" fill="%2$s">M</text></svg>',
			esc_attr( $accent ),
			esc_attr( self::on_accent_hex( $accent ) )
		);

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public static function accent_hex(): string {
		$theme  = \Maapkathi\Core\Theme\ThemeSettings::get();
		$accent = \Maapkathi\Core\Theme\Accents::by_id( $theme['accent_id'] );

		return $theme['custom_accent_hex'] ?: ( $accent['light'] ?? '#6e1f2a' );
	}

	private static function on_accent_hex( string $accent ): string {
		return \Maapkathi\Core\Theme\Accents::on_accent( $accent );
	}

	/**
	 * Resolves a settings key that stores either an attachment ID or a raw
	 * URL, so the Settings screen can accept both.
	 */
	private static function attachment_url( string $key ): string {
		$settings = self::settings();
		$value    = $settings[ $key ] ?? '';

		if ( empty( $value ) ) {
			return '';
		}

		if ( is_numeric( $value ) ) {
			$url = wp_get_attachment_url( (int) $value );
			return $url ? $url : '';
		}

		return esc_url_raw( (string) $value );
	}

	/** Emits the favicon link tags, honouring the fallback chain. */
	public static function render_favicon_tags(): void {
		// If the site owner set a favicon through WordPress's own Site Icon
		// feature, core already emits the correct tags — don't duplicate.
		if ( has_site_icon() ) {
			return;
		}

		$url = self::favicon_url();
		if ( '' === $url ) {
			return;
		}

		$type = str_starts_with( $url, 'data:image/svg' ) || str_ends_with( strtok( $url, '?' ), '.svg' )
			? 'image/svg+xml'
			: '';

		printf(
			'<link rel="icon" href="%s"%s />' . "\n",
			esc_url( $url ),
			$type ? ' type="' . esc_attr( $type ) . '"' : ''
		);
		printf( '<link rel="apple-touch-icon" href="%s" />' . "\n", esc_url( $url ) );
	}
}
