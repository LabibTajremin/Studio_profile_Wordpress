<?php
/**
 * Homepage section: what we stand for.
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
$mk_data = mk_setting( 'section_values_enabled', true ) ? mk_content( 'values' ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-values" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-grid mk-grid--values">
			<?php foreach ( $mk_data as $value ) : ?>
				<div class="mk-card mk-card--value">
					<?php $icon = mk_item_icon( $value->ID ); ?>
					<?php if ( $icon ) : ?>
						<span class="mk-card__icon" aria-hidden="true"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by IconRenderer, escaped at source. ?></span>
					<?php endif; ?>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $value ) ); ?></h3>
					<p><?php echo esc_html( wp_strip_all_tags( $value->post_content ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
