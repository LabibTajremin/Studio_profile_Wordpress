<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Unit\Video;

use Maapkathi\Core\Video\VideoResolver;
use PHPUnit\Framework\TestCase;

/**
 * §6.2/§13 matrix: upload vs. link, all three playable kinds, and the
 * rejection/SSRF-adjacent cases that must never resolve.
 */
final class VideoResolverTest extends TestCase {

	private VideoResolver $resolver;

	protected function setUp(): void {
		$this->resolver = new VideoResolver();
	}

	public function test_upload_source_returns_file_kind(): void {
		$video = $this->resolver->resolve(
			array(
				'video_source'     => 'upload',
				'video_upload_url' => 'https://example.com/wp-content/uploads/maapkathi/2026/07/abc.mp4',
			)
		);
		$this->assertSame( 'file', $video->kind );
	}

	/**
	 * @dataProvider youtubeUrls
	 */
	public function test_youtube_forms_resolve_with_correct_id( string $url ): void {
		$video = $this->resolver->resolve( array( 'video_source' => 'link', 'video_url' => $url ) );
		$this->assertNotNull( $video );
		$this->assertSame( 'youtube', $video->kind );
		$this->assertStringContainsString( 'youtube-nocookie.com/embed/dQw4w9WgXcQ', $video->src );
		$this->assertStringContainsString( 'autoplay=1', $video->src );
		$this->assertStringContainsString( 'mute=1', $video->src );
		$this->assertStringContainsString( 'loop=1', $video->src );
		$this->assertStringContainsString( 'playlist=dQw4w9WgXcQ', $video->src );
	}

	public static function youtubeUrls(): array {
		return array(
			array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ),
			array( 'https://youtu.be/dQw4w9WgXcQ' ),
			array( 'https://www.youtube.com/embed/dQw4w9WgXcQ' ),
			array( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=share' ),
		);
	}

	/**
	 * @dataProvider vimeoUrls
	 */
	public function test_vimeo_forms_resolve( string $url ): void {
		$video = $this->resolver->resolve( array( 'video_source' => 'link', 'video_url' => $url ) );
		$this->assertNotNull( $video );
		$this->assertSame( 'vimeo', $video->kind );
		$this->assertStringContainsString( 'player.vimeo.com/video/12345678', $video->src );
		$this->assertStringContainsString( 'autoplay=1', $video->src );
		$this->assertStringContainsString( 'muted=1', $video->src );
		$this->assertStringContainsString( 'loop=1', $video->src );
		$this->assertStringContainsString( 'background=1', $video->src );
	}

	public static function vimeoUrls(): array {
		return array(
			array( 'https://vimeo.com/12345678' ),
			array( 'https://player.vimeo.com/video/12345678' ),
		);
	}

	public function test_direct_file_url_resolves(): void {
		$video = $this->resolver->resolve( array( 'video_source' => 'link', 'video_url' => 'https://cdn.example.com/clip.mp4' ) );
		$this->assertSame( 'file', $video->kind );
	}

	/**
	 * @dataProvider rejectedUrls
	 */
	public function test_rejected_urls_resolve_to_null( string $url ): void {
		$video = $this->resolver->resolve( array( 'video_source' => 'link', 'video_url' => $url ) );
		$this->assertNull( $video );
	}

	public static function rejectedUrls(): array {
		return array(
			'http scheme'                 => array( 'http://youtube.com/watch?v=dQw4w9WgXcQ' ),
			'javascript scheme'           => array( 'javascript:alert(1)' ),
			'data scheme'                 => array( 'data:text/html,hi' ),
			'protocol relative'           => array( '//youtube.com/watch?v=dQw4w9WgXcQ' ),
			'host smuggling suffix'       => array( 'https://evil.com/youtube.com/watch?v=dQw4w9WgXcQ' ),
			'host smuggling subdomain'    => array( 'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ' ),
			'unknown host'                => array( 'https://randomsite.com/video.mp4x' ),
			'non video extension'         => array( 'https://cdn.example.com/clip.mov' ),
		);
	}

	public function test_validate_link_rejects_unsupported_url(): void {
		$error = $this->resolver->validate_link( 'https://randomsite.com/notavideo' );
		$this->assertNotNull( $error );
	}

	public function test_validate_link_accepts_supported_url(): void {
		$error = $this->resolver->validate_link( 'https://vimeo.com/12345678' );
		$this->assertNull( $error );
	}

	public function test_validate_link_accepts_empty_string_as_no_link(): void {
		$this->assertNull( $this->resolver->validate_link( '' ) );
	}
}
