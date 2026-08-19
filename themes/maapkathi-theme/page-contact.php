<?php
/**
 * Template Name: Contact
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

use Maapkathi\Core\Inquiries\Inquiries;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mk_email    = mk_setting( 'contact_email' );
$mk_phone    = mk_setting( 'contact_phone' );
$mk_whatsapp = mk_setting( 'whatsapp' );
$mk_address  = mk_setting( 'address' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag from our own redirect.
$mk_sent   = isset( $_GET['mk_inquiry'] ) && 'sent' === $_GET['mk_inquiry'];
$mk_errors = class_exists( Inquiries::class ) ? Inquiries::take_errors() : array();
$mk_old    = class_exists( Inquiries::class ) ? Inquiries::take_old_input() : array();
?>
<div class="mk-container mk-section mk-contact">
	<?php mk_the_section_heading( 'contact_page', 'h1', 'mk-page-title' ); ?>
	<?php $mk_intro = mk_text( 'contact_intro' ); ?>
	<?php if ( $mk_intro ) : ?>
		<p class="mk-lede"><?php echo esc_html( $mk_intro ); ?></p>
	<?php endif; ?>

	<?php if ( $mk_sent ) : ?>
		<div class="mk-notice mk-notice--success" role="status">
			<?php mk_the_text( 'contact_success_message' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $mk_errors ) : ?>
		<div class="mk-notice mk-notice--error" role="alert">
			<p><strong><?php esc_html_e( 'Please check the form:', 'maapkathi' ); ?></strong></p>
			<ul>
				<?php foreach ( $mk_errors as $mk_error ) : ?>
					<li><?php echo esc_html( $mk_error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<div class="mk-contact__grid">
		<address class="mk-contact__details">
			<?php if ( $mk_email ) : ?>
				<p>
					<span class="mk-contact__label"><?php esc_html_e( 'Email', 'maapkathi' ); ?></span>
					<a href="mailto:<?php echo esc_attr( $mk_email ); ?>"><?php echo esc_html( $mk_email ); ?></a>
				</p>
			<?php endif; ?>
			<?php if ( $mk_phone ) : ?>
				<p>
					<span class="mk-contact__label"><?php esc_html_e( 'Phone', 'maapkathi' ); ?></span>
					<a href="<?php echo esc_attr( mk_tel_href( $mk_phone ) ); ?>"><?php echo esc_html( $mk_phone ); ?></a>
				</p>
			<?php endif; ?>
			<?php if ( $mk_whatsapp ) : ?>
				<p>
					<span class="mk-contact__label"><?php esc_html_e( 'WhatsApp', 'maapkathi' ); ?></span>
					<a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $mk_whatsapp ) ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( $mk_whatsapp ); ?></a>
				</p>
			<?php endif; ?>
			<?php if ( $mk_address ) : ?>
				<p>
					<span class="mk-contact__label"><?php esc_html_e( 'Studio', 'maapkathi' ); ?></span>
					<?php echo nl2br( esc_html( $mk_address ) ); ?>
				</p>
			<?php endif; ?>
		</address>

		<form class="mk-contact__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="mk_submit_inquiry" />
			<?php wp_nonce_field( 'mk_inquiry', 'mk_inquiry_nonce' ); ?>

			<p class="mk-honeypot" aria-hidden="true">
				<label>
					<?php esc_html_e( 'Website', 'maapkathi' ); ?>
					<input type="text" name="mk_website" tabindex="-1" autocomplete="off" />
				</label>
			</p>

			<p>
				<label for="mk-name"><?php esc_html_e( 'Name', 'maapkathi' ); ?> <span aria-hidden="true">*</span></label>
				<input id="mk-name" type="text" name="name" required value="<?php echo esc_attr( $mk_old['name'] ?? '' ); ?>" autocomplete="name" />
			</p>
			<p>
				<label for="mk-email"><?php esc_html_e( 'Email', 'maapkathi' ); ?> <span aria-hidden="true">*</span></label>
				<input id="mk-email" type="email" name="email" required value="<?php echo esc_attr( $mk_old['email'] ?? '' ); ?>" autocomplete="email" />
			</p>
			<p>
				<label for="mk-phone"><?php esc_html_e( 'Phone', 'maapkathi' ); ?></label>
				<input id="mk-phone" type="tel" name="phone" value="<?php echo esc_attr( $mk_old['phone'] ?? '' ); ?>" autocomplete="tel" />
			</p>
			<p>
				<label for="mk-message"><?php esc_html_e( 'Message', 'maapkathi' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="mk-message" name="message" rows="6" required><?php echo esc_textarea( $mk_old['message'] ?? '' ); ?></textarea>
			</p>

			<button type="submit" class="mk-btn mk-btn--accent"><?php mk_the_text( 'contact_form_button_label' ); ?></button>
		</form>
	</div>

	<?php
	// The map sits below the details and the form, so a visitor reads who
	// to contact before where to go. It hides itself when unconfigured.
	get_template_part( 'parts/section-map', null, array( 'context' => 'contact' ) );
	?>
</div>
<?php
get_footer();
