<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_settings = get_option( 'mk_site_settings', array() );
if ( empty( $site_settings['blog_enabled'] ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( '404' );
	return;
}

get_header();
?>
<div class="mk-container mk-section">
	<h1><?php esc_html_e( 'Blog', 'maapkathi' ); ?></h1>
	<div class="mk-grid mk-grid--posts" data-scroll-reveal>
		<?php while ( have_posts() ) : the_post(); ?>
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
</div>
<?php
get_footer();
