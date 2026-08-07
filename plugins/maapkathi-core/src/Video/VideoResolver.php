<?php
/**
 * Video field resolver.
 *
 * @package maapkathi-core
 */

declare( strict_types = 1 );

namespace Maapkathi\Core\Video;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One resolver, one shape (§6.2). Templates only ever render from
 * ResolvedVideo — never raw admin input. Handles upload vs. link, and the
 * three playable kinds: uploaded/direct file, YouTube, Vimeo.
 */
final class VideoResolver {

	private const YOUTUBE_HOSTS = array( 'youtube.com', 'www.youtube.com', 'youtu.be', 'm.youtube.com' );
	private const VIMEO_HOSTS   = array( 'vimeo.com', 'player.vimeo.com' );

	/**
	 * Resolves a video meta field into a single renderable shape.
	 *
	 * @param array{video_source:string,video_upload_url?:?string,video_url?:?string,video_poster?:?string} $field         Raw video field values.
	 * @param bool                                                                                          $is_background Whether this video plays as a background loop.
	 * @return ?ResolvedVideo
	 */
	public function resolve( array $field, bool $is_background = false ): ?ResolvedVideo {
		$source = $field['video_source'];
		$poster = $field['video_poster'] ?? null;

		if ( 'upload' === $source ) {
			$url = $field['video_upload_url'] ?? null;
			if ( ! $url ) {
				return null;
			}
			return new ResolvedVideo( 'file', esc_url_raw( $url ), $poster, $is_background );
		}

		$raw = trim( (string) ( $field['video_url'] ?? '' ) );
		if ( '' === $raw ) {
			return null;
		}

		return $this->resolve_link( $raw, $poster, $is_background );
	}

	/**
	 * Resolves a pasted video link (YouTube, Vimeo, or direct file) into a
	 * single renderable shape.
	 *
	 * @param string  $raw           Raw pasted video URL.
	 * @param ?string $poster        Poster image URL, if any.
	 * @param bool    $is_background Whether this video plays as a background loop.
	 * @return ?ResolvedVideo
	 */
	private function resolve_link( string $raw, ?string $poster, bool $is_background ): ?ResolvedVideo {
		$scheme = wp_parse_url( $raw, PHP_URL_SCHEME );
		$host   = wp_parse_url( $raw, PHP_URL_HOST );

		if ( 'https' !== $scheme || ! $host ) {
			return null;
		}

		$host = strtolower( $host );

		if ( in_array( $host, self::YOUTUBE_HOSTS, true ) ) {
			$id = $this->extract_youtube_id( $raw );
			if ( ! $id ) {
				return null;
			}
			return new ResolvedVideo( 'youtube', $this->youtube_embed_url( $id ), $poster, $is_background );
		}

		if ( in_array( $host, self::VIMEO_HOSTS, true ) ) {
			$id = $this->extract_vimeo_id( $raw );
			if ( ! $id ) {
				return null;
			}
			return new ResolvedVideo( 'vimeo', $this->vimeo_embed_url( $id ), $poster, $is_background );
		}

		if ( $this->is_direct_video_file( $raw ) ) {
			return new ResolvedVideo( 'file', esc_url_raw( $raw ), $poster, $is_background );
		}

		return null;
	}

	/**
	 * Extracts the video id from a YouTube URL, if present.
	 *
	 * @param string $url YouTube URL.
	 * @return ?string
	 */
	private function extract_youtube_id( string $url ): ?string {
		if ( preg_match( '#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		return null;
	}

	/**
	 * Extracts the video id from a Vimeo URL, if present.
	 *
	 * @param string $url Vimeo URL.
	 * @return ?string
	 */
	private function extract_vimeo_id( string $url ): ?string {
		if ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ) {
			return $m[1];
		}
		return null;
	}

	/**
	 * Builds a privacy-enhanced, autoplaying YouTube embed URL.
	 *
	 * @param string $id YouTube video id.
	 * @return string
	 */
	private function youtube_embed_url( string $id ): string {
		$id = rawurlencode( $id );
		return "https://www.youtube-nocookie.com/embed/{$id}?autoplay=1&mute=1&loop=1&playlist={$id}&controls=0&playsinline=1&rel=0&modestbranding=1&iv_load_policy=3&disablekb=1&fs=0";
	}

	/**
	 * Builds an autoplaying Vimeo embed URL.
	 *
	 * @param string $id Vimeo video id.
	 * @return string
	 */
	private function vimeo_embed_url( string $id ): string {
		$id = rawurlencode( $id );
		return "https://player.vimeo.com/video/{$id}?autoplay=1&muted=1&loop=1&background=1";
	}

	/**
	 * Whether a URL points directly at a supported video file.
	 *
	 * @param string $url URL to check.
	 * @return bool
	 */
	private function is_direct_video_file( string $url ): bool {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return (bool) preg_match( '/\.(mp4|webm)$/i', $path );
	}

	/**
	 * Validates a pasted URL at save time (§6.2, §13). Returns an error
	 * string, or null when the URL is acceptable.
	 *
	 * @param string $raw Raw pasted video URL.
	 * @return ?string
	 */
	public function validate_link( string $raw ): ?string {
		$raw = trim( $raw );

		if ( '' === $raw ) {
			return null;
		}

		if ( ! preg_match( '#^https://#i', $raw ) ) {
			return __( 'Video links must start with https:// — YouTube, Vimeo, or a direct .mp4/.webm file.', 'maapkathi' );
		}

		$resolved = $this->resolve_link( $raw, null, false );

		if ( null === $resolved ) {
			return __( 'Unsupported video link. Accepted: YouTube, Vimeo, or a direct https:// .mp4/.webm URL.', 'maapkathi' );
		}

		return null;
	}
}
