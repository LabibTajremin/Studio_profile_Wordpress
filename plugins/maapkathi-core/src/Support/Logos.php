<?php
/**
 * Logo resolution for content types that show a logo rather than a photo.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the logo image for clients and partners.
 *
 * The featured image is the wrong field for a logo: it is what represents
 * the record everywhere else (a project photo, a room shot), and a client
 * wall that reads it ends up showing an interior photograph among the
 * logos. So both types carry a dedicated `mk_logo` attachment, and the
 * featured image is only a fallback — sites that filled it in before this
 * field existed keep rendering exactly as they did.
 */
final class Logos {

	/**
	 * Meta key holding the dedicated logo attachment id.
	 */
	public const META_KEY = 'mk_logo';

	/**
	 * Attachment id of a record's logo, or 0 when it has none.
	 *
	 * @param \WP_Post|int $post Post or post id.
	 * @return int Attachment id, 0 when neither field is set.
	 */
	public static function id( $post ): int {
		$post_id = $post instanceof \WP_Post ? $post->ID : (int) $post;
		if ( $post_id <= 0 ) {
			return 0;
		}

		$logo = (int) get_post_meta( $post_id, self::META_KEY, true );
		if ( $logo > 0 && 'attachment' === get_post_type( $logo ) ) {
			return $logo;
		}

		return (int) get_post_thumbnail_id( $post_id );
	}

	/**
	 * Whether a record has a logo to show.
	 *
	 * @param \WP_Post|int $post Post or post id.
	 * @return bool
	 */
	public static function has( $post ): bool {
		return self::id( $post ) > 0;
	}

	/**
	 * Whether a record has a logo in the dedicated field specifically,
	 * ignoring the featured-image fallback.
	 *
	 * @param \WP_Post|int $post Post or post id.
	 * @return bool
	 */
	public static function has_dedicated( $post ): bool {
		$post_id = $post instanceof \WP_Post ? $post->ID : (int) $post;
		if ( $post_id <= 0 ) {
			return false;
		}

		$logo = (int) get_post_meta( $post_id, self::META_KEY, true );

		return $logo > 0 && 'attachment' === get_post_type( $logo );
	}

	/**
	 * Renders a record's logo as an `<img>`, or '' when it has none.
	 *
	 * @param \WP_Post|int         $post           Post or post id.
	 * @param string               $size           Registered image size.
	 * @param array<string,string> $attr           Attributes merged over the defaults.
	 * @param bool                 $dedicated_only Ignore the featured-image fallback.
	 * @return string Escaped image markup, or '' when there is no logo.
	 */
	public static function image( $post, string $size = 'medium', array $attr = array(), bool $dedicated_only = false ): string {
		$id = $dedicated_only
			? ( self::has_dedicated( $post ) ? self::id( $post ) : 0 )
			: self::id( $post );
		if ( ! $id ) {
			return '';
		}

		$post_id = $post instanceof \WP_Post ? $post->ID : (int) $post;
		$alt     = (string) get_post_meta( $post_id, 'mk_alt_text', true );

		$defaults = array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		);

		// Alt text falls back to the record's own title, so a logo is never
		// announced as an unnamed image.
		if ( '' === $alt ) {
			$alt = (string) get_the_title( $post_id );
		}
		$defaults['alt'] = $alt;

		return (string) wp_get_attachment_image( $id, $size, false, array_merge( $defaults, $attr ) );
	}
}
