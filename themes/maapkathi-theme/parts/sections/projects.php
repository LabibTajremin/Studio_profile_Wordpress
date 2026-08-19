<?php
/**
 * Homepage section: featured work.
 *
 * Rendered once per instance by front-page.php, which passes the
 * instance's id. Nothing here may assume the section appears only once on
 * the page (FR-03.5), so the anchor and the gallery's DOM hooks are
 * derived from $mk_id rather than hardcoded.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables supplied by front-page.php.
 *
 * @var string $mk_id Section instance id.
 */
$projects_layout = (string) mk_setting( 'projects_layout', 'showcase' );
$gallery_per     = (int) mk_setting( 'gallery_per_load', 12 );
$mk_data         = mk_setting( 'section_projects_enabled', true )
	? mk_content( 'featured_projects', 'gallery' === $projects_layout ? $gallery_per : 6 )
	: array();

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-projects mk-projects--<?php echo esc_attr( $projects_layout ); ?>" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<?php if ( 'showcase' === $projects_layout ) : ?>
			<?php require get_theme_file_path( 'parts/section-projects-showcase.php' ); ?>
		<?php elseif ( 'gallery' === $projects_layout ) : ?>
			<?php
			$gallery_click  = (string) mk_setting( 'gallery_click', 'lightbox' );
			$gallery_total  = (int) wp_count_posts( 'mk_project' )->publish;
			$gallery_shown  = count( $mk_data );
			$gallery_cols   = (int) mk_setting( 'gallery_max_columns', 4 );
			$gallery_gutter = (int) mk_setting( 'gallery_gutter', 16 );
			?>
			<ul
				class="mk-masonry"
				style="--mk-masonry-cols: <?php echo esc_attr( (string) $gallery_cols ); ?>; --mk-masonry-gutter: <?php echo esc_attr( (string) $gallery_gutter ); ?>px;"
				id="mk-masonry-<?php echo esc_attr( $mk_id ); ?>"
				data-mk-gallery
				<?php echo 'lightbox' === $gallery_click ? 'data-lightbox-gallery' : ''; ?>
			>
				<?php
				echo mk_gallery_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by GalleryRenderer, escaped at source.
					$mk_data,
					array(
						'click'  => $gallery_click,
						'offset' => 0,
					)
				);
				?>
			</ul>
			<?php if ( $gallery_total > $gallery_shown ) : ?>
				<p class="mk-masonry__actions">
					<button
						type="button"
						class="mk-btn mk-btn--ghost mk-masonry__more"
						data-mk-gallery-more="mk-masonry-<?php echo esc_attr( $mk_id ); ?>"
						data-offset="<?php echo esc_attr( (string) $gallery_shown ); ?>"
						data-per-page="<?php echo esc_attr( (string) $gallery_per ); ?>"
					>
						<?php esc_html_e( 'Load more', 'maapkathi' ); ?>
					</button>
				</p>
			<?php endif; ?>
		<?php else : ?>
		<div class="mk-grid mk-grid--projects">
			<?php foreach ( $mk_data as $project ) : ?>
				<a class="mk-card mk-card--project" href="<?php echo esc_url( (string) get_permalink( $project ) ); ?>">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $project ) ) : ?>
							<?php echo get_the_post_thumbnail( $project, 'large', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $project ), 1200, 1500 ) ); ?>" width="1200" height="1500" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $project ) ); ?></h3>
					<?php $summary = mk_meta( $project->ID, 'mk_summary' ); ?>
					<?php if ( $summary ) : ?>
						<p class="mk-card__excerpt"><?php echo esc_html( wp_trim_words( $summary, 18 ) ); ?></p>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<p class="mk-section__more">
			<a class="mk-link-more" href="<?php echo esc_url( (string) get_post_type_archive_link( 'mk_project' ) ); ?>">
				<?php esc_html_e( 'View all work', 'maapkathi' ); ?>
			</a>
		</p>
	</div>
</section>
