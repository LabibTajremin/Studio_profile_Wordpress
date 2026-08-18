<?php
/**
 * Modern four-column footer (FR-08).
 *
 * Column order matches the client's reference: brand + socials, contacts,
 * a links column, then a subscribe block.
 *
 * Required from parts/footer.php, which owns the variables below.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables supplied by parts/footer.php.
 *
 * @var array<string,mixed> $mk_footer      Sanitized footer settings.
 * @var string              $mk_studio_name Studio name, for the logo fallback.
 */
$mk_socials  = array_values(
	array_filter(
		(array) $mk_footer['socials'],
		static fn( $row ) => ! empty( $row['enabled'] )
	)
);
$mk_contacts = (array) $mk_footer['contacts'];
$mk_col3     = (array) $mk_footer['col3'];
$mk_col4     = (array) $mk_footer['col4'];

// FR-08.5: footer logo, then header logo, then the site title in the
// heading font — never a broken image.
$mk_footer_logo_light = $mk_footer['logo_light'] ? wp_get_attachment_image_url( (int) $mk_footer['logo_light'], 'medium' ) : '';
$mk_footer_logo_dark  = $mk_footer['logo_dark'] ? wp_get_attachment_image_url( (int) $mk_footer['logo_dark'], 'medium' ) : '';
if ( ! $mk_footer_logo_light && ! $mk_footer_logo_dark ) {
	$mk_footer_logo_light = mk_logo_light_url();
	$mk_footer_logo_dark  = mk_logo_dark_url();
}

$mk_col3_links = mk_footer_column_links( (string) $mk_col3['type'], (int) $mk_col3['limit'], (array) $mk_col3['links'] );
$mk_col4_links = 'links' === $mk_col4['type']
	? mk_footer_column_links( (string) $mk_col4['source'], (int) $mk_col4['limit'], (array) $mk_col4['links'] )
	: array();
?>
<div class="mk-footer__inner mk-footer__inner--modern<?php echo ! empty( $mk_footer['centre_mobile'] ) ? ' is-centred' : ''; ?>">
	<div class="mk-footer__col mk-footer__col--brand">
		<div class="mk-footer__logo">
			<?php if ( $mk_footer_logo_light || $mk_footer_logo_dark ) : ?>
				<?php
				$mk_light_src = $mk_footer_logo_light ? $mk_footer_logo_light : $mk_footer_logo_dark;
				$mk_dark_src  = $mk_footer_logo_dark ? $mk_footer_logo_dark : $mk_footer_logo_light;
				?>
				<img class="mk-footer__logo-img mk-footer__logo-img--light" src="<?php echo esc_url( $mk_light_src ); ?>" alt="<?php echo esc_attr( $mk_studio_name ); ?>" loading="lazy" decoding="async" />
				<img class="mk-footer__logo-img mk-footer__logo-img--dark" src="<?php echo esc_url( $mk_dark_src ); ?>" alt="<?php echo esc_attr( $mk_studio_name ); ?>" loading="lazy" decoding="async" />
			<?php else : ?>
				<span class="mk-footer__wordmark"><?php echo esc_html( $mk_studio_name ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $mk_socials ) : ?>
			<ul class="mk-footer__socials">
				<?php foreach ( $mk_socials as $mk_social ) : ?>
					<?php $mk_platform_label = mk_footer_platform_label( (string) $mk_social['platform'] ); ?>
					<li>
						<a
							class="mk-footer__social"
							href="<?php echo esc_url( (string) $mk_social['url'] ); ?>"
							target="_blank"
							rel="noopener noreferrer me"
							aria-label="<?php echo esc_attr( $mk_platform_label ); ?>"
							title="<?php echo esc_attr( $mk_platform_label ); ?>"
						>
							<?php echo mk_footer_social_icon( (string) $mk_social['platform'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled inline SVG, escaped at source. ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php if ( $mk_contacts ) : ?>
		<div class="mk-footer__col mk-footer__col--contacts">
			<h2 class="mk-footer__heading"><?php mk_the_text( 'footer_contacts_heading' ); ?></h2>
			<ul class="mk-footer__contacts">
				<?php foreach ( $mk_contacts as $mk_contact ) : ?>
					<?php
					$mk_type  = (string) $mk_contact['type'];
					$mk_value = (string) $mk_contact['value'];

					// Email and phone rows become real links without the
					// admin having to paste one; the tel: href drops the
					// spaces and dashes a dialler cannot use.
					$mk_href = (string) $mk_contact['link'];
					if ( '' === $mk_href && 'email' === $mk_type ) {
						$mk_href = 'mailto:' . $mk_value;
					} elseif ( '' === $mk_href && 'phone' === $mk_type ) {
						$mk_href = mk_tel_href( $mk_value );
					}
					?>
					<li class="mk-footer__contact mk-footer__contact--<?php echo esc_attr( $mk_type ); ?>">
						<?php echo mk_contact_icon( $mk_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bundled inline SVG, escaped at source. ?>
						<?php if ( $mk_href ) : ?>
							<a href="<?php echo esc_attr( $mk_href ); ?>"><?php echo nl2br( esc_html( $mk_value ) ); ?></a>
						<?php else : ?>
							<span><?php echo nl2br( esc_html( $mk_value ) ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $mk_col3_links ) : ?>
		<div class="mk-footer__col mk-footer__col--links">
			<h2 class="mk-footer__heading">
				<?php echo esc_html( '' !== $mk_col3['heading'] ? (string) $mk_col3['heading'] : mk_text( 'footer_links_heading' ) ); ?>
			</h2>
			<ul class="mk-footer__links">
				<?php foreach ( $mk_col3_links as $mk_link ) : ?>
					<li><a href="<?php echo esc_url( $mk_link['url'] ); ?>"><?php echo esc_html( $mk_link['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( 'newsletter' === $mk_col4['type'] ) : ?>
		<div class="mk-footer__col mk-footer__col--subscribe">
			<h2 class="mk-footer__heading">
				<?php echo esc_html( '' !== $mk_col4['heading'] ? (string) $mk_col4['heading'] : mk_text( 'footer_subscribe_heading' ) ); ?>
			</h2>
			<form class="mk-subscribe" data-mk-subscribe novalidate>
				<?php wp_nonce_field( 'mk_subscribe', 'mk_subscribe_nonce', false, true ); ?>
				<label class="screen-reader-text" for="mk-subscribe-email"><?php esc_html_e( 'Email address', 'maapkathi' ); ?></label>
				<div class="mk-subscribe__row">
					<input type="email" id="mk-subscribe-email" name="email" required autocomplete="email" placeholder="<?php esc_attr_e( 'you@example.com', 'maapkathi' ); ?>" />
					<button type="submit" class="mk-subscribe__button"><?php esc_html_e( 'Subscribe', 'maapkathi' ); ?></button>
				</div>
				<?php
				// Honeypot. Positioned off-screen rather than display:none,
				// because some bots skip fields that are not rendered.
				?>
				<div class="mk-subscribe__trap" aria-hidden="true">
					<label for="mk-subscribe-website"><?php esc_html_e( 'Leave this field empty', 'maapkathi' ); ?></label>
					<input type="text" id="mk-subscribe-website" name="website" tabindex="-1" autocomplete="off" />
				</div>
				<p class="mk-subscribe__helper">
					<?php echo esc_html( '' !== $mk_col4['helper'] ? (string) $mk_col4['helper'] : mk_text( 'footer_subscribe_helper' ) ); ?>
				</p>
				<p class="mk-subscribe__status" role="status" aria-live="polite"></p>
			</form>
		</div>
	<?php elseif ( 'links' === $mk_col4['type'] && $mk_col4_links ) : ?>
		<div class="mk-footer__col mk-footer__col--links">
			<h2 class="mk-footer__heading">
				<?php echo esc_html( '' !== $mk_col4['heading'] ? (string) $mk_col4['heading'] : mk_text( 'footer_subscribe_heading' ) ); ?>
			</h2>
			<ul class="mk-footer__links">
				<?php foreach ( $mk_col4_links as $mk_link ) : ?>
					<li><a href="<?php echo esc_url( $mk_link['url'] ); ?>"><?php echo esc_html( $mk_link['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
