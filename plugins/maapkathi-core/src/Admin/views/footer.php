<?php
/**
 * Footer settings screen markup (FR-08, FR-09).
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

use Maapkathi\Core\Footer\FooterSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View variables provided by FooterScreen::render().
 *
 * @var array<string,mixed>  $footer       Sanitized footer settings.
 * @var string               $notice       Save notice, if any.
 * @var array<string,string> $field_errors Per-field validation errors.
 */
$mk_socials  = (array) $footer['socials'];
$mk_contacts = (array) $footer['contacts'];
$mk_col3     = (array) $footer['col3'];
$mk_col4     = (array) $footer['col4'];
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Footer', 'maapkathi' ); ?></h1>
	<p class="mk-admin-intro"><?php esc_html_e( 'Everything that appears at the bottom of every page: which footer layout to use, its background colour, the footer logo, your social links, contact lines, the links column, and the newsletter box. The copyright line below the footer always uses the same background as the footer itself.', 'maapkathi' ); ?></p>

	<?php if ( $notice ) : ?>
		<div class="notice <?php echo $field_errors ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_footer', 'mk_footer_nonce' ); ?>

		<h2><?php esc_html_e( 'Layout', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Footer style', 'maapkathi' ); ?></th>
				<td>
					<?php
					$mk_styles = array(
						'classic' => __( 'Classic', 'maapkathi' ),
						'modern'  => __( 'Modern — 4 column', 'maapkathi' ),
					);
					foreach ( $mk_styles as $mk_style => $mk_style_label ) :
						?>
						<label style="margin-right:1.5em"><input type="radio" name="mk_footer[style]" value="<?php echo esc_attr( $mk_style ); ?>" <?php checked( (string) $footer['style'], $mk_style ); ?> /> <?php echo esc_html( $mk_style_label ); ?></label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Classic is the footer this site has always used. Modern is the four-column layout: logo and socials, contacts, links, and a subscribe box.', 'maapkathi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Background', 'maapkathi' ); ?></th>
				<td>
					<?php
					$mk_bg_modes = array(
						'dark'    => __( 'Dark neutral', 'maapkathi' ),
						'accent'  => __( 'Accent colour', 'maapkathi' ),
						'surface' => __( 'Same as the page', 'maapkathi' ),
						'custom'  => __( 'Custom hex', 'maapkathi' ),
					);
					foreach ( $mk_bg_modes as $mk_mode => $mk_mode_label ) :
						?>
						<label style="margin-right:1.5em"><input type="radio" name="mk_footer[bg_mode]" value="<?php echo esc_attr( $mk_mode ); ?>" <?php checked( (string) $footer['bg_mode'], $mk_mode ); ?> /> <?php echo esc_html( $mk_mode_label ); ?></label>
					<?php endforeach; ?>
					<p>
						<input type="text" name="mk_footer[bg_hex]" value="<?php echo esc_attr( (string) ( $footer['bg_hex'] ?? '' ) ); ?>" placeholder="#RRGGBB" />
						<span class="mk-colour-chip" style="background:<?php echo esc_attr( (string) ( $footer['bg_hex'] ?? 'transparent' ) ); ?>" aria-hidden="true"></span>
					</p>
					<?php if ( isset( $field_errors['bg_hex'] ) ) : ?>
						<p class="mk-field-error"><?php echo esc_html( $field_errors['bg_hex'] ); ?></p>
					<?php endif; ?>
					<p class="description"><?php esc_html_e( 'Text and link colours are worked out automatically from whichever background you choose, so the footer stays readable. The copyright bar follows the same colour.', 'maapkathi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Divider above copyright', 'maapkathi' ); ?></th>
				<td>
					<label><input type="hidden" name="mk_footer[show_divider]" value="0" /><input type="checkbox" name="mk_footer[show_divider]" value="1" <?php checked( ! empty( $footer['show_divider'] ) ); ?> /> <?php esc_html_e( 'Show a line above the copyright', 'maapkathi' ); ?></label>
					<p class="description"><?php esc_html_e( 'Off by default, so the footer and the copyright line read as one block.', 'maapkathi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Centre columns on mobile', 'maapkathi' ); ?></th>
				<td><label><input type="hidden" name="mk_footer[centre_mobile]" value="0" /><input type="checkbox" name="mk_footer[centre_mobile]" value="1" <?php checked( ! empty( $footer['centre_mobile'] ) ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Footer logo', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<?php
			foreach ( array(
				'logo_light' => __( 'Logo (for dark footers)', 'maapkathi' ),
				'logo_dark'  => __( 'Logo (for light footers)', 'maapkathi' ),
			) as $mk_logo_key => $mk_logo_label ) :
				$mk_logo_id  = absint( $footer[ $mk_logo_key ] );
				$mk_logo_src = $mk_logo_id ? wp_get_attachment_image_url( $mk_logo_id, 'medium' ) : '';
				?>
				<tr>
					<th><?php echo esc_html( $mk_logo_label ); ?></th>
					<td>
						<div data-mk-media>
							<input type="hidden" name="mk_footer[<?php echo esc_attr( $mk_logo_key ); ?>]" value="<?php echo esc_attr( (string) $mk_logo_id ); ?>" />
							<div class="mk-media__preview">
								<?php if ( $mk_logo_src ) : ?>
									<img src="<?php echo esc_url( $mk_logo_src ); ?>" alt="" />
								<?php endif; ?>
							</div>
							<button type="button" class="button mk-media__choose"><?php esc_html_e( 'Choose image', 'maapkathi' ); ?></button>
							<button type="button" class="button-link mk-media__clear" <?php echo $mk_logo_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Clear', 'maapkathi' ); ?></button>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th><?php esc_html_e( 'Which logo to show', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( array( 'auto', 'light', 'dark' ) as $mk_mode_id ) : ?>
						<label style="margin-right:1em"><input type="radio" name="mk_footer[logo_mode]" value="<?php echo esc_attr( $mk_mode_id ); ?>" <?php checked( (string) $footer['logo_mode'], $mk_mode_id ); ?> /> <?php echo esc_html( ucfirst( $mk_mode_id ) ); ?></label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Auto follows the site\'s light/dark mode. If neither footer logo is set, the header logo is used, and failing that the studio name.', 'maapkathi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Maximum logo height', 'maapkathi' ); ?></th>
				<td><input type="number" min="24" max="160" name="mk_footer[logo_max_h]" value="<?php echo esc_attr( (string) $footer['logo_max_h'] ); ?>" /> px</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Social links', 'maapkathi' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Shown under the footer logo as icons only — no addresses and no platform names are visible. A row with an empty address is not shown at all.', 'maapkathi' ); ?></p>
		<table class="form-table mk-repeater" data-mk-repeater="socials">
			<?php
			// One blank row past the end so there is always somewhere to add
			// the next link without a JavaScript "add row" step.
			$mk_social_rows = array_merge( $mk_socials, array( array() ) );
			foreach ( $mk_social_rows as $mk_i => $mk_social ) :
				?>
				<tr>
					<th>
						<select name="mk_footer[socials][<?php echo esc_attr( (string) $mk_i ); ?>][platform]">
							<option value=""><?php esc_html_e( '— none —', 'maapkathi' ); ?></option>
							<?php foreach ( FooterSettings::platforms() as $mk_slug => $mk_platform_label ) : ?>
								<option value="<?php echo esc_attr( $mk_slug ); ?>" <?php selected( (string) ( $mk_social['platform'] ?? '' ), $mk_slug ); ?>><?php echo esc_html( $mk_platform_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</th>
					<td>
						<input type="url" class="regular-text" name="mk_footer[socials][<?php echo esc_attr( (string) $mk_i ); ?>][url]" value="<?php echo esc_attr( (string) ( $mk_social['url'] ?? '' ) ); ?>" placeholder="https://" />
						<label><input type="hidden" name="mk_footer[socials][<?php echo esc_attr( (string) $mk_i ); ?>][enabled]" value="0" /><input type="checkbox" name="mk_footer[socials][<?php echo esc_attr( (string) $mk_i ); ?>][enabled]" value="1" <?php checked( ! isset( $mk_social['enabled'] ) || $mk_social['enabled'] ); ?> /> <?php esc_html_e( 'Show', 'maapkathi' ); ?></label>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Contact lines', 'maapkathi' ); ?></h2>
		<p class="description"><?php esc_html_e( 'One piece of information per line, each with its own icon. Email lines become clickable mail links and phone lines become tap-to-call links automatically.', 'maapkathi' ); ?></p>
		<table class="form-table mk-repeater" data-mk-repeater="contacts">
			<?php
			$mk_contact_rows = array_merge( $mk_contacts, array( array() ) );
			foreach ( $mk_contact_rows as $mk_i => $mk_contact ) :
				?>
				<tr>
					<th>
						<select name="mk_footer[contacts][<?php echo esc_attr( (string) $mk_i ); ?>][type]">
							<option value=""><?php esc_html_e( '— none —', 'maapkathi' ); ?></option>
							<?php foreach ( FooterSettings::contact_types() as $mk_slug => $mk_type_label ) : ?>
								<option value="<?php echo esc_attr( $mk_slug ); ?>" <?php selected( (string) ( $mk_contact['type'] ?? '' ), $mk_slug ); ?>><?php echo esc_html( $mk_type_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</th>
					<td>
						<textarea rows="2" class="large-text" name="mk_footer[contacts][<?php echo esc_attr( (string) $mk_i ); ?>][value]" placeholder="<?php esc_attr_e( 'Value shown in the footer', 'maapkathi' ); ?>"><?php echo esc_textarea( (string) ( $mk_contact['value'] ?? '' ) ); ?></textarea>
						<input type="url" class="regular-text" name="mk_footer[contacts][<?php echo esc_attr( (string) $mk_i ); ?>][link]" value="<?php echo esc_attr( (string) ( $mk_contact['link'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Optional link (leave empty for the automatic one)', 'maapkathi' ); ?>" />
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Links column', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Heading', 'maapkathi' ); ?></th>
				<td><input type="text" class="regular-text" name="mk_footer[col3][heading]" value="<?php echo esc_attr( (string) $mk_col3['heading'] ); ?>" placeholder="<?php esc_attr_e( 'Our Projects', 'maapkathi' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Show', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_footer[col3][type]">
						<?php foreach ( FooterSettings::column_sources() as $mk_slug => $mk_source_label ) : ?>
							<option value="<?php echo esc_attr( $mk_slug ); ?>" <?php selected( (string) $mk_col3['type'], $mk_slug ); ?>><?php echo esc_html( $mk_source_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php esc_html_e( 'up to', 'maapkathi' ); ?>
					<input type="number" min="1" max="20" name="mk_footer[col3][limit]" value="<?php echo esc_attr( (string) $mk_col3['limit'] ); ?>" style="width:5em" />
					<?php esc_html_e( 'items', 'maapkathi' ); ?>
				</td>
			</tr>
			<?php
			$mk_col3_links = array_merge( (array) $mk_col3['links'], array( array() ) );
			foreach ( $mk_col3_links as $mk_i => $mk_link ) :
				?>
				<tr>
					<th><?php echo 0 === $mk_i ? esc_html__( 'Custom links', 'maapkathi' ) : ''; ?></th>
					<td>
						<input type="text" name="mk_footer[col3][links][<?php echo esc_attr( (string) $mk_i ); ?>][label]" value="<?php echo esc_attr( (string) ( $mk_link['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Label', 'maapkathi' ); ?>" />
						<input type="url" class="regular-text" name="mk_footer[col3][links][<?php echo esc_attr( (string) $mk_i ); ?>][url]" value="<?php echo esc_attr( (string) ( $mk_link['url'] ?? '' ) ); ?>" placeholder="https://" />
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2><?php esc_html_e( 'Fourth column', 'maapkathi' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Column type', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_footer[col4][type]">
						<option value="newsletter" <?php selected( (string) $mk_col4['type'], 'newsletter' ); ?>><?php esc_html_e( 'Newsletter signup', 'maapkathi' ); ?></option>
						<option value="links" <?php selected( (string) $mk_col4['type'], 'links' ); ?>><?php esc_html_e( 'A second links column', 'maapkathi' ); ?></option>
						<option value="none" <?php selected( (string) $mk_col4['type'], 'none' ); ?>><?php esc_html_e( 'Nothing', 'maapkathi' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Heading', 'maapkathi' ); ?></th>
				<td><input type="text" class="regular-text" name="mk_footer[col4][heading]" value="<?php echo esc_attr( (string) $mk_col4['heading'] ); ?>" placeholder="<?php esc_attr_e( 'Subscribe', 'maapkathi' ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Helper text', 'maapkathi' ); ?></th>
				<td><input type="text" class="large-text" name="mk_footer[col4][helper]" value="<?php echo esc_attr( (string) $mk_col4['helper'] ); ?>" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'If showing links', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_footer[col4][source]">
						<?php foreach ( FooterSettings::column_sources() as $mk_slug => $mk_source_label ) : ?>
							<option value="<?php echo esc_attr( $mk_slug ); ?>" <?php selected( (string) $mk_col4['source'], $mk_slug ); ?>><?php echo esc_html( $mk_source_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="number" min="1" max="20" name="mk_footer[col4][limit]" value="<?php echo esc_attr( (string) $mk_col4['limit'] ); ?>" style="width:5em" />
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save footer', 'maapkathi' ) ); ?>
	</form>
</div>
