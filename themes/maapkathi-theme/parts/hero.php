<?php
/**
 * Hero carousel (§6.3). One media kind per slide: still image, animated
 * GIF, uploaded MP4, or external video link — all resolved through the
 * plugin's VideoResolver, never from raw admin input.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

use Maapkathi\Core\Video\VideoResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mk_slides = array_values(
	array_filter(
		(array) mk_setting( 'hero_slides', array() ),
		static function ( $slide ) {
			// A slide with no active flag at all predates the toggle, so
			// treat it as active; an explicit false means hidden.
			return ! isset( $slide['is_active'] ) || $slide['is_active'];
		}
	)
);

$mk_slide_seconds = (int) mk_setting( 'hero_slide_duration', defined( 'MK_HERO_SLIDE_SECONDS' ) ? MK_HERO_SLIDE_SECONDS : 6 );
$mk_resolver      = class_exists( VideoResolver::class ) ? new VideoResolver() : null;
?>
<section class="mk-hero"
	data-hero
	data-slide-seconds="<?php echo esc_attr( (string) $mk_slide_seconds ); ?>"
	data-single="<?php echo esc_attr( count( $mk_slides ) < 2 ? '1' : '0' ); ?>">

	<?php if ( empty( $mk_slides ) ) : ?>
		<div class="mk-hero__slide mk-hero__slide--placeholder is-active">
			<div class="mk-hero__copy">
				<p class="mk-hero__eyebrow"><?php mk_the_text( 'home_hero_eyebrow' ); ?></p>
				<h1 class="mk-hero__headline"><?php echo esc_html( mk_setting( 'studio_name', get_bloginfo( 'name' ) ) ); ?></h1>
				<p class="mk-hero__body"><?php echo esc_html( mk_setting( 'tagline', get_bloginfo( 'description' ) ) ); ?></p>
				<a class="mk-btn mk-btn--on-accent" href="<?php echo esc_url( (string) get_post_type_archive_link( 'mk_project' ) ); ?>">
					<?php esc_html_e( 'View our work', 'maapkathi' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<?php foreach ( $mk_slides as $mk_i => $mk_slide ) : ?>
			<?php
			$mk_kind     = $mk_slide['media_kind'] ?? 'image';
			$mk_first    = 0 === $mk_i;
			$mk_eager    = $mk_first ? 'eager' : 'lazy';
			$mk_priority = $mk_first ? 'high' : 'auto';
			$mk_alt      = $mk_slide['headline'] ?? '';
			?>
			<div class="mk-hero__slide<?php echo $mk_first ? ' is-active' : ''; ?>"
				data-index="<?php echo esc_attr( (string) $mk_i ); ?>"
				data-kind="<?php echo esc_attr( $mk_kind ); ?>"
				data-hold="<?php echo esc_attr( ! empty( $mk_slide['hold_until_video_ends'] ) ? '1' : '0' ); ?>">

				<div class="mk-hero__media-wrap">
				<?php if ( 'image' === $mk_kind ) : ?>
					<?php $mk_src = $mk_slide['image_url'] ?? ''; ?>
					<img class="mk-hero__media"
						width="2400" height="1350"
						src="<?php echo esc_url( $mk_src ? $mk_src : mk_placeholder_url( $mk_alt, 2400, 1350 ) ); ?>"
						alt="<?php echo esc_attr( $mk_alt ); ?>"
						loading="<?php echo esc_attr( $mk_eager ); ?>"
						fetchpriority="<?php echo esc_attr( $mk_priority ); ?>" />

				<?php elseif ( 'gif' === $mk_kind ) : ?>
					<?php
					$mk_gif   = $mk_slide['gif_url'] ?? '';
					$mk_frame = $mk_slide['gif_first_frame_url'] ?? '';
					?>
					<img class="mk-hero__media mk-hero__media--gif"
						width="2400" height="1350"
						src="<?php echo esc_url( $mk_gif ? $mk_gif : mk_placeholder_url( $mk_alt, 2400, 1350 ) ); ?>"
						<?php
						if ( $mk_frame ) :
							?>
							data-reduced-src="<?php echo esc_url( $mk_frame ); ?>"<?php endif; ?>
						alt="<?php echo esc_attr( $mk_alt ); ?>"
						loading="eager"
						fetchpriority="high" />

				<?php else : ?>
					<?php
					$mk_video = $mk_resolver
						? $mk_resolver->resolve(
							array(
								'video_source'     => $mk_slide['video_source'] ?? ( 'video_link' === $mk_kind ? 'link' : 'upload' ),
								'video_upload_url' => $mk_slide['video_upload_url'] ?? null,
								'video_url'        => $mk_slide['video_url'] ?? null,
								'video_poster'     => $mk_slide['video_poster'] ?? null,
							),
							true
						)
						: null;

					// Poster is mandatory: a browser-refused autoplay must
					// degrade to a still frame, never a black box (§6.2).
					$mk_poster = $mk_video && $mk_video->poster
						? $mk_video->poster
						: ( $mk_slide['video_poster'] ?? mk_placeholder_url( $mk_alt, 2400, 1350 ) );
					?>

					<?php if ( $mk_video && 'file' === $mk_video->kind ) : ?>
						<video class="mk-hero__media"
							autoplay muted loop playsinline preload="metadata"
							poster="<?php echo esc_url( $mk_poster ); ?>">
							<source src="<?php echo esc_url( $mk_video->src ); ?>" />
						</video>
						<img class="mk-hero__media mk-hero__media--reduced" width="2400" height="1350" src="<?php echo esc_url( $mk_poster ); ?>" alt="<?php echo esc_attr( $mk_alt ); ?>" />

					<?php elseif ( $mk_video ) : ?>
						<div class="mk-hero__embed" style="background-image:url('<?php echo esc_url( $mk_poster ); ?>')">
							<iframe
								src="<?php echo esc_url( $mk_video->src ); ?>"
								title="<?php echo esc_attr( $mk_alt ? $mk_alt : __( 'Hero video', 'maapkathi' ) ); ?>"
								allow="autoplay; encrypted-media; picture-in-picture"
								referrerpolicy="strict-origin-when-cross-origin"
								loading="<?php echo esc_attr( $mk_first ? 'eager' : 'lazy' ); ?>"
								frameborder="0"
								allowfullscreen></iframe>
						</div>
						<img class="mk-hero__media mk-hero__media--reduced" width="2400" height="1350" src="<?php echo esc_url( $mk_poster ); ?>" alt="<?php echo esc_attr( $mk_alt ); ?>" />

					<?php else : ?>
						<img class="mk-hero__media" width="2400" height="1350" src="<?php echo esc_url( $mk_poster ); ?>" alt="<?php echo esc_attr( $mk_alt ); ?>" loading="<?php echo esc_attr( $mk_eager ); ?>" />
					<?php endif; ?>
				<?php endif; ?>
				</div>

				<div class="mk-hero__copy">
					<?php if ( ! empty( $mk_slide['eyebrow'] ) ) : ?>
						<p class="mk-hero__eyebrow"><?php echo esc_html( $mk_slide['eyebrow'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $mk_slide['headline'] ) ) : ?>
						<?php if ( $mk_first ) : ?>
							<h1 class="mk-hero__headline"><?php echo esc_html( $mk_slide['headline'] ); ?></h1>
						<?php else : ?>
							<p class="mk-hero__headline"><?php echo esc_html( $mk_slide['headline'] ); ?></p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! empty( $mk_slide['body'] ) ) : ?>
						<p class="mk-hero__body"><?php echo esc_html( $mk_slide['body'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $mk_slide['cta_label'] ) && ! empty( $mk_slide['cta_href'] ) ) : ?>
						<a class="mk-btn mk-btn--on-accent" href="<?php echo esc_url( $mk_slide['cta_href'] ); ?>">
							<?php echo esc_html( $mk_slide['cta_label'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<?php if ( count( $mk_slides ) > 1 ) : ?>
			<div class="mk-hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'Hero slides', 'maapkathi' ); ?>">
				<?php foreach ( $mk_slides as $mk_i => $mk_slide ) : ?>
					<button type="button"
						class="mk-hero__dot<?php echo 0 === $mk_i ? ' is-active' : ''; ?>"
						data-slide-to="<?php echo esc_attr( (string) $mk_i ); ?>"
						role="tab"
						aria-selected="<?php echo 0 === $mk_i ? 'true' : 'false'; ?>"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'maapkathi' ), $mk_i + 1 ) ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>
