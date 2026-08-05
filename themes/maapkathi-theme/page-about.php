<?php
/**
 * Template Name: About
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_settings = get_option( 'mk_site_settings', array() );

get_header();
?>
<div class="mk-container mk-section">
	<h1><?php echo esc_html( $site_settings['studio_name'] ?? get_bloginfo( 'name' ) ); ?></h1>
	<p class="mk-lede"><?php echo esc_html( $site_settings['tagline'] ?? get_bloginfo( 'description' ) ); ?></p>
	<div class="mk-prose"><?php the_content(); ?></div>
</div>

<?php if ( ! empty( $site_settings['vision_mission_enabled'] ) ) : ?>
<section class="mk-section mk-container" data-scroll-reveal>
	<div class="mk-grid" style="grid-template-columns:1fr 1fr">
		<div>
			<h2><?php esc_html_e( 'Vision', 'maapkathi' ); ?></h2>
			<p><?php echo esc_html( $site_settings['vision_text'] ?? '' ); ?></p>
		</div>
		<div>
			<h2><?php esc_html_e( 'Mission', 'maapkathi' ); ?></h2>
			<p><?php echo esc_html( $site_settings['mission_text'] ?? '' ); ?></p>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="mk-section mk-container" data-scroll-reveal>
	<h2><?php esc_html_e( 'Stats', 'maapkathi' ); ?></h2>
	<div class="mk-grid mk-grid--stats">
		<?php
		$stats = get_posts( array( 'post_type' => 'mk_stat', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		foreach ( $stats as $stat ) :
			?>
			<div class="mk-stat">
				<span class="mk-stat__value"><?php echo esc_html( get_post_meta( $stat->ID, 'mk_value_number', true ) ); ?><?php echo esc_html( get_post_meta( $stat->ID, 'mk_suffix', true ) ); ?></span>
				<span class="mk-stat__label"><?php echo esc_html( get_the_title( $stat ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<section class="mk-section mk-container" data-scroll-reveal>
	<h2><?php esc_html_e( 'Values', 'maapkathi' ); ?></h2>
	<div class="mk-grid">
		<?php
		$values = get_posts( array( 'post_type' => 'mk_value', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		foreach ( $values as $value ) :
			?>
			<div class="mk-card">
				<h3 class="mk-card__title"><?php echo esc_html( get_the_title( $value ) ); ?></h3>
				<p><?php echo esc_html( wp_strip_all_tags( $value->post_content ) ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php
get_footer();
