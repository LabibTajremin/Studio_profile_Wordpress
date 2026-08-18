<?php
/**
 * Classic footer (FR-08.1): the layout the theme shipped with, kept intact
 * so an existing site does not lose its footer when the theme updates.
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
 * @var string               $mk_studio_name Studio name.
 * @var string               $mk_email       Contact email.
 * @var string               $mk_phone       Contact phone.
 * @var string               $mk_address     Postal address.
 * @var array<string,string> $mk_socials     Social links keyed by platform.
 * @var string               $mk_footer_note Footer note copy.
 */
?>
	<div class="mk-footer__inner mk-footer__inner--classic">
		<div class="mk-footer__brand">
			<p class="mk-footer__name"><?php echo esc_html( $mk_studio_name ); ?></p>
			<?php if ( $mk_footer_note ) : ?>
				<p class="mk-footer__note"><?php echo esc_html( $mk_footer_note ); ?></p>
			<?php endif; ?>
		</div>

		<nav class="mk-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'maapkathi' ); ?>">
			<ul>
				<?php foreach ( mk_nav_items() as $mk_item ) : ?>
					<li><a href="<?php echo esc_url( $mk_item['href'] ); ?>"><?php echo esc_html( $mk_item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<address class="mk-footer__contact-block">
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
				<span class="mk-footer__socials-inline">
					<?php foreach ( $mk_socials as $mk_platform => $mk_url ) : ?>
						<?php if ( $mk_url ) : ?>
							<a href="<?php echo esc_url( $mk_url ); ?>" rel="noopener noreferrer me" target="_blank"><?php echo esc_html( ucfirst( (string) $mk_platform ) ); ?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</address>
	</div>

