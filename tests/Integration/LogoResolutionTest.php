<?php
declare( strict_types = 1 );

namespace Maapkathi\Core\Tests\Integration;

use Maapkathi\Core\Support\Content;
use Maapkathi\Core\Support\Logos;
use WP_UnitTestCase;

/**
 * A client's featured image is a photograph of the work, not a logo, and
 * reading it for the client wall put an interior shot among the logos on
 * the live site. Clients therefore carry a dedicated logo field, and these
 * tests pin down which field wins where — including the asymmetry between
 * clients and partners, which is deliberate: partners have always been
 * logo-only records, so their featured image still counts as a logo and
 * existing sites keep rendering unchanged.
 */
final class LogoResolutionTest extends WP_UnitTestCase {

	/**
	 * Creates an attachment post with no file behind it.
	 *
	 * These tests only ever compare ids and check existence, so no real
	 * upload is needed — and not needing one keeps them independent of the
	 * image-editing extensions available on the runner.
	 *
	 * @return int Attachment id.
	 */
	private function make_attachment(): int {
		return $this->factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/png',
			)
		);
	}

	/**
	 * Points a record's featured image at an attachment.
	 *
	 * Written straight to the meta key rather than through
	 * set_post_thumbnail(), which refuses an attachment it cannot render
	 * an <img> for — get_post_thumbnail_id(), which is what the code under
	 * test reads, only ever looks at this key.
	 *
	 * @param int $post_id       Record to set the featured image on.
	 * @param int $attachment_id Attachment to point it at.
	 * @return void
	 */
	private function set_featured( int $post_id, int $attachment_id ): void {
		update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
	}

	/**
	 * Creates a published record of one of the plugin's types.
	 *
	 * @param string $post_type Post type slug.
	 * @return int Post id.
	 */
	private function make_record( string $post_type ): int {
		return $this->factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => 'Acme',
			)
		);
	}

	public function test_the_logo_field_wins_over_the_featured_image(): void {
		$logo     = $this->make_attachment();
		$featured = $this->make_attachment();
		$client   = $this->make_record( 'mk_client' );

		$this->set_featured( $client, $featured );
		update_post_meta( $client, Logos::META_KEY, $logo );

		$this->assertSame( $logo, Logos::id( $client ) );
		$this->assertTrue( Logos::has_dedicated( $client ) );
	}

	public function test_the_featured_image_is_only_a_fallback(): void {
		$featured = $this->make_attachment();
		$client   = $this->make_record( 'mk_client' );

		$this->set_featured( $client, $featured );

		$this->assertSame( $featured, Logos::id( $client ) );
		$this->assertTrue( Logos::has( $client ) );
		// The distinction the client wall relies on: there is an image, but
		// it is not a logo.
		$this->assertFalse( Logos::has_dedicated( $client ) );
	}

	public function test_a_record_with_neither_field_has_no_logo(): void {
		$client = $this->make_record( 'mk_client' );

		$this->assertSame( 0, Logos::id( $client ) );
		$this->assertFalse( Logos::has( $client ) );
		$this->assertSame( '', Logos::image( $client ) );
	}

	public function test_a_deleted_logo_attachment_falls_back(): void {
		$logo     = $this->make_attachment();
		$featured = $this->make_attachment();
		$client   = $this->make_record( 'mk_client' );

		$this->set_featured( $client, $featured );
		update_post_meta( $client, Logos::META_KEY, $logo );
		wp_delete_post( $logo, true );

		// A stale id must not produce a broken image.
		$this->assertSame( $featured, Logos::id( $client ) );
		$this->assertFalse( Logos::has_dedicated( $client ) );
	}

	public function test_the_band_skips_a_client_that_only_has_a_featured_image(): void {
		$client = $this->make_record( 'mk_client' );
		$this->set_featured( $client, $this->make_attachment() );

		$this->assertSame( array(), Content::logo_wall( 'clients' ) );
	}

	public function test_the_band_includes_a_client_once_a_logo_is_uploaded(): void {
		$client = $this->make_record( 'mk_client' );
		update_post_meta( $client, Logos::META_KEY, $this->make_attachment() );

		$this->assertSame(
			array( $client ),
			wp_list_pluck( Content::logo_wall( 'clients' ), 'ID' )
		);
	}

	public function test_the_band_still_accepts_a_partners_featured_image(): void {
		$partner = $this->make_record( 'mk_partner' );
		$this->set_featured( $partner, $this->make_attachment() );

		$this->assertSame(
			array( $partner ),
			wp_list_pluck( Content::logo_wall( 'partners' ), 'ID' )
		);
	}

	public function test_both_sources_are_merged(): void {
		$partner = $this->make_record( 'mk_partner' );
		$this->set_featured( $partner, $this->make_attachment() );

		$client = $this->make_record( 'mk_client' );
		update_post_meta( $client, Logos::META_KEY, $this->make_attachment() );

		$ids = wp_list_pluck( Content::logo_wall( 'both' ), 'ID' );

		$this->assertContains( $partner, $ids );
		$this->assertContains( $client, $ids );
		$this->assertCount( 2, $ids );
	}
}
