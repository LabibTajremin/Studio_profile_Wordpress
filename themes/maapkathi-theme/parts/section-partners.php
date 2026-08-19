<?php
/**
 * "Our Partners" logo band (FR-10).
 *
 * Sits directly above the footer. Renders nothing at all when no partner
 * logos are configured — no empty band, no heading floating over nothing
 * (FR-10.9).
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_partners = mk_setting( 'section_partners_enabled', true ) ? mk_content( 'partners' ) : array();

if ( ! $mk_partners ) {
	return;
}

$mk_partners_layout   = (string) mk_setting( 'partners_layout', 'grid' );
$mk_partners_per_row  = (int) mk_setting( 'partners_per_row', 5 );
$mk_partners_logo_h   = (int) mk_setting( 'partners_max_logo_h', 48 );
$mk_partners_grey     = (bool) mk_setting( 'partners_greyscale', true );
$mk_partners_speed    = (int) mk_setting( 'partners_speed', 40 );
$mk_partners_bg       = (string) mk_setting( 'partners_background', 'none' );
$mk_partners_heading  = mk_text( 'home_partners_heading' );
$mk_partners_subtitle = mk_text( 'home_partners_subtitle' );
$mk_show_title        = (bool) mk_setting( 'partners_show_title', true );

$mk_partner_classes = array( 'mk-section', 'mk-partners' );
if ( 'none' !== $mk_partners_bg ) {
	$mk_partner_classes[] = 'mk-partners--bg-' . $mk_partners_bg;
}
if ( $mk_partners_grey ) {
	$mk_partner_classes[] = 'mk-partners--greyscale';
}

/**
 * Renders one partner tile.
 *
 * A partner without a website renders as a plain image rather than as an
 * empty anchor, which would otherwise be a keyboard tab stop that does
 * nothing (FR-10.8).
 *
 * @param \WP_Post $partner Partner post.
 * @return void
 */
$mk_render_partner = static function ( \WP_Post $partner ): void {
	$name = get_the_title( $partner );
	$alt  = mk_meta( $partner->ID, 'mk_alt_text' );
	$url  = mk_meta( $partner->ID, 'mk_website' );
	$logo = get_the_post_thumbnail(
		$partner,
		'medium',
		array(
			'class'    => 'mk-partners__logo',
			'loading'  => 'lazy',
			'decoding' => 'async',
			// Alt text falls back to the partner's name, so a logo is never
			// announced as an unnamed image.
			'alt'      => '' !== $alt ? $alt : $name,
		)
	);
	?>
	<li class="mk-partners__item">
		<?php if ( $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $name ); ?>">
				<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by get_the_post_thumbnail(). ?>
			</a>
		<?php else : ?>
			<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by get_the_post_thumbnail(). ?>
		<?php endif; ?>
	</li>
	<?php
};
?>
<section
	id="partners"
	class="<?php echo esc_attr( implode( ' ', $mk_partner_classes ) ); ?>"
	style="--mk-partners-logo-h: <?php echo esc_attr( (string) $mk_partners_logo_h ); ?>px; --mk-partners-per-row: <?php echo esc_attr( (string) $mk_partners_per_row ); ?>; --mk-partners-speed: <?php echo esc_attr( (string) $mk_partners_speed ); ?>s;"
	data-scroll-reveal
>
	<div class="mk-container">
		<?php if ( $mk_show_title && $mk_partners_heading ) : ?>
			<h2 class="mk-section__heading"><?php echo esc_html( $mk_partners_heading ); ?></h2>
		<?php endif; ?>
		<?php if ( $mk_partners_subtitle ) : ?>
			<p class="mk-section__subtitle"><?php echo esc_html( $mk_partners_subtitle ); ?></p>
		<?php endif; ?>

		<?php if ( 'marquee' === $mk_partners_layout ) : ?>
			<?php
			// The track holds the list twice so the animation can translate
			// exactly one copy's width and loop with no visible jump. The
			// duplicate is hidden from assistive tech so the logos are not
			// announced a second time.
			?>
			<div class="mk-partners__marquee" data-mk-marquee>
				<ul class="mk-partners__track">
					<?php foreach ( $mk_partners as $mk_partner ) : ?>
						<?php $mk_render_partner( $mk_partner ); ?>
					<?php endforeach; ?>
				</ul>
				<ul class="mk-partners__track" aria-hidden="true">
					<?php foreach ( $mk_partners as $mk_partner ) : ?>
						<?php $mk_render_partner( $mk_partner ); ?>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php else : ?>
			<ul class="mk-partners__grid">
				<?php foreach ( $mk_partners as $mk_partner ) : ?>
					<?php $mk_render_partner( $mk_partner ); ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
