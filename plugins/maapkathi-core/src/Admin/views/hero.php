<?php
declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array<int,array<string,mixed>> $slides
 * @var int    $duration
 * @var string $notice
 */

$mk_empty_row = array(
	'media_kind'            => 'image',
	'eyebrow'               => '',
	'headline'              => '',
	'body'                  => '',
	'cta_label'             => '',
	'cta_href'              => '',
	'image_url'             => '',
	'gif_url'               => '',
	'gif_first_frame_url'   => '',
	'video_source'          => 'upload',
	'video_upload_url'      => '',
	'video_url'             => '',
	'video_poster'          => '',
	'hold_until_video_ends' => false,
	'is_active'             => true,
);

// A saved slide only carries the keys for its own media_kind, so merge
// every row over the full default shape before rendering.
$mk_rows = array_map(
	static fn( array $row ) => array_merge( $mk_empty_row, $row ),
	array_pad( $slides, 8, $mk_empty_row )
);

$mk_kinds = array(
	'image'        => __( 'Image', 'maapkathi' ),
	'gif'          => __( 'GIF', 'maapkathi' ),
	'video_upload' => __( 'Video upload', 'maapkathi' ),
	'video_link'   => __( 'Video link', 'maapkathi' ),
);

settings_errors( 'mk_hero' );
?>
<div class="wrap mk-admin">
	<h1><?php esc_html_e( 'Hero Carousel', 'maapkathi' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php echo esc_html( $notice ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site ↗', 'maapkathi' ); ?></a>
			</p>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'mk_save_hero', 'mk_hero_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th><label for="mk-hero-duration"><?php esc_html_e( 'Slide duration', 'maapkathi' ); ?></label></th>
				<td>
					<input id="mk-hero-duration" type="number" min="3" max="20" name="hero_slide_duration" value="<?php echo esc_attr( (string) $duration ); ?>" />
					<span><?php esc_html_e( 'seconds', 'maapkathi' ); ?></span>
					<p class="description"><?php esc_html_e( 'How long each slide stays on screen before advancing (3–20, default 6). With only one slide there is no rotation at all.', 'maapkathi' ); ?></p>
				</td>
			</tr>
		</table>

		<?php foreach ( $mk_rows as $mk_i => $mk_slide ) : ?>
			<?php $mk_name = 'slides[' . $mk_i . ']'; ?>
			<fieldset class="mk-card" data-hero-slide>
				<legend><strong><?php printf( esc_html__( 'Slide %d', 'maapkathi' ), (int) $mk_i + 1 ); ?></strong></legend>

				<p>
					<strong><?php esc_html_e( 'Media kind', 'maapkathi' ); ?></strong><br />
					<?php foreach ( $mk_kinds as $mk_kind => $mk_label ) : ?>
						<label style="margin-right:1em">
							<input type="radio" data-media-kind
								name="<?php echo esc_attr( $mk_name ); ?>[media_kind]"
								value="<?php echo esc_attr( $mk_kind ); ?>"
								<?php checked( $mk_slide['media_kind'], $mk_kind ); ?> />
							<?php echo esc_html( $mk_label ); ?>
						</label>
					<?php endforeach; ?>
				</p>

				<p class="mk-hero-length-note" hidden>
					<strong><?php printf( esc_html__( 'Keep it to about %d seconds.', 'maapkathi' ), (int) $duration ); ?></strong>
					<?php printf( esc_html__( 'Hero slides change automatically every %d seconds, so anything longer gets cut off mid-play. Silent, seamless loops work best. Recommended: MP4 (H.264), 1080p, 2–4 Mbps — roughly 2–3 MB for a 6-second clip.', 'maapkathi' ), (int) $duration ); ?>
				</p>

				<!-- Image -->
				<p data-kind-field="image">
					<label>
						<?php esc_html_e( 'Image URL', 'maapkathi' ); ?>
						<input type="url" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[image_url]" value="<?php echo esc_attr( $mk_slide['image_url'] ); ?>" />
					</label>
					<span class="description"><?php esc_html_e( 'Recommended ratio 16:9 (e.g. 2400×1350).', 'maapkathi' ); ?></span>
				</p>

				<!-- GIF -->
				<p data-kind-field="gif">
					<label>
						<?php esc_html_e( 'GIF URL', 'maapkathi' ); ?>
						<input type="url" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[gif_url]" value="<?php echo esc_attr( $mk_slide['gif_url'] ); ?>" />
					</label>
					<span class="description"><?php esc_html_e( 'An MP4 of the same animation is usually ~10× smaller and looks better.', 'maapkathi' ); ?></span>
				</p>
				<p data-kind-field="gif">
					<label>
						<?php esc_html_e( 'GIF first frame URL', 'maapkathi' ); ?>
						<input type="url" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[gif_first_frame_url]" value="<?php echo esc_attr( $mk_slide['gif_first_frame_url'] ); ?>" />
					</label>
					<span class="description"><?php esc_html_e( 'Shown instead of the animation to visitors who prefer reduced motion.', 'maapkathi' ); ?></span>
				</p>

				<!-- Uploaded video -->
				<p data-kind-field="video_upload">
					<label>
						<?php esc_html_e( 'Uploaded video URL', 'maapkathi' ); ?>
						<input type="url" class="regular-text" data-video-url-field name="<?php echo esc_attr( $mk_name ); ?>[video_upload_url]" value="<?php echo esc_attr( $mk_slide['video_upload_url'] ); ?>" />
					</label>
					<span class="description"><?php esc_html_e( 'Upload an MP4 in Media, then paste its file URL here.', 'maapkathi' ); ?></span>
					<span class="mk-video-duration-warning" hidden></span>
				</p>

				<!-- External link -->
				<p data-kind-field="video_link">
					<label>
						<?php esc_html_e( 'Video link', 'maapkathi' ); ?>
						<input type="url" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[video_url]" value="<?php echo esc_attr( $mk_slide['video_url'] ); ?>" />
					</label>
					<span class="description"><?php esc_html_e( 'YouTube, Vimeo, or a direct https .mp4/.webm URL. Anything else is rejected on save.', 'maapkathi' ); ?></span>
				</p>

				<!-- Poster (both video kinds) -->
				<p data-kind-field="video_upload video_link">
					<label>
						<?php esc_html_e( 'Poster image URL', 'maapkathi' ); ?>
						<input type="url" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[video_poster]" value="<?php echo esc_attr( $mk_slide['video_poster'] ); ?>" />
					</label>
					<span class="description"><?php esc_html_e( 'Required — shown if the browser refuses autoplay, and to reduced-motion visitors.', 'maapkathi' ); ?></span>
				</p>

				<p data-kind-field="video_upload video_link">
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $mk_name ); ?>[hold_until_video_ends]" value="1" <?php checked( ! empty( $mk_slide['hold_until_video_ends'] ) ); ?> />
						<?php esc_html_e( 'Hold slide until the video ends', 'maapkathi' ); ?>
					</label>
					<span class="description"><?php printf( esc_html__( 'Capped at %d seconds so one long clip cannot strand the carousel.', 'maapkathi' ), (int) ( defined( 'MK_MAX_HERO_HOLD_SECONDS' ) ? MK_MAX_HERO_HOLD_SECONDS : 20 ) ); ?></span>
				</p>

				<hr />

				<p>
					<label><?php esc_html_e( 'Eyebrow', 'maapkathi' ); ?>
						<input type="text" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[eyebrow]" value="<?php echo esc_attr( $mk_slide['eyebrow'] ); ?>" />
					</label>
				</p>
				<p>
					<label><?php esc_html_e( 'Headline', 'maapkathi' ); ?>
						<input type="text" class="regular-text" name="<?php echo esc_attr( $mk_name ); ?>[headline]" value="<?php echo esc_attr( $mk_slide['headline'] ); ?>" />
					</label>
				</p>
				<p>
					<label><?php esc_html_e( 'Body', 'maapkathi' ); ?>
						<textarea class="large-text" rows="2" name="<?php echo esc_attr( $mk_name ); ?>[body]"><?php echo esc_textarea( $mk_slide['body'] ); ?></textarea>
					</label>
				</p>
				<p>
					<label><?php esc_html_e( 'Button label', 'maapkathi' ); ?>
						<input type="text" name="<?php echo esc_attr( $mk_name ); ?>[cta_label]" value="<?php echo esc_attr( $mk_slide['cta_label'] ); ?>" />
					</label>
					&nbsp;
					<label><?php esc_html_e( 'Button URL', 'maapkathi' ); ?>
						<input type="url" name="<?php echo esc_attr( $mk_name ); ?>[cta_href]" value="<?php echo esc_attr( $mk_slide['cta_href'] ); ?>" />
					</label>
				</p>
				<p>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $mk_name ); ?>[is_active]" value="1" <?php checked( ! empty( $mk_slide['is_active'] ) ); ?> />
						<?php esc_html_e( 'Show this slide on the site', 'maapkathi' ); ?>
					</label>
				</p>
			</fieldset>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save hero slides', 'maapkathi' ) ); ?>
	</form>
</div>
