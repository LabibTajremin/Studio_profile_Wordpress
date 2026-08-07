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
	$categories  = get_the_terms( $post_id, 'mk_project_category' );
	?>
	<article class="mk-project-hero">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mk-project-hero__media"><?php the_post_thumbnail( 'full' ); ?></div>
		<?php endif; ?>
	</article>

	<div class="mk-container mk-section mk-project-detail">
		<div class="mk-project-detail__main">
			<h1><?php the_title(); ?></h1>
			<?php $summary = get_post_meta( $post_id, 'mk_summary', true ); ?>
			<?php
			if ( $summary ) :
				?>
				<p class="mk-lede"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
			<div class="mk-prose"><?php the_content(); ?></div>

			<?php if ( ! empty( $gallery_ids ) ) : ?>
				<div class="mk-gallery" data-lightbox-gallery>
					<?php foreach ( $gallery_ids as $attachment_id ) : ?>
						<a href="<?php echo esc_url( wp_get_attachment_image_url( $attachment_id, 'full' ) ?: '' ); ?>" data-lightbox-item data-alt="<?php echo esc_attr( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ); ?>">
							<?php echo wp_get_attachment_image( $attachment_id, 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<aside class="mk-project-detail__spec">
			<dl>
				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
					<dt><?php esc_html_e( 'Category', 'maapkathi' ); ?></dt><dd><?php echo esc_html( implode( ', ', wp_list_pluck( $categories, 'name' ) ) ); ?></dd>
				<?php endif; ?>
				<?php $client = get_post_meta( $post_id, 'mk_client_name', true ); ?>
				<?php
				if ( $client ) :
					?>
					<dt><?php esc_html_e( 'Client', 'maapkathi' ); ?></dt><dd><?php echo esc_html( $client ); ?></dd><?php endif; ?>
				<?php $location = get_post_meta( $post_id, 'mk_location', true ); ?>
				<?php
				if ( $location ) :
					?>
					<dt><?php esc_html_e( 'Location', 'maapkathi' ); ?></dt><dd><?php echo esc_html( $location ); ?></dd><?php endif; ?>
				<?php $area = get_post_meta( $post_id, 'mk_area_sqft', true ); ?>
				<?php
				if ( $area ) :
					?>
					<dt><?php esc_html_e( 'Area', 'maapkathi' ); ?></dt><dd><?php echo esc_html( $area ); ?> sq ft</dd><?php endif; ?>
				<?php $completed = get_post_meta( $post_id, 'mk_completed_at', true ); ?>
				<?php
				if ( $completed ) :
					?>
					<dt><?php esc_html_e( 'Year', 'maapkathi' ); ?></dt><dd><?php echo esc_html( gmdate( 'Y', strtotime( $completed ) ) ); ?></dd><?php endif; ?>
			</dl>
		</aside>
	</div>
	<?php
endwhile;
get_footer();
