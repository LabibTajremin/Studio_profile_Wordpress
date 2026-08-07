<?php
/**
 * Template Name: Team
 *
 * @package maapkathi-theme
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$mk_members = mk_content( 'members' );
?>
<div class="mk-container mk-section">
	<h1 class="mk-page-title"><?php mk_the_text( 'team_heading' ); ?></h1>
	<?php $mk_intro = mk_text( 'team_intro' ); ?>
	<?php if ( $mk_intro ) : ?>
		<p class="mk-lede"><?php echo esc_html( $mk_intro ); ?></p>
	<?php endif; ?>

	<?php if ( $mk_members ) : ?>
		<div class="mk-grid mk-grid--team" data-scroll-reveal>
			<?php foreach ( $mk_members as $mk_member ) : ?>
				<?php
				$mk_linkedin  = mk_meta( $mk_member->ID, 'mk_social_linkedin' );
				$mk_instagram = mk_meta( $mk_member->ID, 'mk_social_instagram' );
				?>
				<div class="mk-card mk-card--member">
					<div class="mk-card__media">
						<?php if ( has_post_thumbnail( $mk_member ) ) : ?>
							<?php echo get_the_post_thumbnail( $mk_member, 'medium_large', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<img src="<?php echo esc_url( mk_placeholder_url( get_the_title( $mk_member ), 600, 800 ) ); ?>" alt="" loading="lazy" />
						<?php endif; ?>
					</div>
					<h2 class="mk-card__title"><?php echo esc_html( get_the_title( $mk_member ) ); ?></h2>
					<p class="mk-card__role"><?php echo esc_html( mk_meta( $mk_member->ID, 'mk_role_title' ) ); ?></p>
					<?php $mk_bio = mk_meta( $mk_member->ID, 'mk_bio' ); ?>
					<?php if ( $mk_bio ) : ?>
						<p class="mk-card__bio"><?php echo esc_html( $mk_bio ); ?></p>
					<?php endif; ?>

					<?php if ( $mk_linkedin || $mk_instagram ) : ?>
						<p class="mk-card__socials">
							<?php if ( $mk_linkedin ) : ?>
								<a href="<?php echo esc_url( $mk_linkedin ); ?>" rel="noopener noreferrer" target="_blank">
									<?php echo esc_html__( 'LinkedIn', 'maapkathi' ); ?>
									<span class="screen-reader-text"><?php echo esc_html( get_the_title( $mk_member ) ); ?></span>
								</a>
							<?php endif; ?>
							<?php if ( $mk_instagram ) : ?>
								<a href="<?php echo esc_url( $mk_instagram ); ?>" rel="noopener noreferrer" target="_blank">
									<?php echo esc_html__( 'Instagram', 'maapkathi' ); ?>
									<span class="screen-reader-text"><?php echo esc_html( get_the_title( $mk_member ) ); ?></span>
								</a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="mk-empty-state"><?php esc_html_e( 'Team profiles are coming soon.', 'maapkathi' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
