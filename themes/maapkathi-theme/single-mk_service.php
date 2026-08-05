<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
while ( have_posts() ) :
	the_post();
	$post_id     = get_the_ID();
	$gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, 'mk_gallery', true ) ) ) );
	?>
	<article class="mk-project-hero">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mk-project-hero__media"><?php the_post_thumbnail( 'full' ); ?></div>
		<?php endif; ?>
	</article>

	<div class="mk-container mk-section">
		<h1><?php the_title(); ?></h1>
		<div class="mk-prose"><?php the_content(); ?></div>

		<?php if ( ! empty( $gallery_ids ) ) : ?>
			<div class="mk-gallery" data-lightbox-gallery>
				<?php foreach ( $gallery_ids as $attachment_id ) : ?>
					<a href="<?php echo esc_url( wp_get_attachment_image_url( $attachment_id, 'full' ) ?: '' ); ?>" data-lightbox-item>
						<?php echo wp_get_attachment_image( $attachment_id, 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
