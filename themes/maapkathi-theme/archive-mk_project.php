<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="mk-container mk-section">
	<h1><?php esc_html_e( 'Work', 'maapkathi' ); ?></h1>

	<div class="mk-grid mk-grid--projects" data-scroll-reveal>
		<?php while ( have_posts() ) : the_post(); ?>
			<a class="mk-card mk-card--project" href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="mk-card__media"><?php the_post_thumbnail( 'large' ); ?></div>
				<?php endif; ?>
				<h2 class="mk-card__title"><?php the_title(); ?></h2>
				<?php $summary = get_post_meta( get_the_ID(), 'mk_summary', true ); ?>
				<?php if ( $summary ) : ?><p class="mk-card__excerpt"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
			</a>
		<?php endwhile; ?>
	</div>

	<?php the_posts_pagination(); ?>
</div>
<?php
get_footer();
