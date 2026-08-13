<?php
/**
 * Settings screen markup.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by SettingsScreen::render().
 *
 * @var array<string,mixed> $settings
 * @var array<string,mixed> $seo
 * @var string $notice
 */
$socials = $settings['socials'] ?? array();
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Settings', 'maapkathi' ); ?></h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'Your studio\'s core information: name, logo, favicon, contact details, social links, SEO defaults, and which homepage sections are shown.', 'maapkathi' ); ?></p>
	<?php
	if ( $notice ) :
		?>
		<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_settings', 'mk_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Studio', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Studio name', 'maapkathi' ); ?></th><td><input type="text" name="studio_name" value="<?php echo esc_attr( $settings['studio_name'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Tagline', 'maapkathi' ); ?></th><td><input type="text" name="tagline" value="<?php echo esc_attr( $settings['tagline'] ?? '' ); ?>" class="regular-text" /></td></tr>
			<tr><th><?php esc_html_e( 'Show title next to logo', 'maapkathi' ); ?></th><td><label><input type="checkbox" name="logo_show_title" value="1" <?php checked( $settings['logo_show_title'] ?? true ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td></tr>
		</table>

		<h2><?php esc_html_e( 'Branding', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<?php
			$mk_images = array(
				'logo_light' => array( __( 'Logo (light mode)', 'maapkathi' ), __( 'Shown on light backgrounds. Recommended: SVG-crisp PNG, around 400×130.', 'maapkathi' ) ),
				'logo_dark'  => array( __( 'Logo (dark mode)', 'maapkathi' ), __( 'Shown on dark backgrounds. Falls back to the light logo when unset.', 'maapkathi' ) ),
				'favicon'    => array( __( 'Favicon', 'maapkathi' ), __( 'Browser tab icon, square. Falls back to the light logo, then the dark logo, then the built-in mark.', 'maapkathi' ) ),
			);
			foreach ( $mk_images as $mk_key => list( $mk_label, $mk_help ) ) :
				$mk_id  = absint( $settings[ $mk_key ] ?? 0 );
				$mk_src = $mk_id ? wp_get_attachment_image_url( $mk_id, 'medium' ) : '';
				?>
				<tr>
					<th><?php echo esc_html( $mk_label ); ?></th>
					<td>
						<div data-mk-media>
							<input type="hidden" name="<?php echo esc_attr( $mk_key ); ?>" value="<?php echo esc_attr( (string) $mk_id ); ?>" />
							<div class="mk-media__preview">
								<?php if ( $mk_src ) : ?>
									<img src="<?php echo esc_url( $mk_src ); ?>" alt="" />
								<?php endif; ?>
							</div>
							<button type="button" class="button mk-media__choose"><?php esc_html_e( 'Choose image', 'maapkathi' ); ?></button>
							<button type="button" class="button-link mk-media__clear" <?php echo $mk_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Clear', 'maapkathi' ); ?></button>
							<p class="description"><?php echo esc_html( $mk_help ); ?></p>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
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

		<h2><?php esc_html_e( 'Homepage sections', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<?php
			$mk_sections = array(
				'section_clients_enabled'      => __( 'Trusted by (client logos)', 'maapkathi' ),
				'section_categories_enabled'   => __( 'Portfolio categories', 'maapkathi' ),
				'section_projects_enabled'     => __( 'Featured work', 'maapkathi' ),
				'section_services_enabled'     => __( 'Services', 'maapkathi' ),
				'section_stats_enabled'        => __( 'Stats band', 'maapkathi' ),
				'section_values_enabled'       => __( 'Values', 'maapkathi' ),
				'section_team_enabled'         => __( 'Team', 'maapkathi' ),
				'section_testimonials_enabled' => __( 'Testimonials', 'maapkathi' ),
				'section_awards_enabled'       => __( 'Awards', 'maapkathi' ),
				'section_faq_enabled'          => __( 'FAQ', 'maapkathi' ),
			);
			foreach ( $mk_sections as $mk_key => $mk_label ) :
				?>
				<tr>
					<th><?php echo esc_html( $mk_label ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $mk_key ); ?>" value="1" <?php checked( ! isset( $settings[ $mk_key ] ) || $settings[ $mk_key ] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
				</tr>
			<?php endforeach; ?>
			<tr><td colspan="2"><p class="description"><?php esc_html_e( 'A section with no content underneath already hides itself — these switches are for hiding a section on purpose even when it has content.', 'maapkathi' ); ?></p></td></tr>
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
