<?php
/**
 * Template Name: Team
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$members = get_posts(
	array(
		'post_type'      => 'mk_member',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);
?>
<div class="mk-container mk-section">
	<h1><?php the_title(); ?></h1>

	<div class="mk-grid mk-grid--team" data-scroll-reveal>
		<?php foreach ( $members as $member ) : ?>
			<div class="mk-card">
				<?php if ( has_post_thumbnail( $member ) ) : ?>
					<div class="mk-card__media"><?php echo get_the_post_thumbnail( $member, 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<h2 class="mk-card__title"><?php echo esc_html( get_the_title( $member ) ); ?></h2>
				<p class="mk-card__role"><?php echo esc_html( get_post_meta( $member->ID, 'mk_role_title', true ) ); ?></p>
				<p><?php echo esc_html( get_post_meta( $member->ID, 'mk_bio', true ) ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php
get_footer();
