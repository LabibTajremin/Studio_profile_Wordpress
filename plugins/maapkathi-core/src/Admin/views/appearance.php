<?php
/**
 * Appearance screen markup.
 *
 * @package Maapkathi\Core
 */

declare( strict_types = 1 );

use Maapkathi\Core\Theme\Accents;
use Maapkathi\Core\Theme\Backgrounds;
use Maapkathi\Core\Theme\Patterns;
use Maapkathi\Core\Theme\Fonts;
use Maapkathi\Core\Theme\Motion;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appearance → Theme + Motion. Renders all 32 theme_settings (§11.1) as
 * working, posting controls. Simple markup by design — the requirement is
 * that every control demonstrably changes the rendered site, not that this
 * screen wins a design award.
 *
 * @var array<string,mixed> $settings
 */

$is_custom_preset = 'custom' === $settings['motion_preset'];
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Appearance', 'maapkathi' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'mk_save_appearance', 'mk_appearance_nonce' ); ?>

		<h2><?php esc_html_e( 'Theme', 'maapkathi' ); ?></h2>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( '1. Mode', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( array( 'light', 'dark', 'system' ) as $mk_mode ) : ?>
						<label style="margin-right:1em">
							<input type="radio" name="mk_theme_settings[mode]" value="<?php echo esc_attr( $mk_mode ); ?>" <?php checked( $settings['mode'], $mk_mode ); ?> />
							<?php echo esc_html( ucfirst( $mk_mode ) ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '2. Accent', 'maapkathi' ); ?></th>
				<td>
					<div class="mk-swatch-grid">
						<?php foreach ( Accents::all() as $accent ) : ?>
							<label class="mk-swatch" style="background:<?php echo esc_attr( $accent['light'] ); ?>">
								<input type="radio" name="mk_theme_settings[accent_id]" value="<?php echo esc_attr( $accent['id'] ); ?>" <?php checked( $settings['accent_id'], $accent['id'] ); ?> />
								<span class="screen-reader-text"><?php echo esc_html( $accent['name'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '3. Custom accent hex', 'maapkathi' ); ?></th>
				<td><input type="text" name="mk_theme_settings[custom_accent_hex]" value="<?php echo esc_attr( (string) ( $settings['custom_accent_hex'] ?? '' ) ); ?>" placeholder="#RRGGBB" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '4. Pattern', 'maapkathi' ); ?></th>
				<td>
					<div class="mk-pattern-grid">
						<?php foreach ( Patterns::all() as $pattern ) : ?>
							<label class="mk-pattern-swatch">
								<input type="radio" name="mk_theme_settings[pattern_id]" value="<?php echo esc_attr( $pattern['id'] ); ?>" <?php checked( $settings['pattern_id'], $pattern['id'] ); ?> />
								<span class="mk-pattern-swatch__preview" style="<?php echo esc_attr( '--pattern-color:#8a8a8a;background:#f6f5f2;' . $pattern['css'] ); ?>" aria-hidden="true"></span>
								<span class="mk-pattern-swatch__label"><?php echo esc_html( $pattern['name'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '5. Pattern opacity', 'maapkathi' ); ?></th>
				<td><input type="range" min="0" max="100" name="mk_theme_settings[pattern_opacity]" value="<?php echo esc_attr( (string) $settings['pattern_opacity'] ); ?>" oninput="this.nextElementSibling.textContent=this.value" /> <output><?php echo esc_html( (string) $settings['pattern_opacity'] ); ?></output></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '6. Font pair', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_theme_settings[font_pair_id]">
						<?php foreach ( Fonts::all() as $pair ) : ?>
							<option value="<?php echo esc_attr( $pair['id'] ); ?>" <?php selected( $settings['font_pair_id'], $pair['id'] ); ?>><?php echo esc_html( $pair['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '7. Per-area font overrides', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( array( 'headings', 'body', 'nav', 'buttons', 'hero', 'accents' ) as $area ) : ?>
						<?php $row = $settings['font_overrides'][ $area ] ?? array(); ?>
						<p>
							<strong><?php echo esc_html( ucfirst( $area ) ); ?>:</strong>
							<select name="mk_theme_settings[font_overrides][<?php echo esc_attr( $area ); ?>][fontId]">
								<option value=""><?php esc_html_e( '(inherit)', 'maapkathi' ); ?></option>
								<?php foreach ( Fonts::all() as $pair ) : ?>
									<option value="<?php echo esc_attr( $pair['id'] ); ?>" <?php selected( $row['fontId'] ?? '', $pair['id'] ); ?>><?php echo esc_html( $pair['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="text" name="mk_theme_settings[font_overrides][<?php echo esc_attr( $area ); ?>][colorHex]" value="<?php echo esc_attr( (string) ( $row['colorHex'] ?? '' ) ); ?>" placeholder="#RRGGBB" size="8" />
						</p>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '8. Heading colour', 'maapkathi' ); ?></th>
				<td><input type="text" name="mk_theme_settings[heading_color_hex]" value="<?php echo esc_attr( (string) ( $settings['heading_color_hex'] ?? '' ) ); ?>" placeholder="#RRGGBB" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '9. Body colour', 'maapkathi' ); ?></th>
				<td><input type="text" name="mk_theme_settings[body_color_hex]" value="<?php echo esc_attr( (string) ( $settings['body_color_hex'] ?? '' ) ); ?>" placeholder="#RRGGBB" /></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '9a. Background tone', 'maapkathi' ); ?></th>
				<td>
					<div class="mk-swatch-grid mk-swatch-grid--tone">
						<?php foreach ( Backgrounds::all() as $mk_tone_id => $tone ) : ?>
							<label class="mk-swatch" style="background:<?php echo esc_attr( $tone['light']['background'] ); ?>">
								<input type="radio" name="mk_theme_settings[background_tone]" value="<?php echo esc_attr( $mk_tone_id ); ?>" <?php checked( $settings['background_tone'], $mk_tone_id ); ?> />
								<span class="screen-reader-text"><?php echo esc_html( $tone['name'] ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="description"><?php esc_html_e( 'Never solid white — a warm cream (default) or a cooler off-white/grey base for the whole site.', 'maapkathi' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '10. Radius', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( Fonts::RADIUS_SCALE as $mk_radius_id => $px ) : ?>
						<label style="margin-right:1em"><input type="radio" name="mk_theme_settings[radius]" value="<?php echo esc_attr( $mk_radius_id ); ?>" <?php checked( $settings['radius'], $mk_radius_id ); ?> /> <?php echo esc_html( ucfirst( $mk_radius_id ) ); ?> (<?php echo esc_html( $px ); ?>)</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '11. Density', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( array_keys( Fonts::DENSITY_SCALE ) as $mk_density_id ) : ?>
						<label style="margin-right:1em"><input type="radio" name="mk_theme_settings[density]" value="<?php echo esc_attr( $mk_density_id ); ?>" <?php checked( $settings['density'], $mk_density_id ); ?> /> <?php echo esc_html( ucfirst( $mk_density_id ) ); ?></label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '12. Grain', 'maapkathi' ); ?></th>
				<td><label><input type="checkbox" name="mk_theme_settings[grain]" value="1" <?php checked( $settings['grain'] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '13. Glass', 'maapkathi' ); ?></th>
				<td><label><input type="checkbox" name="mk_theme_settings[glass]" value="1" <?php checked( $settings['glass'] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '14. Hero style', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_theme_settings[hero_style]">
						<?php foreach ( array( 'full-bleed', 'contained', 'split', 'minimal' ) as $mk_hero_style_id ) : ?>
							<option value="<?php echo esc_attr( $mk_hero_style_id ); ?>" <?php selected( $settings['hero_style'], $mk_hero_style_id ); ?>><?php echo esc_html( ucwords( str_replace( '-', ' ', $mk_hero_style_id ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Motion', 'maapkathi' ); ?></h2>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( '15. Motion preset', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( array( 'off', 'subtle', 'refined', 'expressive', 'custom' ) as $mk_motion_preset_id ) : ?>
						<label style="margin-right:1em"><input type="radio" name="mk_theme_settings[motion_preset]" value="<?php echo esc_attr( $mk_motion_preset_id ); ?>" <?php checked( $settings['motion_preset'], $mk_motion_preset_id ); ?> /> <?php echo esc_html( ucfirst( $mk_motion_preset_id ) ); ?></label>
					<?php endforeach; ?>
				</td>
			</tr>
			<?php
			$selects = array(
				17 => array( 'scroll_reveal_style', __( 'Scroll reveal style', 'maapkathi' ), Motion::scroll_reveal_styles() ),
				18 => array( 'hero_animation', __( 'Hero animation', 'maapkathi' ), Motion::hero_animations() ),
				19 => array( 'image_hover_style', __( 'Image hover style', 'maapkathi' ), Motion::image_hover_styles() ),
				20 => array( 'card_hover_style', __( 'Card hover style', 'maapkathi' ), Motion::card_hover_styles() ),
				21 => array( 'text_reveal_style', __( 'Text reveal style', 'maapkathi' ), Motion::text_reveal_styles() ),
				22 => array( 'page_transition', __( 'Page transition', 'maapkathi' ), Motion::page_transitions() ),
			);
			foreach ( $selects as $n => [ $key, $label, $options ] ) :
				?>
				<tr>
					<th><?php echo esc_html( $n . '. ' . $label ); ?></th>
					<td>
						<select name="mk_theme_settings[<?php echo esc_attr( $key ); ?>]">
							<?php foreach ( $options as $option ) : ?>
								<option value="<?php echo esc_attr( $option['id'] ); ?>" <?php selected( $settings[ $key ], $option['id'] ); ?>><?php echo esc_html( $option['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th><?php esc_html_e( '23. Cursor style', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_theme_settings[cursor_style]">
						<?php foreach ( array( 'none', 'dot', 'ring', 'accent-blend' ) as $mk_cursor_style_id ) : ?>
							<option value="<?php echo esc_attr( $mk_cursor_style_id ); ?>" <?php selected( $settings['cursor_style'], $mk_cursor_style_id ); ?>><?php echo esc_html( ucwords( str_replace( '-', ' ', $mk_cursor_style_id ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '24. Loader style', 'maapkathi' ); ?></th>
				<td>
					<select name="mk_theme_settings[loader_style]">
						<?php foreach ( array( 'none', 'bar', 'logo-fade' ) as $mk_loader_style_id ) : ?>
							<option value="<?php echo esc_attr( $mk_loader_style_id ); ?>" <?php selected( $settings['loader_style'], $mk_loader_style_id ); ?>><?php echo esc_html( ucwords( str_replace( '-', ' ', $mk_loader_style_id ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '25. Scroll progress', 'maapkathi' ); ?></th>
				<td><label><input type="checkbox" name="mk_theme_settings[scroll_progress]" value="1" <?php checked( $settings['scroll_progress'] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '26. Smooth scroll', 'maapkathi' ); ?></th>
				<td><label><input type="checkbox" name="mk_theme_settings[smooth_scroll]" value="1" <?php checked( $settings['smooth_scroll'] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '27. Parallax intensity', 'maapkathi' ); ?></th>
				<td><input type="range" min="0" max="100" step="5" name="mk_theme_settings[parallax_intensity]" value="<?php echo esc_attr( (string) $settings['parallax_intensity'] ); ?>" oninput="this.nextElementSibling.textContent=this.value" /> <output><?php echo esc_html( (string) $settings['parallax_intensity'] ); ?></output></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '28. Motion speed', 'maapkathi' ); ?></th>
				<td><input type="range" min="50" max="150" step="5" name="mk_theme_settings[motion_speed]" value="<?php echo esc_attr( (string) $settings['motion_speed'] ); ?>" oninput="this.nextElementSibling.textContent=this.value+'%'" /> <output><?php echo esc_html( (string) $settings['motion_speed'] ); ?>%</output></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '29. Stagger (ms)', 'maapkathi' ); ?></th>
				<td>
					<input type="range" min="0" max="300" step="10" name="mk_theme_settings[stagger_ms]" value="<?php echo esc_attr( (string) $settings['stagger_ms'] ); ?>" <?php disabled( ! $is_custom_preset ); ?> oninput="this.nextElementSibling.textContent=this.value" />
					<output><?php echo esc_html( (string) $settings['stagger_ms'] ); ?></output>
					<p class="description mk-stagger-help" <?php echo $is_custom_preset ? 'hidden' : ''; ?>>
						<?php esc_html_e( 'Set the preset to Custom to tune this — every other preset supplies its own stagger.', 'maapkathi' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( '30. Animate once', 'maapkathi' ); ?></th>
				<td><label><input type="checkbox" name="mk_theme_settings[animate_once]" value="1" <?php checked( $settings['animate_once'] ); ?> /> <?php esc_html_e( 'On', 'maapkathi' ); ?></label></td>
			</tr>
			<tr>
				<th><?php esc_html_e( '31. Motion on mobile', 'maapkathi' ); ?></th>
				<td>
					<?php foreach ( array( 'full', 'reduced', 'off' ) as $mk_mobile_motion_id ) : ?>
						<label style="margin-right:1em"><input type="radio" name="mk_theme_settings[motion_on_mobile]" value="<?php echo esc_attr( $mk_mobile_motion_id ); ?>" <?php checked( $settings['motion_on_mobile'], $mk_mobile_motion_id ); ?> /> <?php echo esc_html( ucfirst( $mk_mobile_motion_id ) ); ?></label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save appearance settings', 'maapkathi' ) ); ?>
	</form>
</div>
