<?php
/**
 * 404 template — no post/page matched the request.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="mk-container mk-section" style="text-align:center">
	<h1><?php esc_html_e( 'Page not found', 'maapkathi' ); ?></h1>
	<p><a class="mk-btn mk-btn--accent" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'maapkathi' ); ?></a></p>
</div>
<?php
get_footer();
