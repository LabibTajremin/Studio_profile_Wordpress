<?php
/**
 * Homepage section: portfolio categories.
 *
 * Rendered once per instance by front-page.php, which passes the
 * instance's id. Nothing here may assume the section appears only once on
 * the page (FR-03.5), so the anchor and every DOM id are derived from
 * $mk_id rather than hardcoded.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables supplied by front-page.php.
 *
 * @var string $mk_id Section instance id.
 */
$mk_data = mk_setting( 'section_categories_enabled', true ) ? mk_content( 'project_categories' ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-categories" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-grid mk-grid--categories">
			<?php foreach ( $mk_data as $category_term ) : ?>
				<a class="mk-card mk-card--category" href="<?php echo esc_url( get_term_link( $category_term ) ); ?>">
					<h3 class="mk-card__title"><?php echo esc_html( $category_term->name ); ?></h3>
					<span class="mk-card__count">
						<?php
						printf(
							/* translators: %d: number of projects in this category. */
							esc_html( _n( '%d project', '%d projects', (int) $category_term->count, 'maapkathi' ) ),
							(int) $category_term->count
						);
						?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
