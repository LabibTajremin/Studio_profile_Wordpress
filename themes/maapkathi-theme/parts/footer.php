<?php
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

// Organization JSON-LD (§12). NAP details are included only when set, so
// we never publish an incomplete/misleading business record.
$mk_org = array(
	'@context'    => 'https://schema.org',
	'@type'       => 'Organization',
	'name'        => $mk_studio_name,
	'description' => mk_setting( 'tagline', get_bloginfo( 'description' ) ),
	'url'         => home_url( '/' ),
);

$mk_logo_for_schema = mk_logo_light_url() ?: mk_logo_dark_url();
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
<footer class="mk-site-footer">
	<div class="mk-site-footer__inner">
		<div class="mk-site-footer__brand">
			<p class="mk-site-footer__name"><?php echo esc_html( $mk_studio_name ); ?></p>
			<?php if ( $mk_footer_note ) : ?>
				<p class="mk-site-footer__note"><?php echo esc_html( $mk_footer_note ); ?></p>
			<?php endif; ?>
		</div>

		<nav class="mk-site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'maapkathi' ); ?>">
			<ul>
				<?php foreach ( mk_nav_items() as $mk_item ) : ?>
					<li><a href="<?php echo esc_url( $mk_item['href'] ); ?>"><?php echo esc_html( $mk_item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<address class="mk-site-footer__contact">
			<?php if ( $mk_email ) : ?>
				<a href="mailto:<?php echo esc_attr( $mk_email ); ?>"><?php echo esc_html( $mk_email ); ?></a>
			<?php endif; ?>
			<?php if ( $mk_phone ) : ?>
				<a href="<?php echo esc_attr( mk_tel_href( $mk_phone ) ); ?>"><?php echo esc_html( $mk_phone ); ?></a>
			<?php endif; ?>
			<?php if ( $mk_address ) : ?>
				<span><?php echo nl2br( esc_html( $mk_address ) ); ?></span>
			<?php endif; ?>

			<?php if ( $mk_socials ) : ?>
				<span class="mk-site-footer__socials">
					<?php foreach ( $mk_socials as $mk_platform => $mk_url ) : ?>
						<?php if ( $mk_url ) : ?>
							<a href="<?php echo esc_url( $mk_url ); ?>" rel="noopener noreferrer me" target="_blank"><?php echo esc_html( ucfirst( (string) $mk_platform ) ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</address>
	</div>

	<p class="mk-copyright">
		&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $mk_studio_name ); ?>
	</p>

	<script type="application/ld+json"><?php echo wp_json_encode( $mk_org ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
</footer>
