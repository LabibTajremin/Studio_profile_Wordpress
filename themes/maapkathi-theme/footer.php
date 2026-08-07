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
<?php get_template_part( 'parts/footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
