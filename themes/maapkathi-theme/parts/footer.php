<?php
/**
 * Site footer: brand, footer nav, contact/NAP details, and Organization JSON-LD.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_studio_name = mk_setting( 'studio_name', get_bloginfo( 'name' ) );
$mk_email       = mk_setting( 'contact_email' );
$mk_phone       = mk_setting( 'contact_phone' );
$mk_address     = mk_setting( 'address' );
$mk_socials     = (array) mk_setting( 'socials', array() );
$mk_footer_note = mk_text( 'footer_note' );

// FR-08/FR-09: which footer layout renders, and whether the copyright bar
// keeps a divider above it (off by default, so footer and copyright read as
// one continuous block).
$mk_footer       = mk_footer_settings();
$mk_footer_style = (string) $mk_footer['style'];
$mk_show_divider = ! empty( $mk_footer['show_divider'] );

// Organization JSON-LD (§12). NAP details are included only when set, so
// we never publish an incomplete/misleading business record.
$mk_org = array(
	'@context'    => 'https://schema.org',
	'@type'       => 'Organization',
	'name'        => $mk_studio_name,
	'description' => mk_setting( 'tagline', get_bloginfo( 'description' ) ),
	'url'         => home_url( '/' ),
);

$mk_logo_for_schema = mk_logo_light_url() ? mk_logo_light_url() : mk_logo_dark_url();
if ( $mk_logo_for_schema ) {
	$mk_org['logo'] = $mk_logo_for_schema;
}

if ( $mk_email || $mk_phone ) {
	$mk_contact_point = array(
		'@type'       => 'ContactPoint',
		'contactType' => 'customer service',
	);
	if ( $mk_email ) {
		$mk_contact_point['email'] = $mk_email;
	}
	if ( $mk_phone ) {
		$mk_contact_point['telephone'] = $mk_phone;
	}
	$mk_org['contactPoint'] = $mk_contact_point;
}

if ( $mk_address ) {
	$mk_org['address'] = array(
		'@type'         => 'PostalAddress',
		'streetAddress' => $mk_address,
	);
}

if ( $mk_socials ) {
	$mk_org['sameAs'] = array_values( array_filter( $mk_socials ) );
}
?>
<footer class="mk-site-footer mk-site-footer--<?php echo esc_attr( $mk_footer_style ); ?>" data-logo-mode="<?php echo esc_attr( (string) $mk_footer['logo_mode'] ); ?>">
	<?php
	// require rather than get_template_part() so the partial keeps this
	// file's local scope; get_theme_file_path() still lets a child theme
	// override either layout.
	require get_theme_file_path( 'modern' === $mk_footer_style ? 'parts/footer-modern.php' : 'parts/footer-classic.php' );
	?>

	<?php
	// FR-09: the copyright bar keeps its existing content and markup. The
	// only change is that it now sits on the same --footer-bg as the footer
	// above it, with no divider, so the two read as one block.
	?>
	<div class="mk-site-footer__bottom<?php echo $mk_show_divider ? ' mk-site-footer__bottom--divided' : ''; ?>">
		<p class="mk-copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $mk_studio_name ); ?>
		</p>
	</div>

	<script type="application/ld+json"><?php echo wp_json_encode( $mk_org ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
</footer>
