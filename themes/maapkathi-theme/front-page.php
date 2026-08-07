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

$tagline_note = mk_text( 'home_tagline_note' );
$clients      = mk_content( 'clients' );
$categories   = mk_content( 'project_categories' );
$projects     = mk_content( 'featured_projects', 6 );
$services     = mk_content( 'top_level_services', 4 );
$stats        = mk_content( 'stats' );
$values       = mk_content( 'values' );
$members      = mk_content( 'members', 4 );
$testimonials = mk_content( 'testimonials' );
$awards       = mk_content( 'awards' );
$faqs         = mk_content( 'faqs' );
?>

<?php if ( $tagline_note ) : ?>
<section class="mk-section mk-tagline-note" data-scroll-reveal>
	<div class="mk-container">
		<p class="mk-tagline-note__text"><?php echo esc_html( $tagline_note ); ?></p>
	</div>
</section>
<?php endif; ?>

<?php if ( $clients ) : ?>
<section class="mk-section mk-clients" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_clients_heading' ); ?></h2>
		<div class="mk-clients__wall">
			<?php foreach ( $clients as $client ) : ?>
				<?php
				$website = mk_meta( $client->ID, 'mk_website' );
				$logo    = get_the_post_thumbnail( $client, 'medium', array( 'loading' => 'lazy' ) );
				?>
				<?php if ( $website ) : ?>
					<a class="mk-clients__item" href="<?php echo esc_url( $website ); ?>" rel="noopener noreferrer" target="_blank">
						<?php echo $logo ? $logo : esc_html( get_the_title( $client ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php else : ?>
					<span class="mk-clients__item">
						<?php echo $logo ? $logo : esc_html( get_the_title( $client ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $categories ) : ?>
<section class="mk-section mk-categories" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_categories_heading' ); ?></h2>
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
<section class="mk-section mk-projects" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_projects_heading' ); ?></h2>
		<div class="mk-grid mk-grid--projects">
			<?php foreach ( $projects as $project ) : ?>
				<a class="mk-card mk-card--project" href="<?php echo esc_url( (string) get_permalink( $project ) ); ?>">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $project ) ) : ?>
							<?php echo get_the_post_thumbnail( $project, 'large', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $project ), 1200, 1500 ) ); ?>" alt="" loading="lazy" />
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
		<p class="mk-section__more">
			<a class="mk-btn mk-btn--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( 'mk_project' ) ); ?>">
				<?php esc_html_e( 'View all work', 'maapkathi' ); ?>
			</a>
		</p>
	</div>
</section>
<?php endif; ?>

<?php if ( $services ) : ?>
<section class="mk-section mk-services" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_services_heading' ); ?></h2>
		<div class="mk-grid mk-grid--services">
			<?php foreach ( $services as $service ) : ?>
				<a class="mk-card mk-card--service" href="<?php echo esc_url( (string) get_permalink( $service ) ); ?>">
					<?php $icon = mk_meta( $service->ID, 'mk_icon' ); ?>
					<?php if ( $icon ) : ?>
						<span class="mk-card__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
					<?php endif; ?>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $service ) ); ?></h3>
					<p class="mk-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $service->post_content ), 18 ) ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $stats ) : ?>
<section class="mk-section mk-stats-band" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading mk-section__heading--on-accent"><?php mk_the_text( 'home_stats_heading' ); ?></h2>
		<div class="mk-grid mk-grid--stats">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="mk-stat">
					<span class="mk-stat__value">
						<?php echo esc_html( mk_meta( $stat->ID, 'mk_value_number' ) ); ?><?php echo esc_html( mk_meta( $stat->ID, 'mk_suffix' ) ); ?>
					</span>
					<span class="mk-stat__label"><?php echo esc_html( get_the_title( $stat ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $values ) : ?>
<section class="mk-section mk-values" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_values_heading' ); ?></h2>
		<div class="mk-grid mk-grid--values">
			<?php foreach ( $values as $value ) : ?>
				<div class="mk-card mk-card--value">
					<?php $icon = mk_meta( $value->ID, 'mk_icon' ); ?>
					<?php if ( $icon ) : ?>
						<span class="mk-card__icon dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
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
<section class="mk-section mk-team-preview" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_team_heading' ); ?></h2>
		<div class="mk-grid mk-grid--team">
			<?php foreach ( $members as $member ) : ?>
				<div class="mk-card mk-card--member">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $member ) ) : ?>
							<?php echo get_the_post_thumbnail( $member, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $member ), 600, 800 ) ); ?>" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $member ) ); ?></h3>
					<p class="mk-card__role"><?php echo esc_html( mk_meta( $member->ID, 'mk_role_title' ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="mk-section__more">
			<a class="mk-btn mk-btn--ghost" href="<?php echo esc_url( home_url( '/team/' ) ); ?>"><?php esc_html_e( 'Meet the team', 'maapkathi' ); ?></a>
		</p>
	</div>
</section>
<?php endif; ?>

<?php if ( $testimonials ) : ?>
<section class="mk-section mk-testimonials" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_testimonials_heading' ); ?></h2>
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
<section class="mk-section mk-awards" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_awards_heading' ); ?></h2>
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
<section class="mk-section mk-faq" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_faq_heading' ); ?></h2>
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

<section class="mk-section mk-cta-band">
	<div class="mk-container">
		<h2 class="mk-section__heading mk-section__heading--on-accent"><?php mk_the_text( 'home_cta_heading' ); ?></h2>
		<a class="mk-btn mk-btn--on-accent" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
			<?php mk_the_text( 'home_cta_button_label' ); ?>
		</a>
	</div>
</section>
<?php
get_footer();
