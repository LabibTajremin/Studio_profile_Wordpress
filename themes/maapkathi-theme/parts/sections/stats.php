<?php
/**
 * Homepage section: stats band.
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
$mk_data = mk_setting( 'section_stats_enabled', true ) ? mk_content( 'stats' ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-stats-band" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id, 'h2', 'mk-section__heading mk-section__heading--on-accent' ); ?>
		<div class="mk-grid mk-grid--stats">
			<?php foreach ( $mk_data as $stat ) : ?>
				<?php
				$stat_value    = (float) mk_meta( $stat->ID, 'mk_value_number' );
				$stat_is_whole = (float) (int) $stat_value === $stat_value;
				$stat_display  = $stat_is_whole ? (string) (int) $stat_value : (string) $stat_value;
				?>
				<div class="mk-stat">
					<span class="mk-stat__value" data-count-to="<?php echo esc_attr( (string) $stat_value ); ?>">
						<span class="mk-stat__value-number"><?php echo esc_html( $stat_display ); ?></span><span class="mk-stat__value-suffix"><?php echo esc_html( mk_meta( $stat->ID, 'mk_suffix' ) ); ?></span>
					</span>
					<span class="mk-stat__label"><?php echo esc_html( get_the_title( $stat ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
