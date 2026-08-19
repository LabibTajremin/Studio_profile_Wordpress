<?php
/**
 * Homepage section: awards.
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
$mk_data = mk_setting( 'section_awards_enabled', true ) ? mk_content( 'awards' ) : array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-awards" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<ul class="mk-awards__list">
			<?php foreach ( $mk_data as $award ) : ?>
				<?php
				$issuer     = mk_meta( $award->ID, 'mk_issuer' );
				$award_year = mk_meta( $award->ID, 'mk_year' );
				$award_link = mk_meta( $award->ID, 'mk_link' );
				?>
				<li class="mk-awards__item">
					<span class="mk-awards__title">
						<?php if ( $award_link ) : ?>
							<a href="<?php echo esc_url( $award_link ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( get_the_title( $award ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( get_the_title( $award ) ); ?>
						<?php endif; ?>
					</span>
					<span class="mk-awards__meta"><?php echo esc_html( trim( $issuer . ( $issuer && $award_year ? ' · ' : '' ) . $award_year ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
