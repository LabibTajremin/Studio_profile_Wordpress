<?php
/**
 * Generic page template.
 *
 * Without this file WordPress falls back to index.php for any Page that has
 * no more specific template, and index.php runs the blog loop — so a plain
 * Page rendered as a grid of post excerpts instead of its own content. That
 * also made the theme unusable with Elementor, which edits Pages.
 *
 * Page builder content is detected and given the full width with no theme
 * container, because a builder lays out its own sections edge to edge and a
 * wrapper with padding would fight it. Hand-written content keeps the
 * container and the page title.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$mk_is_builder = mk_page_is_builder_built( get_the_ID() );
	?>
	<?php if ( $mk_is_builder ) : ?>
		<div class="mk-builder-page">
			<?php the_content(); ?>
		</div>
	<?php else : ?>
		<div class="mk-container mk-section">
			<?php if ( ! is_front_page() ) : ?>
				<h1 class="mk-page-title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="mk-prose">
				<?php the_content(); ?>
			</div>
			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="mk-page-links">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
