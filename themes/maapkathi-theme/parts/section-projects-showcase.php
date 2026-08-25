<?php
/**
 * Featured work — showcase layout.
 *
 * A horizontal rail of tiles inside the section's gutter: hovering a tile
 * washes it and lifts a caption carrying the project title and its
 * category. Required from the projects section partial, which owns $mk_data.
 *
 * The rail scrolls natively — touch, trackpad, scrollbar and keyboard all
 * work with no JavaScript at all. The arrows are added by showcase.js on
 * top of that, so nothing here renders a control that would sit dead if
 * the script fails to load.
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
 * @var \WP_Post[] $mk_data Projects to show.
 */
?>
<div class="mk-showcase-rail" data-mk-showcase>
	<ul class="mk-showcase" tabindex="0" role="list" aria-label="<?php esc_attr_e( 'Featured work', 'maapkathi' ); ?>">
	<?php foreach ( $mk_data as $mk_showcase_project ) : ?>
		<?php
		$mk_showcase_title = get_the_title( $mk_showcase_project );
		$mk_showcase_terms = get_the_terms( $mk_showcase_project, 'mk_project_category' );
		$mk_showcase_cat   = ( is_array( $mk_showcase_terms ) && $mk_showcase_terms )
			? $mk_showcase_terms[0]->name
			: '';
		?>
		<li class="mk-showcase__item">
			<a class="mk-showcase__link" href="<?php echo esc_url( (string) get_permalink( $mk_showcase_project ) ); ?>">
				<?php if ( has_post_thumbnail( $mk_showcase_project ) ) : ?>
					<?php
					echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by core.
						$mk_showcase_project,
						'large',
						array(
							'class'    => 'mk-showcase__image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(min-width: 992px) 33vw, (min-width: 600px) 50vw, 100vw',
							'alt'      => $mk_showcase_title,
						)
					);
					?>
				<?php else : ?>
					<img class="mk-showcase__image" src="<?php echo esc_url( mk_placeholder_url( $mk_showcase_title, 1200, 800 ) ); ?>" width="1200" height="800" alt="" loading="lazy" />
				<?php endif; ?>

				<span class="mk-showcase__veil" aria-hidden="true"></span>

				<?php
				// The caption is real text rather than a background overlay,
				// so it is selectable, translatable and read out in order by
				// a screen reader even though it is visually revealed on
				// hover.
				?>
				<span class="mk-showcase__caption">
					<span class="mk-showcase__title"><?php echo esc_html( $mk_showcase_title ); ?></span>
					<?php if ( '' !== $mk_showcase_cat ) : ?>
						<span class="mk-showcase__meta"><?php echo esc_html( $mk_showcase_cat ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
	</ul>
</div>
