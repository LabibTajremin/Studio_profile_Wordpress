<?php
/**
 * Closes the #main content region and renders the site footer.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<?php
// FR-10.2: the partner band's default home is the last thing before the
// footer, after every other section including any CTA or contact block.
// Rendering it here rather than at the end of front-page.php also puts it
// on the inner pages, which is where "directly above the footer" has to
// mean the same thing.
get_template_part( 'parts/section-partners' );
?>
<?php get_template_part( 'parts/footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
