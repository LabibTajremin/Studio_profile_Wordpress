<?php
/**
 * Archive template for the mk_project custom post type.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="mk-container mk-section">
	<h1 class="mk-page-title"><?php mk_the_text( 'work_archive_heading' ); ?></h1>

	<?php if ( have_posts() ) : ?>
		<div class="mk-grid mk-grid--projects" data-scroll-reveal>
			<?php
			while ( have_posts() ) :
				the_post();
				$mk_summary = mk_meta( get_the_ID(), 'mk_summary' );
				?>
				<a class="mk-card mk-card--project" href="<?php the_permalink(); ?>">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title(), 1200, 1500 ) ); ?>" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h2 class="mk-card__title"><?php the_title(); ?></h2>
					<?php if ( $mk_summary ) : ?>
						<p class="mk-card__excerpt"><?php echo esc_html( wp_trim_words( $mk_summary, 20 ) ); ?></p>
					<?php endif; ?>
				</a>
			<?php endwhile; ?>
		</div>

		<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
	<?php else : ?>
		<p class="mk-empty-state"><?php mk_the_text( 'work_empty_state' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
