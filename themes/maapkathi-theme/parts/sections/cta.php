<?php
/**
 * Homepage section: closing call to action.
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
// The band is nothing but its heading and button, so it renders only when
// that copy exists — an empty accent slab helps nobody.
$mk_data = '' !== trim( mk_text( 'home_cta_heading' ) ) || '' !== trim( mk_text( 'home_cta_button_label' ) );

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-cta-band">
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id, 'h2', 'mk-section__heading mk-section__heading--on-accent' ); ?>
		<a class="mk-btn mk-btn--on-accent" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
			<?php mk_the_text( 'home_cta_button_label' ); ?>
		</a>
	</div>
</section>
