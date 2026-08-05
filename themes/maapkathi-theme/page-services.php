<?php
/**
 * Template Name: Services
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$parents = new WP_Query(
	array(
		'post_type'      => 'mk_service',
		'post_parent'    => 0,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);
?>
<div class="mk-container mk-section">
	<h1><?php the_title(); ?></h1>

	<?php while ( $parents->have_posts() ) : $parents->the_post(); ?>
		<section class="mk-service-group" data-scroll-reveal>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<p><?php the_excerpt(); ?></p>

			<?php
			$children = get_posts(
				array(
					'post_type'      => 'mk_service',
					'post_parent'    => get_the_ID(),
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				)
			);
			?>
			<?php if ( $children ) : ?>
				<div class="mk-grid mk-grid--services">
					<?php foreach ( $children as $child ) : ?>
						<a class="mk-card" href="<?php echo esc_url( get_permalink( $child ) ); ?>">
							<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $child ) ); ?></h3>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endwhile; ?>
	<?php wp_reset_postdata(); ?>
</div>
<?php
get_footer();
