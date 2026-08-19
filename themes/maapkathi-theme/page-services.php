<?php
/**
 * Template Name: Services
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mk_parents = mk_content( 'top_level_services' );
?>
<div class="mk-container mk-section">
	<?php mk_the_section_heading( 'services_page', 'h1', 'mk-page-title' ); ?>
	<?php $mk_intro = mk_text( 'services_intro' ); ?>
	<?php if ( $mk_intro ) : ?>
		<p class="mk-lede"><?php echo esc_html( $mk_intro ); ?></p>
	<?php endif; ?>

	<?php if ( $mk_parents ) : ?>
		<?php foreach ( $mk_parents as $mk_parent ) : ?>
			<section class="mk-service-group mk-services--icon-<?php echo esc_attr( (string) mk_theme_setting( 'services_icon_position', 'beside' ) ); ?>" data-scroll-reveal>
				<h2 class="mk-service-group__title">
					<a href="<?php echo esc_url( (string) get_permalink( $mk_parent ) ); ?>"><?php echo esc_html( get_the_title( $mk_parent ) ); ?></a>
				</h2>

				<?php $mk_body = wp_strip_all_tags( $mk_parent->post_content ); ?>
				<?php if ( $mk_body ) : ?>
					<p class="mk-service-group__intro"><?php echo esc_html( wp_trim_words( $mk_body, 40 ) ); ?></p>
				<?php endif; ?>

				<?php $mk_children = mk_content( 'child_services', $mk_parent->ID ); ?>
				<?php if ( $mk_children ) : ?>
					<div class="mk-grid mk-grid--services">
						<?php foreach ( $mk_children as $mk_child ) : ?>
							<a class="mk-card mk-card--service" href="<?php echo esc_url( (string) get_permalink( $mk_child ) ); ?>">
								<?php $mk_icon = mk_item_icon( $mk_child->ID ); ?>
								<div class="mk-card__head">
									<?php if ( $mk_icon ) : ?>
										<span class="mk-card__icon" aria-hidden="true"><?php echo $mk_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by IconRenderer, escaped at source. ?></span>
									<?php endif; ?>
									<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $mk_child ) ); ?></h3>
								</div>
								<p class="mk-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $mk_child->post_content ), 18 ) ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="mk-empty-state"><?php esc_html_e( 'Services are being finalised.', 'maapkathi' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
