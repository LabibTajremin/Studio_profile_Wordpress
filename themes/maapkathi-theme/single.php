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
while ( have_posts() ) :
	the_post();
	?>
	<article class="mk-container mk-section">
		<h1><?php the_title(); ?></h1>
		<?php if ( has_post_thumbnail() ) : ?><div class="mk-post-cover"><?php the_post_thumbnail( 'full' ); ?></div><?php endif; ?>
		<div class="mk-prose"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;
get_footer();
