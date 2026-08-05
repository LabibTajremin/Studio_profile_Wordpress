<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="mk-container mk-section">
	<?php if ( have_posts() ) : ?>
		<div class="mk-grid mk-grid--posts">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'mk-card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="mk-card__media"><?php the_post_thumbnail( 'medium_large' ); ?></a>
					<?php endif; ?>
					<h2 class="mk-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="mk-card__excerpt"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'maapkathi' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
