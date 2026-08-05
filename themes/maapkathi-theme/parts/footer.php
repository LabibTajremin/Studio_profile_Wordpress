<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_settings = get_option( 'mk_site_settings', array() );
$org = array(
	'@context' => 'https://schema.org',
	'@type'    => 'Organization',
	'name'     => $site_settings['studio_name'] ?? get_bloginfo( 'name' ),
	'description' => $site_settings['tagline'] ?? get_bloginfo( 'description' ),
	'url'      => home_url( '/' ),
);
if ( ! empty( $site_settings['contact_email'] ) ) {
	$org['contactPoint'] = array(
		'@type'      => 'ContactPoint',
		'email'      => $site_settings['contact_email'],
		'contactType'=> 'customer service',
	);
}
if ( ! empty( $site_settings['socials'] ) && is_array( $site_settings['socials'] ) ) {
	$org['sameAs'] = array_values( $site_settings['socials'] );
}
?>
<footer class="mk-site-footer">
	<div class="mk-site-footer__inner">
		<nav aria-label="<?php esc_attr_e( 'Footer', 'maapkathi' ); ?>">
			<ul>
				<?php foreach ( mk_nav_items() as $item ) : ?>
					<li><a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<address>
			<?php if ( ! empty( $site_settings['contact_email'] ) ) : ?>
				<a href="mailto:<?php echo esc_attr( $site_settings['contact_email'] ); ?>"><?php echo esc_html( $site_settings['contact_email'] ); ?></a>
			<?php endif; ?>
			<?php if ( ! empty( $site_settings['contact_phone'] ) ) : ?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $site_settings['contact_phone'] ) ); ?>"><?php echo esc_html( $site_settings['contact_phone'] ); ?></a>
			<?php endif; ?>
			<?php if ( ! empty( $site_settings['address'] ) ) : ?>
				<span><?php echo esc_html( $site_settings['address'] ); ?></span>
			<?php endif; ?>
		</address>

		<p class="mk-copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_settings['studio_name'] ?? get_bloginfo( 'name' ) ); ?></p>
	</div>

	<script type="application/ld+json"><?php echo wp_json_encode( $org ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
</footer>
