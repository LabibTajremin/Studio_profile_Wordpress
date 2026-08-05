<?php
declare( strict_types = 1 );

use Maapkathi\Core\Video\VideoResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_settings  = get_option( 'mk_site_settings', array() );
$theme_settings = \Maapkathi\Core\Theme\ThemeSettings::get();
$slides         = $site_settings['hero_slides'] ?? array();
$slide_seconds  = (int) ( $site_settings['hero_slide_duration'] ?? MK_HERO_SLIDE_SECONDS );

get_header();
?>
<section class="mk-hero" data-hero data-slide-seconds="<?php echo esc_attr( (string) $slide_seconds ); ?>" data-single="<?php echo esc_attr( count( $slides ) < 2 ? '1' : '0' ); ?>">
	<?php if ( empty( $slides ) ) : ?>
		<div class="mk-hero__slide mk-hero__slide--placeholder">
			<h1><?php echo esc_html( $site_settings['studio_name'] ?? get_bloginfo( 'name' ) ); ?></h1>
			<p><?php echo esc_html( $site_settings['tagline'] ?? get_bloginfo( 'description' ) ); ?></p>
		</div>
	<?php else : ?>
		<?php
		$resolver = new VideoResolver();
		foreach ( $slides as $i => $slide ) :
			$kind = $slide['media_kind'] ?? 'image';
			?>
			<div class="mk-hero__slide" data-index="<?php echo esc_attr( (string) $i ); ?>" data-hold="<?php echo esc_attr( ! empty( $slide['hold_until_video_ends'] ) ? '1' : '0' ); ?>">
				<?php if ( 'image' === $kind && ! empty( $slide['image_url'] ) ) : ?>
					<img src="<?php echo esc_url( $slide['image_url'] ); ?>" alt="<?php echo esc_attr( $slide['headline'] ?? '' ); ?>" class="mk-hero__media" loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>" fetchpriority="<?php echo 0 === $i ? 'high' : 'auto'; ?>" />
				<?php elseif ( 'gif' === $kind && ! empty( $slide['gif_url'] ) ) : ?>
					<img src="<?php echo esc_url( $slide['gif_url'] ); ?>" data-reduced-src="<?php echo esc_url( $slide['gif_first_frame_url'] ?? $slide['gif_url'] ); ?>" alt="<?php echo esc_attr( $slide['headline'] ?? '' ); ?>" class="mk-hero__media mk-hero__media--gif" loading="eager" fetchpriority="high" />
				<?php else :
					$video = $resolver->resolve(
						array(
							'video_source'     => $slide['video_source'] ?? 'upload',
							'video_upload_url' => $slide['video_upload_url'] ?? null,
							'video_url'        => $slide['video_url'] ?? null,
							'video_poster'     => $slide['video_poster'] ?? null,
						),
						true
					);
					if ( $video ) :
						if ( 'file' === $video->kind ) :
							?>
							<video class="mk-hero__media" autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url( $video->poster ?? '' ); ?>">
								<source src="<?php echo esc_url( $video->src ); ?>" />
							</video>
						<?php else : ?>
							<div class="mk-hero__embed" style="background-image:url('<?php echo esc_url( $video->poster ?? '' ); ?>')">
								<iframe src="<?php echo esc_url( $video->src ); ?>" title="<?php echo esc_attr( $slide['headline'] ?? 'Hero video' ); ?>" allow="autoplay; encrypted-media" referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation" loading="eager"></iframe>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				<?php endif; ?>

				<div class="mk-hero__copy">
					<?php if ( ! empty( $slide['eyebrow'] ) ) : ?><p class="mk-hero__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></p><?php endif; ?>
					<h1 class="mk-hero__headline"><?php echo esc_html( $slide['headline'] ?? '' ); ?></h1>
					<?php if ( ! empty( $slide['body'] ) ) : ?><p class="mk-hero__body"><?php echo esc_html( $slide['body'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $slide['cta_label'] ) && ! empty( $slide['cta_href'] ) ) : ?>
						<a class="mk-btn mk-btn--accent" href="<?php echo esc_url( $slide['cta_href'] ); ?>"><?php echo esc_html( $slide['cta_label'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</section>

<?php if ( ! empty( $site_settings['clients'] ) ) : ?>
<section class="mk-section mk-clients">
	<div class="mk-container mk-clients__wall">
		<?php foreach ( $site_settings['clients'] as $client ) : ?>
			<img src="<?php echo esc_url( $client['logo_url'] ?? '' ); ?>" alt="<?php echo esc_attr( $client['name'] ?? '' ); ?>" loading="lazy" />
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<section class="mk-section mk-projects" data-scroll-reveal>
	<div class="mk-container">
		<h2><?php esc_html_e( 'Featured Work', 'maapkathi' ); ?></h2>
		<div class="mk-grid mk-grid--projects">
			<?php
			$featured = new WP_Query(
				array(
					'post_type'      => 'mk_project',
					'posts_per_page' => 6,
					'meta_key'       => 'mk_is_featured',
					'meta_value'     => '1',
				)
			);
			while ( $featured->have_posts() ) :
				$featured->the_post();
				?>
				<a class="mk-card mk-card--project" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="mk-card__media"><?php the_post_thumbnail( 'large' ); ?></div>
					<?php endif; ?>
					<h3 class="mk-card__title"><?php the_title(); ?></h3>
				</a>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>

<?php if ( ! empty( $site_settings['faqs'] ) ) : ?>
<section class="mk-section mk-faq" data-scroll-reveal>
	<div class="mk-container">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'maapkathi' ); ?></h2>
		<div class="mk-accordion">
			<?php foreach ( $site_settings['faqs'] as $faq ) : ?>
				<details class="mk-accordion__item">
					<summary><?php echo esc_html( $faq['question'] ?? '' ); ?></summary>
					<div><?php echo wp_kses_post( $faq['answer'] ?? '' ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="mk-section mk-cta-band">
	<div class="mk-container">
		<h2><?php esc_html_e( "Let's build something", 'maapkathi' ); ?></h2>
		<a class="mk-btn mk-btn--accent" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Get in touch', 'maapkathi' ); ?></a>
	</div>
</section>
<?php
get_footer();
