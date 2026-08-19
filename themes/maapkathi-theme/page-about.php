<?php
/**
 * Template Name: About
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mk_stats   = mk_content( 'stats' );
$mk_values  = mk_content( 'values' );
$mk_awards  = mk_content( 'awards' );
$mk_members = mk_content( 'members', 4 );
$mk_steps   = mk_content( 'process_steps' );
?>
<div class="mk-container mk-section">
	<?php mk_the_section_heading( 'about_page', 'h1', 'mk-page-title' ); ?>
	<p class="mk-lede"><?php echo esc_html( mk_setting( 'tagline', mk_text( 'about_intro' ) ) ); ?></p>

	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<div class="mk-prose"><?php the_content(); ?></div>
		<?php endwhile; ?>
	<?php endif; ?>
</div>

<?php if ( mk_setting( 'vision_mission_enabled' ) ) : ?>
<section class="mk-section mk-vision-mission" data-scroll-reveal>
	<div class="mk-container">
		<div class="mk-grid mk-grid--halves">
			<div class="mk-card">
				<h2><?php mk_the_text( 'about_vision_heading' ); ?></h2>
				<p><?php echo esc_html( mk_setting( 'vision_text' ) ); ?></p>
			</div>
			<div class="mk-card">
				<h2><?php mk_the_text( 'about_mission_heading' ); ?></h2>
				<p><?php echo esc_html( mk_setting( 'mission_text' ) ); ?></p>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $mk_stats ) : ?>
<section class="mk-section mk-stats-band" data-scroll-reveal>
	<div class="mk-container">
		<div class="mk-grid mk-grid--stats">
			<?php foreach ( $mk_stats as $mk_stat ) : ?>
				<div class="mk-stat">
					<span class="mk-stat__value"><?php echo esc_html( mk_meta( $mk_stat->ID, 'mk_value_number' ) ); ?><?php echo esc_html( mk_meta( $mk_stat->ID, 'mk_suffix' ) ); ?></span>
					<span class="mk-stat__label"><?php echo esc_html( get_the_title( $mk_stat ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $mk_values ) : ?>
<section class="mk-section mk-values" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_values_heading' ); ?></h2>
		<div class="mk-grid mk-grid--values">
			<?php foreach ( $mk_values as $mk_value ) : ?>
				<div class="mk-card mk-card--value">
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $mk_value ) ); ?></h3>
					<p><?php echo esc_html( wp_strip_all_tags( $mk_value->post_content ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $mk_steps ) : ?>
<section class="mk-section mk-process" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php esc_html_e( 'How we work', 'maapkathi' ); ?></h2>
		<ol class="mk-process__list">
			<?php foreach ( $mk_steps as $mk_step ) : ?>
				<li class="mk-process__item">
					<span class="mk-process__no"><?php echo esc_html( mk_meta( $mk_step->ID, 'mk_step_no' ) ); ?></span>
					<div>
						<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $mk_step ) ); ?></h3>
						<p><?php echo esc_html( wp_strip_all_tags( $mk_step->post_content ) ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php endif; ?>

<?php if ( $mk_awards ) : ?>
<section class="mk-section mk-awards" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_awards_heading' ); ?></h2>
		<ul class="mk-awards__list">
			<?php foreach ( $mk_awards as $mk_award ) : ?>
				<li class="mk-awards__item">
					<span class="mk-awards__title"><?php echo esc_html( get_the_title( $mk_award ) ); ?></span>
					<span class="mk-awards__meta"><?php echo esc_html( trim( mk_meta( $mk_award->ID, 'mk_issuer' ) . ' · ' . mk_meta( $mk_award->ID, 'mk_year' ), ' ·' ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<?php if ( $mk_members ) : ?>
<section class="mk-section mk-team-preview" data-scroll-reveal>
	<div class="mk-container">
		<h2 class="mk-section__heading"><?php mk_the_text( 'home_team_heading' ); ?></h2>
		<div class="mk-grid mk-grid--team">
			<?php foreach ( $mk_members as $mk_member ) : ?>
				<div class="mk-card mk-card--member">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $mk_member ) ) : ?>
							<?php echo get_the_post_thumbnail( $mk_member, 'medium', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $mk_member ), 600, 800 ) ); ?>" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $mk_member ) ); ?></h3>
					<p class="mk-card__role"><?php echo esc_html( mk_meta( $mk_member->ID, 'mk_role_title' ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="mk-section__more">
			<a class="mk-link-more" href="<?php echo esc_url( home_url( '/team/' ) ); ?>"><?php esc_html_e( 'Meet the team', 'maapkathi' ); ?></a>
		</p>
	</div>
</section>
<?php endif; ?>
<?php
get_footer();
