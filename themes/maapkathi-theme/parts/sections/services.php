<?php
/**
 * Homepage section: services.
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
$mk_data = mk_setting( 'section_services_enabled', true ) ? mk_content( 'top_level_services', 4 ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-services mk-services--icon-<?php echo esc_attr( (string) mk_theme_setting( 'services_icon_position', 'beside' ) ); ?>" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-grid mk-grid--services">
			<?php foreach ( $mk_data as $service ) : ?>
				<a class="mk-card mk-card--service" href="<?php echo esc_url( (string) get_permalink( $service ) ); ?>">
					<?php $icon = mk_item_icon( $service->ID ); ?>
					<?php
					// The icon and the title share a row so the title sits
					// beside the icon rather than under it; the description
					// then spans the full card width below both.
					?>
					<div class="mk-card__head">
						<?php if ( $icon ) : ?>
							<span class="mk-card__icon" aria-hidden="true"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by IconRenderer, escaped at source. ?></span>
						<?php endif; ?>
						<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $service ) ); ?></h3>
					</div>
					<p class="mk-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $service->post_content ), 18 ) ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
