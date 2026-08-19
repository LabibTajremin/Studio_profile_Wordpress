<?php
/**
 * Homepage section: team preview.
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
$mk_data = mk_setting( 'section_team_enabled', true ) ? mk_content( 'members', 4 ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-team-preview" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-grid mk-grid--team">
			<?php foreach ( $mk_data as $member ) : ?>
				<div class="mk-card mk-card--member">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $member ) ) : ?>
							<?php echo get_the_post_thumbnail( $member, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $member ), 600, 800 ) ); ?>" width="600" height="800" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $member ) ); ?></h3>
					<p class="mk-card__role"><?php echo esc_html( mk_meta( $member->ID, 'mk_role_title' ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="mk-section__more">
			<a class="mk-link-more" href="<?php echo esc_url( home_url( '/team/' ) ); ?>"><?php esc_html_e( 'Meet the team', 'maapkathi' ); ?></a>
		</p>
	</div>
</section>
