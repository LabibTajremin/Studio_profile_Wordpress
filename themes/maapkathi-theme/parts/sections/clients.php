<?php
/**
 * Homepage section: client wall.
 *
 * Rendered once per instance by front-page.php, which passes the
 * instance's id. Nothing here may assume the section appears only once on
 * the page (FR-03.5), so the anchor and every DOM id are derived from
 * $mk_id rather than hardcoded.
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
$mk_data = mk_setting( 'section_clients_enabled', true ) ? mk_content( 'clients' ) : array();

// Whether the tiles carry the client's name beside the logo.
$show_client_names = (bool) mk_setting( 'clients_show_name', true );

if ( ! $mk_data ) {
	return;
}
?>
<section id="<?php echo esc_attr( mk_section_anchor( $mk_id ) ); ?>" class="mk-section mk-clients" data-scroll-reveal>
	<div class="mk-container">
		<?php mk_the_section_heading( $mk_id ); ?>
		<div class="mk-clients__wall">
			<?php foreach ( $mk_data as $client ) : ?>
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
