<?php
/**
 * Opens the HTML document: <head>, site header, and the #main region.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mk-skip-link" href="#mk-main"><?php esc_html_e( 'Skip to content', 'maapkathi' ); ?></a>
<?php get_template_part( 'parts/header' ); ?>
<main id="mk-main">
