<?php
/**
 * Template Name: Contact
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_settings = get_option( 'mk_site_settings', array() );

get_header();
?>
<div class="mk-container mk-section mk-contact">
	<h1><?php the_title(); ?></h1>

	<?php if ( isset( $_GET['mk_inquiry'] ) && 'sent' === $_GET['mk_inquiry'] ) : ?>
		<p class="mk-notice mk-notice--success"><?php esc_html_e( 'Thanks — we received your message and will be in touch.', 'maapkathi' ); ?></p>
	<?php endif; ?>

	<div class="mk-contact__grid">
		<address class="mk-contact__details">
			<?php if ( ! empty( $site_settings['contact_email'] ) ) : ?><p><a href="mailto:<?php echo esc_attr( $site_settings['contact_email'] ); ?>"><?php echo esc_html( $site_settings['contact_email'] ); ?></a></p><?php endif; ?>
			<?php if ( ! empty( $site_settings['contact_phone'] ) ) : ?><p><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $site_settings['contact_phone'] ) ); ?>"><?php echo esc_html( $site_settings['contact_phone'] ); ?></a></p><?php endif; ?>
			<?php if ( ! empty( $site_settings['address'] ) ) : ?><p><?php echo esc_html( $site_settings['address'] ); ?></p><?php endif; ?>
		</address>

		<form class="mk-contact__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mk_submit_inquiry" />
			<?php wp_nonce_field( 'mk_inquiry', 'mk_inquiry_nonce' ); ?>
			<p class="mk-honeypot" aria-hidden="true">
				<label>Website<input type="text" name="mk_website" tabindex="-1" autocomplete="off" /></label>
			</p>
			<p><label for="mk-name"><?php esc_html_e( 'Name', 'maapkathi' ); ?></label><input id="mk-name" type="text" name="name" required /></p>
			<p><label for="mk-email"><?php esc_html_e( 'Email', 'maapkathi' ); ?></label><input id="mk-email" type="email" name="email" required /></p>
			<p><label for="mk-phone"><?php esc_html_e( 'Phone', 'maapkathi' ); ?></label><input id="mk-phone" type="tel" name="phone" /></p>
			<p><label for="mk-message"><?php esc_html_e( 'Message', 'maapkathi' ); ?></label><textarea id="mk-message" name="message" rows="5" required></textarea></p>
			<button type="submit" class="mk-btn mk-btn--accent"><?php esc_html_e( 'Send message', 'maapkathi' ); ?></button>
		</form>
	</div>
</div>
<?php
get_footer();
