<?php
/**
 * Homepage — all 13 sections from §3.2, in order:
 * hero carousel, tagline note, client logo wall, portfolio categories,
 * featured projects, services, stats band, values, team, testimonials,
 * awards, FAQ accordion, closing CTA band.
 *
 * Every section is self-hiding when it has no content, so a partially
 * filled site never renders an empty heading.
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

get_template_part( 'parts/hero' );

// Each section already self-hides when it has no content; these settings
// let an admin hide a section on purpose even when it has content, by
// simply not fetching it at all — same effect as "no content", so no
// other part of this template needs to know the toggle exists.
$tagline_note = mk_text( 'home_tagline_note' );
$clients      = mk_setting( 'section_clients_enabled', true ) ? mk_content( 'clients' ) : array();
$categories   = mk_setting( 'section_categories_enabled', true ) ? mk_content( 'project_categories' ) : array();
$services     = mk_setting( 'section_services_enabled', true ) ? mk_content( 'top_level_services', 4 ) : array();
$stats        = mk_setting( 'section_stats_enabled', true ) ? mk_content( 'stats' ) : array();
$values       = mk_setting( 'section_values_enabled', true ) ? mk_content( 'values' ) : array();
$members      = mk_setting( 'section_team_enabled', true ) ? mk_content( 'members', 4 ) : array();
$testimonials = mk_setting( 'section_testimonials_enabled', true ) ? mk_content( 'testimonials' ) : array();
$awards       = mk_setting( 'section_awards_enabled', true ) ? mk_content( 'awards' ) : array();
$faqs         = mk_setting( 'section_faq_enabled', true ) ? mk_content( 'faqs' ) : array();

// Whether the "Trusted by" tiles carry the client's name beside the logo.
$show_client_names = (bool) mk_setting( 'clients_show_name', true );

// FR-04: the gallery layout shows the admin's configured page size and
// offers "Load more"; the grid keeps its original fixed six.
$projects_layout = (string) mk_setting( 'projects_layout', 'showcase' );
$gallery_per     = (int) mk_setting( 'gallery_per_load', 12 );
$projects        = mk_setting( 'section_projects_enabled', true )
	? mk_content( 'featured_projects', 'gallery' === $projects_layout ? $gallery_per : 6 )
	: array();
?>

<?php if ( $tagline_note ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'tagline' ) ); ?>" class="mk-section mk-tagline-note" data-scroll-reveal>
	<div class="mk-container">
		<p class="mk-tagline-note__text"><?php echo esc_html( $tagline_note ); ?></p>
	</div>
</section>
<?php endif; ?>

<?php if ( $clients ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'clients' ) ); ?>" class="mk-section mk-clients" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'clients' ); ?>
		<div class="mk-clients__wall">
			<?php foreach ( $clients as $client ) : ?>
				<?php
				$website = mk_meta( $client->ID, 'mk_website' );
				$name    = get_the_title( $client );
				$logo    = get_the_post_thumbnail(
					$client,
					'medium',
					array(
						'loading' => 'lazy',
						'class'   => 'mk-clients__logo',
					)
				);
				// The name always accompanies a bare initials mark, whatever
				// the setting — otherwise a client with no logo uploaded
				// would render as an unidentifiable coloured square.
				$with_name = ( ! $logo || $show_client_names );
				// Named tiles lay the logo out small and left, ahead of the
				// text; logo-only tiles centre a much larger logo. The class
				// rides the tile rather than the wall so a logo-less client
				// still gets the named layout inside a logos-only wall.
				$item_class = $with_name ? 'mk-clients__item mk-clients__item--named' : 'mk-clients__item mk-clients__item--logo';
				?>
				<div class="<?php echo esc_attr( $item_class ); ?>" title="<?php echo esc_attr( $name ); ?>">
					<?php if ( $website ) : ?>
						<a href="<?php echo esc_url( $website ); ?>" rel="noopener noreferrer" target="_blank" class="mk-clients__link">
					<?php endif; ?>

					<?php if ( $logo ) : ?>
						<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<span class="mk-clients__mark" style="<?php echo esc_attr( 'background-color:' . mk_client_mark_color( $name ) ); ?>"><?php echo esc_html( mk_client_initials( $name ) ); ?></span>
					<?php endif; ?>

					<?php if ( $with_name ) : ?>
						<span class="mk-clients__name"><?php echo esc_html( $name ); ?></span>
					<?php endif; ?>

					<?php if ( $website ) : ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $categories ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'categories' ) ); ?>" class="mk-section mk-categories" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'categories' ); ?>
		<div class="mk-grid mk-grid--categories">
			<?php foreach ( $categories as $category_term ) : ?>
				<a class="mk-card mk-card--category" href="<?php echo esc_url( get_term_link( $category_term ) ); ?>">
					<h3 class="mk-card__title"><?php echo esc_html( $category_term->name ); ?></h3>
					<span class="mk-card__count">
						<?php
						printf(
							/* translators: %d: number of projects in this category. */
							esc_html( _n( '%d project', '%d projects', (int) $category_term->count, 'maapkathi' ) ),
							(int) $category_term->count
						);
						?>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $projects ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'projects' ) ); ?>" class="mk-section mk-projects mk-projects--<?php echo esc_attr( $projects_layout ); ?>" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'projects' ); ?>
		<?php if ( 'showcase' === $projects_layout ) : ?>
			<?php require get_theme_file_path( 'parts/section-projects-showcase.php' ); ?>
		<?php elseif ( 'gallery' === $projects_layout ) : ?>
			<?php
			$gallery_click  = (string) mk_setting( 'gallery_click', 'lightbox' );
			$gallery_total  = (int) wp_count_posts( 'mk_project' )->publish;
			$gallery_shown  = count( $projects );
			$gallery_cols   = (int) mk_setting( 'gallery_max_columns', 4 );
			$gallery_gutter = (int) mk_setting( 'gallery_gutter', 16 );
			?>
			<ul
				class="mk-masonry"
				style="--mk-masonry-cols: <?php echo esc_attr( (string) $gallery_cols ); ?>; --mk-masonry-gutter: <?php echo esc_attr( (string) $gallery_gutter ); ?>px;"
				data-mk-gallery
				<?php echo 'lightbox' === $gallery_click ? 'data-lightbox-gallery' : ''; ?>
			>
				<?php
				echo mk_gallery_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by GalleryRenderer, escaped at source.
					$projects,
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
						data-mk-gallery-more
						data-offset="<?php echo esc_attr( (string) $gallery_shown ); ?>"
						data-per-page="<?php echo esc_attr( (string) $gallery_per ); ?>"
					>
						<?php esc_html_e( 'Load more', 'maapkathi' ); ?>
					</button>
				</p>
			<?php endif; ?>
		<?php else : ?>
		<div class="mk-grid mk-grid--projects">
			<?php foreach ( $projects as $project ) : ?>
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
<?php endif; ?>

<?php if ( $services ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'services' ) ); ?>" class="mk-section mk-services mk-services--icon-<?php echo esc_attr( (string) mk_theme_setting( 'services_icon_position', 'beside' ) ); ?>" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'services' ); ?>
		<div class="mk-grid mk-grid--services">
			<?php foreach ( $services as $service ) : ?>
				<a class="mk-card mk-card--service" href="<?php echo esc_url( (string) get_permalink( $service ) ); ?>">
					<?php $icon = mk_item_icon( $service->ID ); ?>
					<?php
					// The icon and the title share a row so the title sits
					// beside the icon rather than under it; the description
					// then spans the full card width below both.
					?>
					<div class="mk-card__head">
						<?php if ( $icon ) : ?>
							<span class="mk-card__icon" aria-hidden="true"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by IconRenderer, escaped at source. ?></span>
						<?php endif; ?>
						<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $service ) ); ?></h3>
					</div>
					<p class="mk-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $service->post_content ), 18 ) ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $stats ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'stats' ) ); ?>" class="mk-section mk-stats-band" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'stats', 'h2', 'mk-section__heading mk-section__heading--on-accent' ); ?>
		<div class="mk-grid mk-grid--stats">
			<?php foreach ( $stats as $stat ) : ?>
				<?php
				$stat_value    = (float) mk_meta( $stat->ID, 'mk_value_number' );
				$stat_is_whole = (float) (int) $stat_value === $stat_value;
				$stat_display  = $stat_is_whole ? (string) (int) $stat_value : (string) $stat_value;
				?>
				<div class="mk-stat">
					<span class="mk-stat__value" data-count-to="<?php echo esc_attr( (string) $stat_value ); ?>">
						<span class="mk-stat__value-number"><?php echo esc_html( $stat_display ); ?></span><span class="mk-stat__value-suffix"><?php echo esc_html( mk_meta( $stat->ID, 'mk_suffix' ) ); ?></span>
					</span>
					<span class="mk-stat__label"><?php echo esc_html( get_the_title( $stat ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $values ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'values' ) ); ?>" class="mk-section mk-values" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'values' ); ?>
		<div class="mk-grid mk-grid--values">
			<?php foreach ( $values as $value ) : ?>
				<div class="mk-card mk-card--value">
					<?php $icon = mk_item_icon( $value->ID ); ?>
					<?php if ( $icon ) : ?>
						<span class="mk-card__icon" aria-hidden="true"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by IconRenderer, escaped at source. ?></span>
					<?php endif; ?>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $value ) ); ?></h3>
					<p><?php echo esc_html( wp_strip_all_tags( $value->post_content ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $members ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'team' ) ); ?>" class="mk-section mk-team-preview" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'team' ); ?>
		<div class="mk-grid mk-grid--team">
			<?php foreach ( $members as $member ) : ?>
				<div class="mk-card mk-card--member">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $member ) ) : ?>
							<?php echo get_the_post_thumbnail( $member, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $member ), 600, 800 ) ); ?>" width="600" height="800" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $member ) ); ?></h3>
					<p class="mk-card__role"><?php echo esc_html( mk_meta( $member->ID, 'mk_role_title' ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="mk-section__more">
			<a class="mk-link-more" href="<?php echo esc_url( home_url( '/team/' ) ); ?>"><?php esc_html_e( 'Meet the team', 'maapkathi' ); ?></a>
		</p>
	</div>
</section>
<?php endif; ?>

<?php if ( $testimonials ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'testimonials' ) ); ?>" class="mk-section mk-testimonials" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'testimonials' ); ?>
		<div class="mk-grid mk-grid--testimonials">
			<?php foreach ( $testimonials as $testimonial ) : ?>
				<?php
				$rating      = (int) mk_meta( $testimonial->ID, 'mk_rating', '0' );
				$author      = mk_meta( $testimonial->ID, 'mk_author_name', get_the_title( $testimonial ) );
				$author_role = mk_meta( $testimonial->ID, 'mk_author_role' );
				$firm        = mk_meta( $testimonial->ID, 'mk_company' );
				?>
				<figure class="mk-card mk-card--testimonial">
					<?php if ( $rating > 0 ) : ?>
						<div class="mk-rating" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating out of five. */ __( '%d out of 5 stars', 'maapkathi' ), $rating ) ); ?>">
							<?php echo esc_html( str_repeat( '★', min( 5, $rating ) ) . str_repeat( '☆', max( 0, 5 - $rating ) ) ); ?>
						</div>
					<?php endif; ?>
					<blockquote><?php echo esc_html( mk_meta( $testimonial->ID, 'mk_quote' ) ); ?></blockquote>
					<figcaption>
						<strong><?php echo esc_html( $author ); ?></strong>
						<?php if ( $author_role || $firm ) : ?>
							<span><?php echo esc_html( trim( $author_role . ( $author_role && $firm ? ', ' : '' ) . $firm ) ); ?></span>
						<?php endif; ?>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $awards ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'awards' ) ); ?>" class="mk-section mk-awards" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'awards' ); ?>
		<ul class="mk-awards__list">
			<?php foreach ( $awards as $award ) : ?>
				<?php
				$issuer     = mk_meta( $award->ID, 'mk_issuer' );
				$award_year = mk_meta( $award->ID, 'mk_year' );
				$award_link = mk_meta( $award->ID, 'mk_link' );
				?>
				<li class="mk-awards__item">
					<span class="mk-awards__title">
						<?php if ( $award_link ) : ?>
							<a href="<?php echo esc_url( $award_link ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( get_the_title( $award ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( get_the_title( $award ) ); ?>
						<?php endif; ?>
					</span>
					<span class="mk-awards__meta"><?php echo esc_html( trim( $issuer . ( $issuer && $award_year ? ' · ' : '' ) . $award_year ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<?php if ( $faqs ) : ?>
<section id="<?php echo esc_attr( mk_section_anchor( 'faq' ) ); ?>" class="mk-section mk-faq" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( 'faq' ); ?>
		<div class="mk-accordion">
			<?php foreach ( $faqs as $faq ) : ?>
				<details class="mk-accordion__item">
					<summary><?php echo esc_html( $faq['question'] ); ?></summary>
					<div class="mk-accordion__body"><?php echo esc_html( $faq['answer'] ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
// FR-05.1: the same map block, behind its own separate home toggle.
get_template_part( 'parts/section-map', null, array( 'context' => 'home' ) );
?>

<section id="<?php echo esc_attr( mk_section_anchor( 'cta' ) ); ?>" class="mk-section mk-cta-band">
	<div class="mk-container">
		<?php mk_the_section_heading( 'cta', 'h2', 'mk-section__heading mk-section__heading--on-accent' ); ?>
		<a class="mk-btn mk-btn--on-accent" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
			<?php mk_the_text( 'home_cta_button_label' ); ?>
		</a>
	</div>
</section>
<?php
get_footer();
