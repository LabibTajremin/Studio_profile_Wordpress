<?php
/**
 * Homepage section: testimonials.
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
$mk_data = mk_setting( 'section_testimonials_enabled', true ) ? mk_content( 'testimonials' ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-testimonials" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-grid mk-grid--testimonials">
			<?php foreach ( $mk_data as $testimonial ) : ?>
				<?php
				$rating      = (int) mk_meta( $testimonial->ID, 'mk_rating', '0' );
				$author      = mk_meta( $testimonial->ID, 'mk_author_name', get_the_title( $testimonial ) );
				$author_role = mk_meta( $testimonial->ID, 'mk_author_role' );
				$firm        = mk_meta( $testimonial->ID, 'mk_company' );
				?>
				<figure class="mk-card mk-card--testimonial">
					<?php if ( $rating > 0 ) : ?>
						<div class="mk-rating" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating out of five. */ __( '%d out of 5 stars', 'maapkathi' ), $rating ) ); ?>">
							<?php echo esc_html( str_repeat( '★', min( 5, $rating ) ) . str_repeat( '☆', max( 0, 5 - $rating ) ) ); ?>
						</div>
					<?php endif; ?>
					<blockquote><?php echo esc_html( mk_meta( $testimonial->ID, 'mk_quote' ) ); ?></blockquote>
					<figcaption>
						<strong><?php echo esc_html( $author ); ?></strong>
						<?php if ( $author_role || $firm ) : ?>
							<span><?php echo esc_html( trim( $author_role . ( $author_role && $firm ? ', ' : '' ) . $firm ) ); ?></span>
						<?php endif; ?>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
