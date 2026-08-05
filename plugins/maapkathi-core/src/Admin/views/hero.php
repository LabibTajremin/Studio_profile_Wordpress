<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array<int,array<string,mixed>> $slides
 * @var int $duration
 * @var string $notice
 */

$empty_row = array(
	'media_kind' => 'image',
	'eyebrow' => '', 'headline' => '', 'body' => '', 'cta_label' => '', 'cta_href' => '',
	'image_url' => '', 'gif_url' => '', 'gif_first_frame_url' => '',
	'video_source' => 'upload', 'video_upload_url' => '', 'video_url' => '', 'video_poster' => '',
	'hold_until_video_ends' => false, 'is_active' => true,
);
$rows = array_pad( $slides, 8, $empty_row );

settings_errors( 'mk_hero' );
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Hero Carousel', 'maapkathi' ); ?></h1>
	<?php if ( $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_hero', 'mk_hero_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Slide duration (seconds)', 'maapkathi' ); ?></th>
				<td>
					<input type="number" min="3" max="20" name="hero_slide_duration" value="<?php echo esc_attr( (string) $duration ); ?>" />
					<p class="description"><?php esc_html_e( 'Slides change automatically every N seconds (3-20, default 6).', 'maapkathi' ); ?></p>
				</td>
			</tr>
		</table>

		<?php foreach ( $rows as $i => $slide ) : ?>
			<fieldset class="mk-card" style="margin-bottom:1rem">
				<legend><strong><?php printf( esc_html__( 'Slide %d', 'maapkathi' ), $i + 1 ); ?></strong></legend>

				<p>
					<?php foreach ( array( 'image' => 'Image', 'gif' => 'GIF', 'video_upload' => 'Video upload', 'video_link' => 'Video link' ) as $kind => $label ) : ?>
						<label style="margin-right:1em">
							<input type="radio" name="slides[<?php echo esc_attr( (string) $i ); ?>][media_kind]" value="<?php echo esc_attr( $kind ); ?>" <?php checked( $slide['media_kind'], $kind ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</p>

				<p class="description">
					<?php printf( esc_html__( 'Keep it to about %d seconds. Hero slides change automatically, so anything longer gets cut off mid-play. Silent, seamless loops work best.', 'maapkathi' ), $duration ); ?>
				</p>

				<p><label><?php esc_html_e( 'Eyebrow', 'maapkathi' ); ?> <input type="text" name="slides[<?php echo esc_attr( (string) $i ); ?>][eyebrow]" value="<?php echo esc_attr( $slide['eyebrow'] ); ?>" class="regular-text" /></label></p>
				<p><label><?php esc_html_e( 'Headline', 'maapkathi' ); ?> <input type="text" name="slides[<?php echo esc_attr( (string) $i ); ?>][headline]" value="<?php echo esc_attr( $slide['headline'] ); ?>" class="regular-text" /></label></p>
				<p><label><?php esc_html_e( 'Body', 'maapkathi' ); ?> <textarea name="slides[<?php echo esc_attr( (string) $i ); ?>][body]" rows="2" class="large-text"><?php echo esc_textarea( $slide['body'] ); ?></textarea></label></p>
				<p>
					<label><?php esc_html_e( 'CTA label', 'maapkathi' ); ?> <input type="text" name="slides[<?php echo esc_attr( (string) $i ); ?>][cta_label]" value="<?php echo esc_attr( $slide['cta_label'] ); ?>" /></label>
					<label><?php esc_html_e( 'CTA URL', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][cta_href]" value="<?php echo esc_attr( $slide['cta_href'] ); ?>" /></label>
				</p>

				<p><label><?php esc_html_e( 'Image URL (for Image kind)', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][image_url]" value="<?php echo esc_attr( $slide['image_url'] ); ?>" class="regular-text" /></label></p>
				<p>
					<label><?php esc_html_e( 'GIF URL (for GIF kind)', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][gif_url]" value="<?php echo esc_attr( $slide['gif_url'] ); ?>" class="regular-text" /></label>
					<label><?php esc_html_e( 'GIF first-frame URL (reduced motion)', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][gif_first_frame_url]" value="<?php echo esc_attr( $slide['gif_first_frame_url'] ); ?>" class="regular-text" /></label>
				</p>
				<p><label><?php esc_html_e( 'Uploaded video URL (for Video upload kind)', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][video_upload_url]" value="<?php echo esc_attr( $slide['video_upload_url'] ); ?>" class="regular-text" /></label></p>
				<p><label><?php esc_html_e( 'Video link (YouTube/Vimeo/direct .mp4, for Video link kind)', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][video_url]" value="<?php echo esc_attr( $slide['video_url'] ); ?>" class="regular-text" /></label></p>
				<p><label><?php esc_html_e( 'Poster image (required for both video kinds)', 'maapkathi' ); ?> <input type="url" name="slides[<?php echo esc_attr( (string) $i ); ?>][video_poster]" value="<?php echo esc_attr( $slide['video_poster'] ); ?>" class="regular-text" /></label></p>

				<p>
					<label><input type="checkbox" name="slides[<?php echo esc_attr( (string) $i ); ?>][hold_until_video_ends]" value="1" <?php checked( ! empty( $slide['hold_until_video_ends'] ) ); ?> /> <?php esc_html_e( 'Hold slide until video ends (capped at 20s)', 'maapkathi' ); ?></label>
					&nbsp;&nbsp;
					<label><input type="checkbox" name="slides[<?php echo esc_attr( (string) $i ); ?>][is_active]" value="1" <?php checked( ! isset( $slide['is_active'] ) || $slide['is_active'] ); ?> /> <?php esc_html_e( 'Active', 'maapkathi' ); ?></label>
				</p>
			</fieldset>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save hero slides', 'maapkathi' ) ); ?>
	</form>
</div>
