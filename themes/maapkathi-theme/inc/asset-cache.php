<?php
/**
 * Path-fingerprinted asset URLs.
 *
 * Production sits behind LiteSpeed with "remove query strings from static
 * resources" switched on, so the usual `?ver=<mtime>` cache-buster is
 * stripped before it ever reaches the browser: every visitor keeps the
 * stylesheet they first downloaded for the full week of
 * `Cache-Control: max-age=604800`, and no deploy is ever picked up. This
 * was observed directly — the same URL returned a 12,535-byte stylesheet
 * from August while `?bust=…` returned the current 21,259-byte one.
 *
 * The fix is to move the version out of the query string and into the
 * path, which nothing can strip: each source file is mirrored into
 * uploads as `<name>.<mtime>.<ext>`. A changed file yields a URL no cache
 * layer has ever seen, so it is fetched fresh with no purge needed, while
 * an unchanged file keeps its URL and stays cached for the full week.
 *
 * Every failure path falls back to the plain theme URL with a `?ver=`, so
 * a read-only or unusual uploads directory degrades to today's behaviour
 * rather than a missing stylesheet.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subdirectory of wp-content/uploads holding the mirrored assets.
 */
const MK_ASSET_CACHE_DIR = 'maapkathi-assets';

/**
 * Version string for a theme-relative asset, for use as wp_enqueue_*()'s
 * $ver argument.
 *
 * Never returns an empty string: WordPress treats '' as "no version" and
 * drops the query entirely, which is the very state this file exists to
 * avoid on the fallback path.
 *
 * @param string $rel Theme-relative path, leading slash included.
 * @return string Non-empty version string.
 */
function mk_asset_version( string $rel ): string {
	$path  = get_stylesheet_directory() . $rel;
	$stamp = file_exists( $path ) ? filemtime( $path ) : false;
	if ( false !== $stamp ) {
		return (string) $stamp;
	}

	$theme_version = (string) wp_get_theme()->get( 'Version' );
	return '' !== $theme_version ? $theme_version : MK_THEME_VERSION;
}

/**
 * URL for a theme-relative asset, fingerprinted in the path where that is
 * possible and falling back to the plain theme URL where it is not.
 *
 * @param string $rel Theme-relative path, leading slash included.
 * @return string Absolute URL.
 */
function mk_asset_url( string $rel ): string {
	$fallback = get_stylesheet_directory_uri() . $rel;
	$source   = get_stylesheet_directory() . $rel;

	if ( ! file_exists( $source ) ) {
		return $fallback;
	}

	$stamp = filemtime( $source );
	if ( false === $stamp ) {
		return $fallback;
	}

	$name = basename( $rel );
	$stem = pathinfo( $name, PATHINFO_FILENAME );
	$ext  = pathinfo( $name, PATHINFO_EXTENSION );
	if ( '' === $stem || '' === $ext ) {
		return $fallback;
	}

	$mirrored = $stem . '.' . $stamp . '.' . $ext;
	$uploads  = wp_get_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
		return $fallback;
	}

	$cache_dir = trailingslashit( $uploads['basedir'] ) . MK_ASSET_CACHE_DIR;
	$target    = $cache_dir . '/' . $mirrored;

	if ( ! file_exists( $target ) && ! mk_asset_mirror( $source, $cache_dir, $target, $stem, $ext ) ) {
		return $fallback;
	}

	return trailingslashit( $uploads['baseurl'] ) . MK_ASSET_CACHE_DIR . '/' . $mirrored;
}

/**
 * Copies one source asset into the uploads mirror and prunes the previous
 * fingerprints of that same asset.
 *
 * @param string $source    Absolute path of the theme file.
 * @param string $cache_dir Absolute path of the mirror directory.
 * @param string $target    Absolute path the file is copied to.
 * @param string $stem      Filename without extension, e.g. "base".
 * @param string $ext       Extension without the dot, e.g. "css".
 * @return bool True when the mirrored file exists and is readable afterwards.
 */
function mk_asset_mirror( string $source, string $cache_dir, string $target, string $stem, string $ext ): bool {
	if ( ! wp_mkdir_p( $cache_dir ) ) {
		return false;
	}

	// Written to a temporary name first and then renamed, so a concurrent
	// request can never link a half-copied stylesheet: rename() is atomic
	// within a filesystem, plain copy() is not.
	$temp = $target . '.' . wp_generate_password( 8, false ) . '.tmp';
	if ( ! copy( $source, $temp ) ) {
		return false;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- atomicity is the entire point of the temp-file dance, and WP_Filesystem::move() neither guarantees it nor is worth bootstrapping the filesystem API on a front-end request for.
	if ( ! rename( $temp, $target ) ) {
		wp_delete_file( $temp );
		return false;
	}

	mk_asset_prune( $cache_dir, $stem, $ext, basename( $target ) );

	return true;
}

/**
 * Deletes fingerprints of one asset that nothing can still be pointing at,
 * so the mirror does not grow by a file per deploy forever.
 *
 * A superseded file is NOT removed straight away: page HTML is cached
 * downstream too, so a visitor can be handed markup that still references
 * the previous fingerprint, and deleting it eagerly would turn a stale
 * stylesheet into a 404 — a worse failure than the one this file fixes.
 * They are kept for a week, matching the Cache-Control window on the
 * static files themselves.
 *
 * Only exact `<stem>.<digits>.<ext>` names are considered, and the current
 * one is always kept — an unrelated upload sharing the directory is never
 * touched.
 *
 * @param string $cache_dir Absolute path of the mirror directory.
 * @param string $stem      Filename without extension.
 * @param string $ext       Extension without the dot.
 * @param string $keep      Filename that must survive.
 * @return void
 */
function mk_asset_prune( string $cache_dir, string $stem, string $ext, string $keep ): void {
	$found = glob( $cache_dir . '/' . $stem . '.*.' . $ext );
	if ( ! is_array( $found ) ) {
		return;
	}

	foreach ( $found as $file ) {
		$name = basename( $file );
		if ( $name === $keep ) {
			continue;
		}

		if ( 1 !== preg_match( '/^' . preg_quote( $stem, '/' ) . '\.\d+\.' . preg_quote( $ext, '/' ) . '$/', $name ) ) {
			continue;
		}

		$copied = filemtime( $file );
		if ( false !== $copied && ( time() - $copied ) > WEEK_IN_SECONDS ) {
			wp_delete_file( $file );
		}
	}
}
