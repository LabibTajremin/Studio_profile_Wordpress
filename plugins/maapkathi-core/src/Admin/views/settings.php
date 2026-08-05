<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array<string,mixed> $settings
 * @var array<string,mixed> $seo
 * @var string $notice
 */
$socials = $settings['socials'] ?? array();
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Settings', 'maapkathi' ); ?></h1>
	<?php if ( $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_settings', 'mk_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Studio', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Studio name', 'maapkathi' ); ?></th><td><input type="text" name="studio_name" value="<?php echo esc_attr( $settings['studio_name'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Tagline', 'maapkathi' ); ?></th><td><input type="text" name="tagline" value="<?php echo esc_attr( $settings['tagline'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Show title next to logo', 'maapkathi' ); ?></th><td><label><input type="checkbox" name="logo_show_title" value="1" <?php checked( $settings['logo_show_title'] ?? true ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td></tr>
		</table>

		<h2><?php esc_html_e( 'Contact', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Email', 'maapkathi' ); ?></th><td><input type="email" name="contact_email" value="<?php echo esc_attr( $settings['contact_email'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Phone', 'maapkathi' ); ?></th><td><input type="text" name="contact_phone" value="<?php echo esc_attr( $settings['contact_phone'] ?? '' ); ?>" /></td></tr>
			<tr><th><?php esc_html_e( 'WhatsApp', 'maapkathi' ); ?></th><td><input type="text" name="whatsapp" value="<?php echo esc_attr( $settings['whatsapp'] ?? '' ); ?>" /></td></tr>
			<tr><th><?php esc_html_e( 'Address', 'maapkathi' ); ?></th><td><textarea name="address" rows="2" class="large-text"><?php echo esc_textarea( $settings['address'] ?? '' ); ?></textarea></td></tr>
			<?php foreach ( array( 'facebook', 'instagram', 'linkedin', 'twitter', 'pinterest' ) as $platform ) : ?>
				<tr><th><?php echo esc_html( ucfirst( $platform ) ); ?></th><td><input type="url" name="social_<?php echo esc_attr( $platform ); ?>" value="<?php echo esc_attr( $socials[ $platform ] ?? '' ); ?>" class="regular-text" /></td></tr>
			<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Behaviour', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Blog enabled', 'maapkathi' ); ?></th><td><label><input type="checkbox" name="blog_enabled" value="1" <?php checked( ! empty( $settings['blog_enabled'] ) ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Vision & Mission section', 'maapkathi' ); ?></th><td><label><input type="checkbox" name="vision_mission_enabled" value="1" <?php checked( ! empty( $settings['vision_mission_enabled'] ) ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Vision text', 'maapkathi' ); ?></th><td><textarea name="vision_text" rows="2" class="large-text"><?php echo esc_textarea( $settings['vision_text'] ?? '' ); ?></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Mission text', 'maapkathi' ); ?></th><td><textarea name="mission_text" rows="2" class="large-text"><?php echo esc_textarea( $settings['mission_text'] ?? '' ); ?></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Editor verification required', 'maapkathi' ); ?></th><td><label><input type="checkbox" name="editor_verification_required" value="1" <?php checked( ! isset( $settings['editor_verification_required'] ) || $settings['editor_verification_required'] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td></tr>
			<tr><th><?php esc_html_e( 'Admin login shield', 'maapkathi' ); ?></th><td><label><input type="checkbox" name="show_admin_shield" value="1" <?php checked( ! empty( $settings['show_admin_shield'] ) ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td></tr>
		</table>

		<h2><?php esc_html_e( 'SEO defaults', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Default title', 'maapkathi' ); ?></th><td><input type="text" name="seo_default_title" value="<?php echo esc_attr( $seo['default_title'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Default description', 'maapkathi' ); ?></th><td><textarea name="seo_default_description" rows="2" class="large-text"><?php echo esc_textarea( $seo['default_description'] ?? '' ); ?></textarea></td></tr>
			<tr><th><?php esc_html_e( 'Default OG image URL', 'maapkathi' ); ?></th><td><input type="url" name="seo_og_image" value="<?php echo esc_attr( $seo['og_image'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Google Analytics ID', 'maapkathi' ); ?></th><td><input type="text" name="seo_ga_id" value="<?php echo esc_attr( $seo['ga_id'] ?? '' ); ?>" /></td></tr>
			<tr><th><?php esc_html_e( 'Google Tag Manager ID', 'maapkathi' ); ?></th><td><input type="text" name="seo_gtm_id" value="<?php echo esc_attr( $seo['gtm_id'] ?? '' ); ?>" /></td></tr>
			<tr><th><?php esc_html_e( 'Meta Pixel ID', 'maapkathi' ); ?></th><td><input type="text" name="seo_meta_pixel_id" value="<?php echo esc_attr( $seo['meta_pixel_id'] ?? '' ); ?>" /></td></tr>
		</table>

		<p class="description"><?php esc_html_e( 'Logo, favicon, and video uploads are managed from the WordPress Media Library and referenced here in a future iteration of this screen.', 'maapkathi' ); ?></p>

		<?php submit_button( __( 'Save settings', 'maapkathi' ) ); ?>
	</form>
</div>
